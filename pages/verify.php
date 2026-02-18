<h1>Потвърждение на регистрация</h1>
<?php
function verify($database): User|bool|null
{
//verify token from volunteers submission
	if (empty($_GET['token'])) {
		echo "<h3 class='login-error'>Невалиден токен.</h3>";
		return FALSE;
	}
	$token = $_GET['token'];
// Check if the token is valid
	$user = $database->query(
		'SELECT * from users where token = :token and token_expiry > NOW()',
		[':token' => $token]
	);

	if (empty($user)) {
		echo "<h3 class='login-error'>Невалиден токен.</h3>";
		return FALSE;
	}

// The user exists, log them in and set the active to true
	$user = $user[0];
// Set the user as active
	$database->query(
		'UPDATE users SET token = NULL, token_expiry = NULL WHERE uid = :uid',
		[':uid' => $user->uid]
	);
//verify non-verified volunteers linked to this user
	$database->query(
		'UPDATE volunteers SET verified = TRUE WHERE "user" = :uid AND verified = FALSE',
		[':uid' => $user->uid]
	);

	return User::load($user);
}
if ($user = verify($this->database)) {
    if($user->isActive()) {
        //add user to LDAP groups, based on team data
        try {
            $user->addToLdapGroups(Conference::getActive()->getSlug());
        } catch (Exception $e) {
            _log('Failed to add user to LDAP groups: ' . $user->getUsername() . ' - ' . $e->getMessage(), LOG_ERR);
        }
    }
    // Set the user in the session
    $_SESSION['user'] = $user;
	header('Location: /profile');
	exit;
}

