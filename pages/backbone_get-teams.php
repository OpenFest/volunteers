<?php

checkAdmin();

//check valid conference
$conference = $_GET['c'] ?? null;
if (!$conference) {
	header('Location: /backbone');
	exit;
}

$conference = $this->database->query(
	'SELECT * FROM conferences WHERE slug = :slug',
	[':slug' => $conference]
);
$conference = array_shift($conference);
if (!$conference) {
	header('Location: /backbone');
	exit;
}

//check valid volunteer
$volunteerId = $_GET['v'] ?? null;
if (!$volunteerId) {
	header('Location: /backbone');
	exit;
}
//check if volunteer exists
$volunteer = $this->database->query(
	'SELECT * FROM volunteers WHERE id = :id',
	[':id' => $volunteerId]
);
$volunteer = array_shift($volunteer);
if (!$volunteer) {
	header('Location: /backbone');
	exit;
}

//select the volunteer and the available teams for the given conference that the volunteer is still not part of
$teams = $this->database->query(
	'SELECT slug, name FROM teams WHERE conference = :conference AND slug NOT IN (SELECT team FROM volunteer_teams WHERE volunteer = :volunteer AND conference = :conference)',
	[':conference' => $conference->slug, ':volunteer' => $volunteerId]
);

//return json response for the available teams
header('Content-Type: application/json');
echo json_encode([
	'success' => true,
	'teams' => $teams
]);
exit();
