<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$volunteers = $this->database->query(
	"SELECT 
	*, 
	case when v.name <> u.name then concat(v.name, ' (', u.name, ')') else v.name end as name
	FROM 
	volunteers v left join users u on v.user = u.uid ORDER BY user"
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
                    <th>Mugshot</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>T-shirt Cut</th>
                    <th>T-shirt Size</th>
                    <th>Food Prefs</th>
                    <th>Previous Experience</th>
                    <th>Notes</th>
                    <th>Reg. Date</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($volunteers as $volunteer): ?>
					<tr>
                        <td>
							<?php if ($volunteer->mugshot): ?>
                                <img src="<?php echo htmlspecialchars('/assets/uploads/volunteers/' .$volunteer->mugshot); ?>" alt="Mugshot" class="vol-mugshot" />
							<?php else: ?>
                                N/A
							<?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($volunteer->name ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->phone ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->email ?? 'N/A'); ?></td>
                        <td><?php echo ucwords(htmlspecialchars($volunteer->tshirt_cut ?? 'N/A')); ?></td>
                        <td><?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size ?? 'N/A')); ?></td>
                        <td><?php echo ucwords(htmlspecialchars($volunteer->food_preferences ?? 'N/A')); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->previous_experience ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($volunteer->notes ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s',strtotime($volunteer->registration_date)); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

