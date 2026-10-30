<?php

checkAdmin();

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

// only accepted volunteers get shirts; one row per volunteer even with several teams
$volunteers = $this->database->query(
	"SELECT v.id, v.tshirt_cut, v.tshirt_size, v.shirt_taken_date,
	v.name, u.name AS user_name, u.email, v.registration_date,
	string_agg(DISTINCT vt.conference, ', ') AS conferences,
	string_agg(DISTINCT t.name, ', ') AS teams
	FROM volunteers v
	LEFT JOIN users u ON v.user = u.uid
	JOIN volunteer_teams vt ON v.id = vt.volunteer
	LEFT JOIN teams t ON vt.team = t.slug AND vt.conference = t.conference
	WHERE v.status = 'accepted' AND vt.conference = COALESCE(:conference, vt.conference)
	GROUP BY v.id, v.tshirt_cut, v.tshirt_size, v.shirt_taken_date, u.name, v.name, u.email, v.registration_date
	ORDER BY v.name, v.registration_date",
	[':conference' => $conference ? $conference->slug : null]
);

$summary = [];
$taken = 0;
foreach ($volunteers as $volunteer) {
	$key = $volunteer->tshirt_cut . '|' . $volunteer->tshirt_size;
	$summary[$key] ??= ['cut' => $volunteer->tshirt_cut, 'size' => $volunteer->tshirt_size, 'total' => 0, 'taken' => 0];
	$summary[$key]['total']++;
	if ($volunteer->shirt_taken_date) {
		$summary[$key]['taken']++;
		$taken++;
	}
}
ksort($summary);
?>

<div class="backbone-page">
	<div class="page-title">
		<h1>Shirts</h1>
		<h3>
			<label for="conference-select">Conference: </label><select name="conference" id="conference-select" onchange="window.location.href='/backbone/shirts?conf=' + this.value">
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
		<div class="page-title">
			<h2>Overview: <span id="shirts-taken"><?php echo $taken; ?></span> / <?php echo count($volunteers); ?> taken</h2>
		</div>
		<table class="compact">
			<thead>
				<tr><th>Cut</th><th>Size</th><th>Taken</th><th>Remaining</th><th>Total</th></tr>
			</thead>
			<tbody>
				<?php foreach ($summary as $key => $row): ?>
					<tr class="summary-row" data-summary="<?php echo htmlspecialchars($key); ?>">
						<td><?php echo ucwords(htmlspecialchars($row['cut'])); ?></td>
						<td><?php echo strtoupper(htmlspecialchars($row['size'])); ?></td>
						<td class="summary-taken"><?php echo $row['taken']; ?></td>
						<td class="summary-remaining"><?php echo $row['total'] - $row['taken']; ?></td>
						<td><?php echo $row['total']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="page-title">
			<h2>Volunteers</h2>
		</div>
		<div class="input">
			<input type="search" id="shirt-search" placeholder="Search by name, email or team" autofocus>
			<label><input type="checkbox" id="shirt-hide-taken"> Hide those who took their shirt</label>
		</div>
		<table class="compact" id="shirts-table">
			<thead>
				<tr><th>Name</th><th>Conference</th><th>Reg. Date</th><th>Teams</th><th>Cut</th><th>Size</th><th>Shirt taken</th></tr>
			</thead>
			<tbody>
				<?php foreach ($volunteers as $volunteer): ?>
					<tr data-search="<?php echo htmlspecialchars(mb_strtolower($volunteer->name . ' ' . $volunteer->user_name . ' ' . $volunteer->email . ' ' . $volunteer->teams)); ?>"
						data-summary="<?php echo htmlspecialchars($volunteer->tshirt_cut . '|' . $volunteer->tshirt_size); ?>"
						data-taken="<?php echo $volunteer->shirt_taken_date ? '1' : '0'; ?>">
						<td><?php echo htmlspecialchars($volunteer->name); ?><?php if ($volunteer->user_name && $volunteer->user_name !== $volunteer->name): ?> (<?php echo htmlspecialchars($volunteer->user_name); ?>)<?php endif; ?></td>
						<td><?php echo htmlspecialchars($volunteer->conferences ?? ''); ?></td>
						<td><?php echo date('Y-m-d', strtotime($volunteer->registration_date)); ?></td>
						<td><?php echo htmlspecialchars($volunteer->teams ?? ''); ?></td>
						<td><?php echo ucwords(htmlspecialchars($volunteer->tshirt_cut)); ?></td>
						<td><?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size)); ?></td>
						<td>
							<label>
								<input type="checkbox" class="shirt-toggle" data-volunteer-id="<?php echo (int)$volunteer->id; ?>" <?php echo $volunteer->shirt_taken_date ? 'checked' : ''; ?>>
								<span class="shirt-date"><?php echo $volunteer->shirt_taken_date ? date('Y-m-d H:i', strtotime($volunteer->shirt_taken_date)) : ''; ?></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<script src="/assets/js/shirts.js"></script>
