<?php

/**
 * If the user is already logged in and is admin, redirect to the backbone page,
 * otherwise redirect to the home page
 * @return void
 */
function redirectIfLoggedIn(): void
{
    if (isset($_SESSION['user'])) {
        if ($_SESSION['user']->isAdmin()) {
            header('Location: /backbone');
        } else {
            header('Location: /profile');
        }
        exit;
    }
}
redirectIfLoggedIn();

function handlePost($database): void
{
    try{
	    $ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
    } catch (Exception $e) {
        _log("LDAP connection failed: " . $e->getMessage(), LOG_ERR);
        echo "<h3 class='login-error'>Грешка в системата. Моля, опитайте по-късно!</h3>";
        return; // Stop further processing
    }
	// Get the username and password from the POST request
	$username = $_POST['username'] ?? '';
	$password = $_POST['password'] ?? '';

	// Validate username and password
	if (empty($username) || empty($password)) {
        _log("Login attempt with empty username or password");
		echo "<h3 class='login-error'>Моля, въведете валидни данни за вход.</h3>";
		return; // Stop further processing
	}
    // get ldap user
    try {
        $ldapUser = $ldap->getUser($username);
    } catch (Exception $e) {
        _log("LDAP search failed for user: " . $username . " - " . $e->getMessage(), LOG_ERR);
        $ldapUser = null;
    }

	//verify the password against ldap
	if (!$ldapUser) {
        _log("Login attempt with non-existing user: " . $username);
		echo "<h3 class='login-error'>Грешен имейл или парола.</h1>";
		return; // Stop further processing
	}
    try{
        $ldap->testBind($ldapUser, $password);
    } catch (Exception $e) {
        _log("LDAP bind failed for user: " . $username . " - " . $e->getMessage(), LOG_ERR);
        echo "<h3 class='login-error'>Грешен имейл или парола.</h1>";
        return; // Stop further processing
    }
	// Check if the user exists in the local database
	$users = $database->query(
		'SELECT * FROM users WHERE lower(username) = lower(:username) OR lower(email) = lower(:username)',
		[':username' => $username]
	);
    // check admin access flag
    $userInCore = $ldap->isMember($ldapUser, ['core','global admin']);
    unset($ldap); // Free the LDAP connection

	$user = NULL;
	if (count($users) === 1) {
		$user = $users[0];
		//TODO: sync the user data with the LDAP data (including permissions)
		if (
                $user->email !== $ldapUser->mail ||
                $user->name !== $ldapUser->givenname . ' ' . $ldapUser->sn ||
                $user->admin !== $userInCore
        ) {
			//update the user data
			$database->query(
				'UPDATE users SET email = :email, name = :name, admin = :admin WHERE uid = :uid',
				[
					':email' => $ldapUser->mail,
					':name' => $ldapUser->givenname . ' ' . $ldapUser->sn,
                    ':admin' => $userInCore ? 'true' : 'false',
					':uid' => $user->uid,
				]
			);
            _log("User data updated for user: " . $user->uid);
		}
		$user = new User($user->uid, $ldapUser->mail, $ldapUser->uid, $ldapUser->givenname . ' ' . $ldapUser->sn, $user->phone, $userInCore);
	}

	if (!$user) {
		//create the new user, based on the ldap data
        _log("Creating new user: " . $ldapUser->uid);
		$uuid = uuid();
		$res = $database->query(
			'INSERT INTO users (uid, username, email, name, active, admin) VALUES (:uid, :username, :email, :name, true, :admin)',
			[
				':uid' => $uuid,
				':username' => $ldapUser->uid,
				':email' => $ldapUser->mail,
				':name' => $ldapUser->givenname . ' ' . $ldapUser->sn,
                ':admin' => $userInCore ? 'true' : 'false',
			]
		);
		if (!$res) {
            _log("Failed to create user: " . $ldapUser->uid, LOG_ERR);
			echo "<h1>Грешка при създаване на потребител!</h1>";
			return; // Stop further processing
		}
        // If the user was created successfully, create a new User object
        _log("User created successfully: " . $uuid);
		$user = new User($uuid, $ldapUser->mail, $ldapUser->uid, $ldapUser->givenname . ' ' . $ldapUser->sn, NULL, $userInCore);
	}
	$_SESSION['user'] = $user;
    redirectIfLoggedIn();
}

//if post request, process the login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	handlePost($this->database);
}
?>
<form action="/login" method="post" class="login-form">
    <div class="input">
        <label for="username">Username/Email:</label>
        <input type="text" name="username" id="username" required>
    </div>
    <div class="input">
        <label for="password">Парола:</label>
        <input type="password" name="password" id="password" required>
    </div>
	<br>
    <div class="form-actions">
        <button class="btn" type="submit">Вход</button>
        <a href="/password-reset" class="btn-link">Забравена парола?</a>
    </div>
</form>
