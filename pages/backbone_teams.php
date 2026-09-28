<?php

checkAdmin();

//get conf from the url parmas
$allConferences = Conference::getConferences();
$activeConference = Conference::getActive();
$_conf = $_REQUEST['conf'] ?? '';

$conference = null;
if (empty($_conf)) {
    if ($activeConference) {
        $conference = $activeConference->toObject();
    }
} else {
    foreach ($allConferences as $conf) {
        if ($conf->slug === $_conf) {
            $conference = $conf;
            break;
        }
    }
}

$teams = $this->database->query(
	'SELECT t.*, m.name AS mail_list_name, m.value AS mail_list_value FROM teams t LEFT JOIN mail_lists m ON t.mail_list = m.id WHERE t.conference = COALESCE(:conference, t.conference) ORDER BY t.conference DESC',
	[':conference' => $conference ? $conference->slug : null]
);
?>
<div class="backbone-page">
	<div class="page-title">
        <h1>Teams</h1>
        <h3>
            <label for="conference-select">Conference: </label><select name="conference" id="conference-select" onchange="window.location.href='/backbone/teams?conf=' + this.value">
                <option value="all">All Conferences</option>
                <?php foreach ($allConferences as $conf): ?>
                    <option value="<?php echo htmlspecialchars($conf->slug); ?>" <?php echo ($conference && $conference->slug === $conf->slug) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($conf->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </h3>
	</div>

	<div class="pane full-width">
        <div class="add-new">
            <button type="button" onclick="window.location.href='/backbone/teams/new'" class="btn">
                Add New Team
            </button>
        </div>
		<table>
			<thead>
				<tr>
					<th>Conference</th>
					<th>Slug</th>
					<th>Name</th>
					<th>Description</th>
					<th>Mail list</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($teams as $team): ?>
					<tr>
						<td><?php echo htmlspecialchars($team->conference); ?></td>
						<td><a href="/backbone/team?c=<?php echo htmlspecialchars($team->conference); ?>&t=<?php echo htmlspecialchars($team->slug); ?>"><?php echo htmlspecialchars($team->slug); ?></a></td>
						<td><?php echo htmlspecialchars($team->name); ?></td>
						<td><?php echo htmlspecialchars($team->description); ?></td>
						<td><?php echo htmlspecialchars($team->mail_list_name ?? ''); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
