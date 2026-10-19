<?php

checkAuth();
$isAdmin = $_SESSION['user']->isAdmin();

// non-admins reach this page only as leaders of the requested team (checked below)
if (!$isAdmin) {
    $redirectTo = '/profile';
} else {
    $redirectTo = '/backbone';
}

// check for valid conference
$conference = $_GET['c'] ?? null;
if (!$conference) {
    header('Location: ' . $redirectTo);
    exit;
}
$conference = $this->database->query(
    'SELECT * FROM conferences WHERE slug = :slug',
    [':slug' => $conference]
);
$conference = array_shift($conference);
if (!$conference) {
    header('Location: ' . $redirectTo);
    exit;
}
// check for valid team
$team = $_GET['t'] ?? null;
if (!$team) {
    header('Location: ' . $redirectTo);
    exit;
}

$team = $this->database->query(
    'SELECT * FROM teams WHERE conference = :conference AND slug = :slug',
    [':conference' => $conference->slug, ':slug' => $team]
);
$team = array_shift($team);
if (!$team) {
    header('Location: ' . $redirectTo);
    exit;
}

if (!$isAdmin) {
    $isLeader = $this->database->query(
        'SELECT 1 FROM volunteer_teams vt JOIN volunteers v ON vt.volunteer = v.id
        WHERE vt.team = :team AND vt.conference = :conference AND vt.is_leader = TRUE AND v."user" = :uid',
        [':team' => $team->slug, ':conference' => $conference->slug, ':uid' => $_SESSION['user']->getId()]
    );
    if (!$isLeader) {
        header('Location: /profile');
        exit;
    }
}

// set team leader
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leader'])) {
    $leaderId = (int)$_POST['leader'];
    $this->database->query(
        'UPDATE volunteer_teams SET is_leader = FALSE WHERE team = :team AND conference = :conference',
        [':team' => $team->slug, ':conference' => $conference->slug]
    );
    if ($leaderId) {
        // only volunteers with this team as primary can lead
        $this->database->query(
            'UPDATE volunteer_teams SET is_leader = TRUE
            WHERE volunteer = :volunteer AND team = :team AND conference = :conference AND is_primary = TRUE
            AND volunteer NOT IN (SELECT id FROM volunteers WHERE status = \'denied\')',
            [':volunteer' => $leaderId, ':team' => $team->slug, ':conference' => $conference->slug]
        );
    }
    _log("Set leader of team '{$team->slug}' to volunteer $leaderId");
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// set team limits
// admins and (already verified) team leaders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['min_primary'], $_POST['min_total'])) {
    $this->database->query(
        'UPDATE teams SET min_primary = :min_primary, min_total = :min_total WHERE conference = :conference AND slug = :slug',
        [
            ':min_primary' => max(1, (int)$_POST['min_primary']),
            ':min_total' => max(1, (int)$_POST['min_total']),
            ':conference' => $conference->slug,
            ':slug' => $team->slug,
        ]
    );
    _log("Updated limits of team '{$team->slug}' in conference '{$conference->slug}'");
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

//get team members
$volunteers = $this->database->query(
        'SELECT vt.*, v.*, u.email, u.phone FROM volunteer_teams vt left join volunteers v on vt.volunteer = v.id
        left join users u on v."user" = u.uid
        WHERE vt.team = :team AND vt.conference = :conference ORDER BY vt.is_leader DESC, vt.is_primary DESC, v.name ASC',
        [':team' => $team->slug, ':conference' => $conference->slug]
);

$deniedVolunteers = array_filter($volunteers, fn($v) => $v->status === 'denied');
$volunteers = array_filter($volunteers, fn($v) => $v->status !== 'denied');
$leader = null;
foreach ($volunteers as $v) {
    if ($v->is_leader) {
        $leader = $v;
    }
}

?>

<div class="backbone-page">
	<div class="page-title">
		<h1>Team <?php echo htmlspecialchars($team->name);?> [<?php echo htmlspecialchars($conference->title) ?>]</h1>
        <h5><?php echo htmlspecialchars($team->description); ?></h5>
	</div>

	<div class="pane full-width">
        <?php
        $acceptedTotal = count(array_filter($volunteers, fn($v) => $v->status === 'accepted'));
        $acceptedPrimary = count(array_filter($volunteers, fn($v) => $v->status === 'accepted' && $v->is_primary));
        ?>
        <p>
            <strong>Primary:</strong> <span class="<?php echo quotaClass($acceptedPrimary, $team->min_primary); ?>"><?php echo $acceptedPrimary . ' / ' . (int)$team->min_primary; ?></span>
            &nbsp; <strong>Total:</strong> <span class="<?php echo quotaClass($acceptedTotal, $team->min_total); ?>"><?php echo $acceptedTotal . ' / ' . (int)$team->min_total; ?></span>
            <span class="small">(accepted / minimum)</span>
        </p>
        <?php if (!$isAdmin): ?>
            <p><strong>Team leader:</strong> <?php echo $leader ? htmlspecialchars($leader->name) : 'none'; ?></p>
        <?php else: ?>
        <form method="post" class="team-leader-form">
            <strong>Team leader:</strong>
            <?php echo $leader ? htmlspecialchars($leader->name) : 'none'; ?>
            <select name="leader">
                <option value="0">— none —</option>
                <?php foreach ($volunteers as $v): if (!$v->is_primary || $v->status === 'denied') continue; ?>
                    <option value="<?php echo (int)$v->volunteer; ?>"<?php echo $v->is_leader ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars($v->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Set leader</button>
        </form>
        <?php endif; ?>
        <form method="post" class="team-limits-form">
            <label for="min_primary">Min primary:</label>
            <input type="number" id="min_primary" name="min_primary" min="1" value="<?php echo (int)$team->min_primary; ?>">
            <label for="min_total">Min total:</label>
            <input type="number" id="min_total" name="min_total" min="1" value="<?php echo (int)$team->min_total; ?>">
            <button type="submit" class="btn">Set limits</button>
        </form>
        <?php foreach (['' => $volunteers, 'Denied' => $deniedVolunteers] as $sectionTitle => $sectionVolunteers):
            if ($sectionTitle && !$sectionVolunteers) continue; ?>
        <?php if ($sectionTitle): ?><h2><?php echo $sectionTitle; ?></h2><?php endif; ?>
        <table>
            <thead>
            <tr>
                <th>Mugshot</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Previous Experience</th>
                <th>Notes</th>
                <th>Reg. Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sectionVolunteers as $volunteer):
                $state = $volunteer->status === 'accepted' ?
                    '<span class="green">✔</span>' :
                    ($volunteer->status === 'denied' ?
                        '<span class="red">✘</span>' : '<span class="yellow">⏳</span>');
                $primary = $volunteer->is_primary ? '<span class="green tooltip">★<span class="tooltiptext">Primary</span></span>' : '';
                $lead = $volunteer->is_leader ? ' <span class="yellow tooltip">♛<span class="tooltiptext">Team leader</span></span>' : '';
            ?>
                <tr>
                    <td>
                        <?php if ($volunteer->mugshot): ?>
                            <img src="<?php echo htmlspecialchars('/assets/uploads/volunteers/' .$volunteer->mugshot); ?>" alt="Mugshot" class="vol-mugshot zoomable" />
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo $state . ' ' .htmlspecialchars($volunteer->name ?? 'N/A') . ' ' . $primary . $lead; ?></td>
                    <td><?php echo htmlspecialchars($volunteer->phone ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->email ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->previous_experience ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($volunteer->notes ?? 'N/A'); ?></td>
                    <td><?php echo date('Y-m-d H:i:s',strtotime($volunteer->registration_date)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
	</div>
