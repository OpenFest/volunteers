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

//check valid team
$team = $_GET['t'] ?? null;
if (!$team) {
	header('Location: /backbone');
	exit;
}

$team = $this->database->query(
	'SELECT * FROM teams WHERE conference = :conference AND slug = :slug',
	[':conference' => $conference->slug, ':slug' => $team]
);
$team = array_shift($team);
if (!$team) {
	header('Location: /backbone');
	exit;
}

//get all volunteers not in this team
$volunteers = $this->database->query(
	'SELECT * FROM volunteers v WHERE v.id NOT IN (SELECT volunteer FROM volunteer_teams WHERE team = :team AND conference = :conference) ORDER BY v.name ASC',
	[':team' => $team->slug, ':conference' => $conference->slug]
);

if (empty($volunteers)) {
	echo "<h3 class='login-error'>Няма налични доброволци за добавяне към този екип.</h3>";
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$volunteerId = $_POST['volunteer'] ?? NULL;
	if (!$volunteerId) {
		echo "<h3 class='login-error'>Моля, изберете доброволец.</h3>";
		exit;
	}
	
	//check if volunteer is already in the team
	$existing = $this->database->query(
		'SELECT * FROM volunteer_teams WHERE volunteer = :volunteer AND team = :team AND conference = :conference',
		[':volunteer' => $volunteerId, ':team' => $team->slug, ':conference' => $conference->slug]
	);
	if ($existing) {
		echo "<h3 class='login-error'>Доброволецът вече е част от този екип.</h3>";
		exit;
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
	header('Location: /backbone/team?c=' . urlencode($conference->slug) . '&t=' . urlencode($team->slug));
	exit;
}
?>
<div class="backbone-page">
	<div class="page-title">
		<h1>Add Volunteer to Team <?php echo htmlspecialchars($team->name);?> [<?php echo htmlspecialchars($conference->title) ?>]</h1>
	</div>
	<div class="pane full-width">
		<form method="post" id="add-volunteer-form" >
			<input type="hidden" name="team" value="<?php echo htmlspecialchars($team->slug); ?>">
			<input type="hidden" name="conference" value="<?php echo htmlspecialchars($conference->slug); ?>">
			<div class="input">
				<label for="volunteer">Volunteer:</label>
				<select name="volunteer" id="volunteer" required>
					<option value="">Select a volunteer</option>
					<?php foreach ($volunteers as $volunteer): ?>
						<option value="<?php echo htmlspecialchars($volunteer->id); ?>"><?php echo htmlspecialchars($volunteer->name); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="input">
				<button type="submit" class="btn">Add Volunteer</button>
			</div>
		</form>
	</div>
</div>