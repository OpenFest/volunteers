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
    $user = User::load($user);

    $user->resetToken();
//verify non-verified volunteers linked to this user
    $database->query(
            'UPDATE volunteers SET verified = TRUE WHERE "user" = :uid AND verified = FALSE',
            [':uid' => $user->getId()]
    );

    return $user;
}

if ($user = verify($this->database)) {
    // Set the user in the session
    $_SESSION['user'] = $user;
    header('Location: /profile');
    exit;
}

