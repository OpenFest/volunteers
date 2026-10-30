<?php

checkAdmin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'POST required.']);
	exit;
}

$rawInput = json_decode(file_get_contents('php://input'), true);
$volunteerId = filter_var($rawInput['volunteer'] ?? null, FILTER_VALIDATE_INT);
$taken = filter_var($rawInput['taken'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if (!$volunteerId || $taken === null) {
	echo json_encode(['success' => false, 'message' => 'Invalid volunteer.']);
	exit;
}

$affected = $this->database->execute(
	'UPDATE volunteers SET shirt_taken_date = ' . ($taken ? 'now()' : 'NULL') . ' WHERE id = :id',
	[':id' => $volunteerId]
);
if (!$affected) {
	echo json_encode(['success' => false, 'message' => 'Volunteer not found.']);
	exit;
}

_log("Volunteer $volunteerId shirt marked as " . ($taken ? 'taken' : 'not taken'));
$row = $this->database->query('SELECT shirt_taken_date FROM volunteers WHERE id = :id', [':id' => $volunteerId]);
$date = $row[0]->shirt_taken_date ?? null;
echo json_encode([
	'success' => true,
	'taken' => $taken,
	'date' => $date ? date('Y-m-d H:i', strtotime($date)) : '',
]);
exit;
