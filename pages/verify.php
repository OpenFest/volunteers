<?php

//verify token from volunteers submission
if (empty($_GET['token'])) {
	echo "<h3 class='login-error'>Невалиден токен.</h3>";
	exit;
}
$token = $_GET['token'];
// Check if the token is valid
$user = $this->database->query(
	'SELECT * from users where token = :token and token_expiry > NOW()',
	[':token' => $token]
);

if (empty($user)) {
	echo "<h3 class='login-error'>Невалиден токен.</h3>";
	exit;
}

// The user exists, log them in and set the active to true
$user = $user[0];
// Set the user as active
$this->database->query(
	'UPDATE users SET active = TRUE, token = NULL, token_expiry = NULL WHERE uid = :uid',
	[':uid' => $user->uid]
);
// Set the user in the session
$_SESSION['user'] = $user;
header('Location: /profile');
exit;

