<?php

$user = $_SESSION['user'] ?? null;
if (!$user->isAdmin()) {
	header('Location: /');
	exit;
}

$volunteerId = $_GET['volunteer'] ?? null;
$status = $_GET['status'] ?? null;
if (!$volunteerId) {
	header('Location: /backbone');
	exit;
}

if (!in_array($status, [ 'accept', 'deny'])) {
	echo "<h3 class='login-error'>Невалиден статус.</h3>";
	exit;
}

$volunteer = $this->database->query(
	'SELECT * FROM volunteers WHERE id = :id',
	[':id' => $volunteerId]
);

if (!$volunteer) {
	echo "<h3 class='login-error'>Доброволецът не е намерен.</h3>";
	exit;
}

$volunteer = $volunteer[0];
$newState = $status === 'accept' ? 'accepted' : 'denied';
$this->database->query("UPDATE volunteers SET status = :status, status_update_date = now() WHERE id = :id",
[':status' => $newState, ':id' => $volunteerId]
);

header('Location: /backbone/volunteers');
exit;