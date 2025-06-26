<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$teams = $this->database->query(
	'SELECT * FROM teams ORDER BY name'
);
?>
<div class="backbone-page">
	<div class="page-title">
		<h1>Teams</h1>
	</div>

	<div class="pane full-width">
		<table>
			<thead>
				<tr>
					<th>Conference</th>
					<th>Slug</th>
					<th>Name</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($teams as $team): ?>
					<tr>
						<td><?php echo htmlspecialchars($team->conference); ?></td>
						<td><?php echo htmlspecialchars($team->slug); ?></td>
						<td><?php echo htmlspecialchars($team->name); ?></td>
						<td><?php echo htmlspecialchars($team->description); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
