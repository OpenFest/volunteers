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
	$email = $_POST['email'] ?? '';
	$password = $_POST['password'] ?? '';

	// Validate email and password
	if (empty($email) || empty($password)) {
		echo "<h1>Моля, въведете валидни данни за вход.</h1>";
		return; // Stop further processing
	}
	// Sanitize email and password
	$email = filter_var($email, FILTER_SANITIZE_EMAIL);
	// Check if the email is valid
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo "<h1>Моля, въведете валиден имейл адрес.</h1>";
		return; // Stop further processing
	}

	//verify the password

	// Check if the user exists in the database
	$users = $this->database->query(
		'SELECT * FROM users WHERE email = :email AND active = true',
		[
			':email' => $email,
		]
	);
    $user = NULL;
    if (count($users) === 1) {
        $user = $users[0];
    }

	if ($user) {
		//verify the password (we should use ldap for this, but for now we will use the database)
		if (password_verify($password, $user->password)) {
			// load the user object
			$_SESSION['user'] = new User($user->uid, $user->email, true);

            //redirect to the backbone page if the user is admin, otherwise redirect to the home page
			if ($_SESSION['user']->isAdmin()) {
				header('Location: /backbone');
			} else {
				header('Location: /');
			}
			exit;
		}
	}
	echo "<h3 class='login-error'>Грешен имейл или парола.</h1>";
}
?>
<form action="/login" method="post" class="login-form">
    <div class="input">
        <label for="email">Имейл:</label>
        <input type="email" name="email" id="email" required>
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
