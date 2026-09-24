<?php
//check if user is logged in and has admin rights

checkAdmin();

$volunteerId = $_REQUEST['volunteer'] ?? $rawInput['volunteer'] ?? null;
_log("Sending verification link for volunteer ID {$volunteerId}");
if (!$volunteerId) {
	echo "<h3 class='login-error'>Невалиден доброволец.</h3>";
	return FALSE;
}

//get volunteer data and user data for ldap refresh (temp way to fix ldap access)
$volunteer = $this->database->query(
	'SELECT * FROM volunteers WHERE id = :id',
	[':id' => $volunteerId]
);
$volunteer = array_shift($volunteer);
$user = User::load($volunteer->user);
if ($volunteer->verified) {
	echo "<h3 class='login-error'>Доброволецът вече е верифициран.</h3>";
	return FALSE;
}

$user->resetToken();
$token = User::generateToken($volunteerId);


$message = <<<EOT
Здравейте {$volunteer->name},
Моля, кликнете на следния линк, за да потвърдите вашия имейл адрес и да завършите регистрацията си като доброволец за конференцията:
https://volunteer.example.com/verify?token={$token}
(Този линк ще изтече след 24 часа.)

Поздрави,
Екипът на конференцията
EOT;

if (!_mail($user->getEmail(), "Потвърждение на регистрация като доброволец", $message)) {
	echo "<h3 class='login-error'>Грешка при изпращане на имейл. Моля, опитайте отново по-късно.</h3>";
	return FALSE;
}
$user->setToken($token, '24 hours');
_log("Verification link sent to volunteer ID {$volunteerId} [{$volunteer->name}] ({$user->getEmail()})");
header("Location: /backbone/volunteers");
exit;
