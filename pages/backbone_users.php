<?php

checkAdmin();

$newUsers = $this->database->query(
	'SELECT * FROM users WHERE username is NULL ORDER BY name ASC'
);
$existingUsers = $this->database->query(
    'SELECT * FROM users WHERE username is NOT NULL ORDER BY name ASC'
);

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>New Users</h1>
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
				<?php foreach ($newUsers as $user):
				    $tokenIcon = '';
				    if (!empty($user->token)) {
				        if(strtotime($user->token_expiry) < time()) {
				            //token red warning icon
                            $tokenIcon = '<span class="red tooltip"><span class="tooltiptext">Token Expired</span>⚠</span>';
                        } else {
                            //token icon yellow sand watch
                            $tokenIcon = '<span class="yellow tooltip"><span class="tooltiptext">Token Pending</span>⏳</span>';
                        }
                    }
				?>
					<tr>
                        <td><?php echo $tokenIcon; ?><a href="/backbone/profile?user=<?php echo $user->uid; ?>"><?php echo htmlspecialchars($user->username ?? 'n/a');?></a></td>
                        <td><?php echo htmlspecialchars($user->name); ?></td>
						<td><?php echo htmlspecialchars($user->email); ?></td>
						<td><?php echo htmlspecialchars($user->phone); ?></td>
                        <td><?php echo $user->admin ? '<span class="green">✔</span>' : '<span class="red">✘</span>';?></td>
                        <td><?php echo $user->active ? '<span class="green">✔</span>' : '<span class="red">✘</span> <a href="/backbone/user/activate?user=' . $user->uid .'" class="button green">Activate</a>';?></td>
					</tr>
				<?php endforeach; ?>
		</table>
	</div>

	<div class="page-title">
        <h1>Existing Users</h1>
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
                <?php foreach ($existingUsers as $user): ?>
                    <tr>
                        <td><a href="/backbone/profile?user=<?php echo $user->uid; ?>"><?php echo htmlspecialchars($user->username ?? 'n/a'); ?></a></td>
                        <td><?php echo htmlspecialchars($user->name); ?></td>
                        <td><?php echo htmlspecialchars($user->email); ?></td>
                        <td><?php echo htmlspecialchars($user->phone); ?></td>
                        <td><?php echo $user->admin ? '<span class="green">✔</span>' : '<span class="red">✘</span>';?></td>
                        <td><?php echo $user->active ? '<span class="green">✔</span>' : '<span class="red">✘</span> <a href="/backbone/user/activate?user=' . $user->uid .'" class="button green">Activate</a>';?></td>
                    </tr>
                <?php endforeach; ?>
        </table>
    </div>
</div>

