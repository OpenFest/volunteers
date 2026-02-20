<?php
//check if user is logged in and has admin rights

checkAdmin();

$volunteerId = $_REQUEST['volunteer'] ?? null;
$team = $_REQUEST['team'] ?? null;
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

$this->database->query(
    'UPDATE volunteer_teams SET is_primary = TRUE WHERE volunteer = :volunteer AND team = :team',
    [':volunteer' => $volunteerId, ':team' => $team]
);

echo json_encode(['success' => true, 'message' => 'Основният екип е зададен успешно.']);
exit;
