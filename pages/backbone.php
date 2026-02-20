<?php
//check if user is logged in and has admin rights

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

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

$last10volunteers = $this->database->query(
	'SELECT v.name, u.email, v.registration_date, vt.conference, v.status 
	FROM volunteers v LEFT JOIN users u on v."user" = u.uid LEFT JOIN volunteer_teams vt ON v.id = vt.volunteer 
	WHERE vt.conference = COALESCE(:conference, vt.conference)
	GROUP BY v.name, u.email, v.registration_date, vt.conference, v.status
	ORDER BY v.registration_date DESC LIMIT 10',
	[':conference' => $conference ? $conference->slug : null]
);

// volunteers stats: shirt size, shirt cut, food preferences, lang, team
$volunteersStats = $this->database->query(
	'SELECT tshirt_size, tshirt_cut, food_preferences, lang, count(*) AS count 
	FROM (
	    SELECT v.id, tshirt_size, tshirt_cut, food_preferences, previous_experience, lang 
	    FROM volunteers v LEFT JOIN users u ON v."user" = u.uid LEFT JOIN volunteer_teams vt ON v.id = vt.volunteer 
	    WHERE vt.conference = COALESCE(:conference, vt.conference) 
	    GROUP BY v.id, tshirt_size, tshirt_cut, food_preferences, previous_experience
	) AS a 
	GROUP BY a.tshirt_size, a.tshirt_cut, a.food_preferences, a.lang',
    [':conference' => $conference ? $conference->slug : null]
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

$volunteersTeams = $this->database->query(
	'SELECT t.conference, vt.team, COUNT(*) as count FROM volunteer_teams vt LEFT JOIN teams t ON (vt.team = t.slug AND vt.conference = t.conference) 
	WHERE  t.conference = COALESCE(:conference, t.conference)
	GROUP BY t.conference, vt.team order by count(*) DESC',
	[':conference' => $conference ? $conference->slug : null]
);

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>Backbone</h1>
        <h3>
            <label for="conference-select">Conference: </label><select name="conference" id="conference-select" onchange="window.location.href='/backbone?conf=' + this.value">
                <option value="all">All Conferences</option>
                <?php foreach ($allConferences as $conf): ?>
                    <option value="<?php echo htmlspecialchars($conf->slug); ?>" <?php echo ($conference && $conference->slug === $conf->slug) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($conf->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </h3>
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
			<?php foreach ($last10volunteers as $volunteer):
			    $status = match ($volunteer->status) {
			         'accepted' => '<span class="green tooltip" >✔<span class="tooltiptext">Accepted</span></span>',
			         'denied' => '<span class="red tooltip">✘<span class="tooltiptext">Denied</span></span>',
                     'pending' => '<span class="yellow tooltip">⏳<span class="tooltiptext">Pending</span></span>',
			    }
			?>
                <tr>
                    <td><?php echo $status . ' ' .htmlspecialchars($volunteer->name);  ?></td>
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
                <th>Conference</th>
                <th>Team</th>
                <th>Count</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($volunteersTeams as $team): ?>
                <tr>
                    <td><?php echo htmlspecialchars($team->conference); ?></td>
                    <td><a href="/backbone/team?c=<?php echo $team->conference;?>&t=<?php echo $team->team;?>"><?php echo htmlspecialchars($team->team); ?></a></td>
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
