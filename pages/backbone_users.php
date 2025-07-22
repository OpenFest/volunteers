<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$last10volunteers = $this->database->query(
	'SELECT * FROM users ORDER BY uid DESC LIMIT 10'
);

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>Users</h1>
    </div>

	<div class="pane full-width">
		<table>
			<thead>
				<tr>
                    <th>Name</th>
					<th>Email</th>
					<th>Phone</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($last10volunteers as $volunteer): ?>
					<tr>
                        <td><?php echo htmlspecialchars($volunteer->name); ?></td>
						<td><?php echo htmlspecialchars($volunteer->email); ?></td>
						<td><?php echo htmlspecialchars($volunteer->phone); ?></td>
					</tr>
				<?php endforeach; ?>
		</table>
	</div>
</div>
