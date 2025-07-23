<?php

// If the user is already logged in and is admin, redirect to the backbone page, otherwise redirect to the home page
if (isset($_SESSION['user'])){
	if($_SESSION['user']->isAdmin()) {
		header('Location: /backbone');
		exit;
	} else {
		header('Location: /');
		exit;
	}
}
//if post request, process the login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Get the email and password from the POST request
	$username = $_POST['username'] ?? '';
	$password = $_POST['password'] ?? '';

	// Validate email and password
	if (empty($username) || empty($password)) {
		echo "<h1>Моля, въведете валидни данни за вход.</h1>";
		return; // Stop further processing
	}
	//verify the password against ldap
    try{
	    if (!User::ldapTestPassword($username, $password)) {
		    echo "<h1>Грешен имейл или парола.</h1>";
		    return; // Stop further processing
	    }
    } catch (Exception $e) {
        // If there is an error with LDAP, we can log it or handle it accordingly
        // For now, we will just display a generic error message
        echo "<h1>Грешка в системата! Моля, опитайте по-късно!</h1>";
        return; // Stop further processing
    }


	// Check if the user exists in the database
	$users = $this->database->query(
		'SELECT * FROM users WHERE username = :username OR email = :username AND active = true',
		[':username' => $username]
	);
	try {
        //fetch user from LDAP
		$userData = iterator_to_array(User::ldapGetUser($username));
	} catch (Exception $e) {
		echo "<h1>Грешка в системата! Моля, опитайте по-късно!</h1>";
		return; // Stop further processing
	}
	$userData = array_shift($userData); //get the first element from the generator
	if (!$userData) {
		echo "<h1>Хм... нещо се обърка...</h1>";
		return; // Stop further processing
	}
    $user = NULL;
    if (count($users) === 1) {
        $user = $users[0];
	    //TODO: sync the user data with the LDAP data (including permissions)
        if ($user->email !== $userData->email || $user->name !== $userData->name . ' ' . $userData->sirName) {
            //update the user data
            $this->database->query(
                'UPDATE users SET email = :email, name = :name WHERE uid = :uid',
                [
                    ':email' => $userData->email,
                    ':name' => $userData->name . ' ' . $userData->sirName,
                    ':uid' => $user->uid,
                ]
            );
        }
    }
    if (!$user) {
        //create the new user, based on the ldap data
        $uuid = uuid();
        $res = $this->database->query(
            'INSERT INTO users (uid, username, email, name, active) VALUES (:uid, :username, :email, :name, true)',
            [
	            ':uid' => $uuid,
                ':username' => $userData->uid,
                ':email' => $userData->email,
                ':name' => $userData->name . ' ' . $userData->sirName,
            ]
        );
        if (!$res) {
            echo "<h1>Грешка при създаване на потребител!</h1>";
            return; // Stop further processing
        }
        $user = $res[0];

	    $user = new User($uuid, $userData->email, $userData->uid, $userData->name . ' ' . $userData->sirName, true);

        $_SESSION['user'] = $user;

        if ($user->isAdmin()) {
            header('Location: /backbone');
        } else {
            header('Location: /');
        }
        exit;
    }

	echo "<h3 class='login-error'>Грешен имейл или парола.</h1>";
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
    </div>
</form>
