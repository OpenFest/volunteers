<?php

checkAdmin();

// check for valid conference
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
// check for valid team
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


//get team members
$volunteers = $this->database->query(
        'SELECT * FROM volunteer_teams vt left join volunteers v on vt.volunteer = v.id 
        WHERE vt.team = :team AND vt.conference = :conference ORDER BY v.name ASC',
        [':team' => $team->slug, ':conference' => $conference->slug]
);

?>

<div class="backbone-page">
	<div class="page-title">
		<h1>Team <?php echo htmlspecialchars($team->name);?> [<?php echo htmlspecialchars($conference->title) ?>]</h1>
        <h5><?php echo htmlspecialchars($team->description); ?></h5>
	</div>

	<div class="pane full-width">
        <table>
            <thead>
            <tr>
                <th>Mugshot</th>
                <th>Name</th>
                <th>Previous Experience</th>
                <th>Notes</th>
                <th>Reg. Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($volunteers as $volunteer): ?>
                <tr>
                    <td>
                        <?php if ($volunteer->mugshot): ?>
                            <img src="<?php echo htmlspecialchars('/assets/uploads/volunteers/' .$volunteer->mugshot); ?>" alt="Mugshot" class="vol-mugshot" />
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($volunteer->name ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->previous_experience ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->notes ?? 'N/A'); ?></td>
                    <td><?php echo date('Y-m-d H:i:s',strtotime($volunteer->registration_date)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
	</div>
