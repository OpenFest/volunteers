<?php
//check if user is logged in and has admin rights

checkAdmin();

$rawInput = json_decode(file_get_contents('php://input'), true);
header('Content-Type: application/json');
$volunteerId = $_REQUEST['volunteer'] ?? $rawInput['volunteer'] ?? null;
$team = $_REQUEST['team'] ?? $rawInput['team'] ?? null;
if (!$volunteerId || !$team) {
    echo json_encode(['success' => false, 'message' => 'Невалиден доброволец или екип.']);
    exit;
}

$volunteerTeam = $this->database->query(
    'SELECT * FROM volunteer_teams WHERE volunteer = :volunteer AND team = :team',
    [':volunteer' => $volunteerId, ':team' => $team]
);

if (!$volunteerTeam) {
    echo json_encode(['success' => false, 'message' => 'Доброволецът не е част от този екип.']);
    exit;
}

//get other teams from this conference for this volunteer and set them to is_primary = false
$conference = $volunteerTeam[0]->conference;
$this->database->query(
	'UPDATE volunteer_teams SET is_primary = FALSE WHERE volunteer = :volunteer AND conference = :conference',
	[':volunteer' => $volunteerId, ':conference' => $conference]
);

$this->database->query(
    'UPDATE volunteer_teams SET is_primary = TRUE WHERE volunteer = :volunteer AND team = :team',
    [':volunteer' => $volunteerId, ':team' => $team]
);
_log("Set primary team for volunteer $volunteerId to '$team'");

echo json_encode(['success' => true, 'message' => 'Основният екип е зададен успешно.']);
exit;
