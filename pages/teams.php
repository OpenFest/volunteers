<?php
$activeConf = 'of-2025';
$teams = $this->database->query("SELECT * FROM teams WHERE conference = :conference", ['conference' => $activeConf]);
?>
<h1>Екипи от доброволци</h1>

<div class="teams-list">
<?php foreach ($teams as $team) { ?>
    <div class="team-item">
        <h2><?= htmlspecialchars($team->name) ?></h2>
        <p><?= htmlspecialchars($team->description) ?></p>
    </div>
<?php
}
?>
</div>
