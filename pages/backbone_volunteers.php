<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$volunteers = $this->database->query(
	'SELECT * FROM volunteers v left join users u on v.user = u.uid ORDER BY user'
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
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>T-shirt Cut</th>
                    <th>T-shirt Size</th>
                    <th>Food Preferences</th>
					<th>mugshot</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($volunteers as $volunteer): ?>
					<tr>
                        <td><?php echo htmlspecialchars($volunteer->name ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->phone ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->email ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->tshirt_cut ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->tshirt_size ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->food_preferences ?? 'N/A'); ?></td>
						<td>
							<?php if ($volunteer->mugshot): ?>
								<img src="<?php echo htmlspecialchars('/assets/uploads/volunteers/' .$volunteer->mugshot); ?>" alt="Mugshot" style="width: 50px; height: 50px;">
							<?php else: ?>
								 N/A
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

