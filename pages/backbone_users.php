<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$users = $this->database->query(
	'SELECT * FROM users ORDER BY name ASC'
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
                    <th>Username</th>
                    <th>Name</th>
					<th>Email</th>
                    <th>Phone</th>
                    <th>Admin</th>
                    <th>Active</th>

				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $user): ?>
					<tr>
                        <td><a href="/backbone/profile?user=<?php echo $user->uid; ?>"><?php echo htmlspecialchars($user->username); ?></a></td>
                        <td><?php echo htmlspecialchars($user->name); ?></td>
						<td><?php echo htmlspecialchars($user->email); ?></td>
						<td><?php echo htmlspecialchars($user->phone); ?></td>
                        <td><?php echo $user->admin ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $user->active ? 'Yes' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
		</table>
	</div>
</div>
