<?php

checkAdmin();

//get raw data from POST request
$rawData = json_decode(file_get_contents('php://input'), true);

//check valid conference
$conference = $_REQUEST['c'] ?? $rawData['c'] ?? null;
if (!$conference) {
    echo json_encode([
        'success' => false,
        'message' => 'Невалидна конференция.'
    ]);
    exit();
}

$conference = $this->database->query(
	'SELECT * FROM conferences WHERE slug = :slug',
	[':slug' => $conference]
);
$conference = array_shift($conference);
if (!$conference) {
    echo json_encode([
        'success' => false,
        'message' => 'Невалидна конференция!'
    ]);
    exit();
}

//check valid team
$team = $_REQUEST['t'] ?? $rawData['t'] ?? null;
if (!$team) {
    echo json_encode([
        'success' => false,
        'message' => 'Невалиден екип.'
    ]);
    exit();
}

$team = $this->database->query(
	'SELECT * FROM teams WHERE conference = :conference AND slug = :slug',
	[':conference' => $conference->slug, ':slug' => $team]
);
$team = array_shift($team);
if (!$team) {
    echo json_encode([
        'success' => false,
        'message' => 'Невалиден екип!'
    ]);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$volunteerId = $_POST['volunteer'] ?? $rawData['volunteer'] ?? null;
	if (!$volunteerId) {
		echo json_encode([
            'success' => false,
            'message' => 'Моля, изберете доброволец.'
        ]);
		exit;
	}
	
	//check if volunteer is already in the team
	$existing = $this->database->query(
		'SELECT * FROM volunteer_teams WHERE volunteer = :volunteer AND team = :team AND conference = :conference',
		[':volunteer' => $volunteerId, ':team' => $team->slug, ':conference' => $conference->slug]
	);
	if ($existing) {
		echo json_encode([
            'success' => false,
            'message' => 'Доброволецът вече е част от този екип.'
        ]);
		exit();
	}
	
	//add volunteer to team
	$this->database->query(
		'INSERT INTO volunteer_teams (volunteer, team, conference) VALUES (:volunteer, :team, :conference)',
		[':volunteer' => $volunteerId, ':team' => $team->slug, ':conference' => $conference->slug]
	);

	//get volunteer data
	$volunteer = $this->database->query(
		'SELECT * FROM volunteers WHERE id = :id',
		[':id' => $volunteerId]
	);
	$volunteer = array_shift($volunteer);
	_log("Added volunteer {$volunteer->name} (ID: {$volunteer->id}) to team {$team->name} (Slug: {$team->slug}) in conference {$conference->title} (Slug: {$conference->slug})");
	
	//get user data
	$user = User::load($volunteer->user);
	if ($user->isActive()) {
		try {
			$user->addToLdapGroups($conference->slug);
			_log('Added volunteer to LDAP groups for conference: ' . $conference->slug);
		} catch (Exception $e) {
			_log('Failed to add volunteer to LDAP groups for conference: ' . $conference->slug . ': ' . $e->getMessage(), LOG_ERR);
		}
	}
    header('Content-Type: application/json');
    echo json_encode([
            'success' => true,
            'message' => 'Volunteer added to team successfully.'
    ]);
    exit();
}