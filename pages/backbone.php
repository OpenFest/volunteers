<?php
//check if user is loogged in and has admin rights

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$last10volunteers = $this->database->query(
	'SELECT v.name, u.email, v.registration_date FROM volunteers v left join users u on v."user" = u.uid ORDER BY v.registration_date DESC LIMIT 10'
);

// volunteers stats: shirt size, shirt cut, food preferences, lang, team
$volunteersStats = $this->database->query(
	'SELECT tshirt_size, tshirt_cut, food_preferences, lang, COUNT(*) as count FROM volunteers v left join users u on v."user" = u.uid GROUP BY tshirt_size, tshirt_cut, food_preferences, lang'
);

// aggregate the data and flatten the array
$stats = [
	'tshirt' => [
		'female' => [
			's' => 0,
			'm' => 0,
			'l' => 0,
			'xl' => 0,
			'xxl' => 0,
			'xxxl' => 0,
		],
		'unisex' => [
			's' => 0,
			'm' => 0,
			'l' => 0,
			'xl' => 0,
			'xxl' => 0,
			'xxxl' => 0,
		],
	],
	'food_preferences' => [
		'none' => 0,
		'vegetarian' => 0,
		'vegan' => 0,
	],
	'lang' => [
		'bg' => 0,
		'en' => 0,
	]
];

foreach ($volunteersStats as $volunteersStat) {
	$stats['tshirt'][$volunteersStat->tshirt_cut][$volunteersStat->tshirt_size] += $volunteersStat->count;
	$stats['food_preferences'][$volunteersStat->food_preferences] += $volunteersStat->count;
	$stats['lang'][$volunteersStat->lang] += $volunteersStat->count;
}

$voluteersTeams = $this->database->query(
	'SELECT team, COUNT(*) as count FROM volunteer_teams GROUP BY team order by count(*) DESC'
);

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>Backbone</h1>
    </div>

    <div class="pane">
        <div class="pane-header">Last 10 Volunteers</div>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Reg. Date</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($last10volunteers as $volunteer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($volunteer->name); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->email); ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($volunteer->registration_date)); ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="pane">
        <div class="pane-header">Volunteers Teams</div>
        <table>
            <thead>
            <tr>
                <th>Team</th>
                <th>Count</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($voluteersTeams as $team): ?>
                <tr>
                    <td><?php echo htmlspecialchars($team->team); ?></td>
                    <td><?php echo htmlspecialchars($team->count); ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="pane">
        <div class="pane-header">Volunteers Stats</div>
        <table>
            <thead>
            <tr>
                <th>T-Shirt (Female)</th>
                <th>T-Shirt (Unisex)</th>
                <th>Food Preferences</th>
                <th>Language</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
	                <?php foreach ($stats['tshirt']['female'] as $size => $count): ?>

		                <?php echo htmlspecialchars(strtoupper($size)) . ': ' . htmlspecialchars($count) . '<br>'; ?>
	                <?php endforeach; ?>
                </td>
                <td>
		            <?php foreach ($stats['tshirt']['unisex'] as $size => $count): ?>

			            <?php echo htmlspecialchars(strtoupper($size)) . ': ' . htmlspecialchars($count) . '<br>'; ?>
		            <?php endforeach; ?>
                </td>
                <td>
					<?php foreach ($stats['food_preferences'] as $preference => $count): ?>
						<?php echo htmlspecialchars(ucfirst($preference)) . ': ' . htmlspecialchars($count) . '<br>'; ?>
					<?php endforeach; ?>
                </td>
                <td>
					<?php foreach ($stats['lang'] as $lang => $count): ?>
						<?php echo htmlspecialchars(strtoupper($lang)) . ': ' . htmlspecialchars($count) . '<br>'; ?>
					<?php endforeach; ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
