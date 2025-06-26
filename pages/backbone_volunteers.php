<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$volunteers = $this->database->query(
	'SELECT * FROM volunteers ORDER BY user'
);

?>

<div class="backbone-page">
	<div class="page-title">
		<h1>Volunteers</h1>
	</div>

	<div class="pane full-width">
		<table>
			<thead>
				<tr>
					<th>Email</th>
					<th>User ID</th>
					<th>mugshot</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($volunteers as $volunteer): ?>
					<tr>
						<td><?php echo htmlspecialchars($volunteer->clarion_email ?? 'N/A'); ?></td>
						<td><?php echo htmlspecialchars($volunteer->user); ?></td>
						<td>
							<?php if ($volunteer->mugshot): ?>
								<img src="<?php echo htmlspecialchars($volunteer->mugshot); ?>" alt="Mugshot" style="width: 50px; height: 50px;">
							<?php else: ?>
								 N/A
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

