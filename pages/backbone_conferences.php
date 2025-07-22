<?php

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

$conferences = $this->database->query(
	'SELECT * FROM conferences ORDER BY start_date DESC'
);

?>
<div class="backbone-page">
    <div class="page-title">
        <h1>Conferences</h1>
    </div>

	<div class="pane full-width">
        <div class="add-new">
            <button type="button" onclick="window.location.href='/backbone/conferences/new'" class="btn">
                Add New Conference
            </button>
        </div>
		<table>
			<thead>
				<tr>
                    <th>Slug</th>
					<th>Title</th>
					<th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Location</th>
                    <th>Reg Open</th>
                    <th>Reg Close</th>
				</tr>
			</thead>
			<tbody>
                <?php foreach ($conferences as $conference): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($conference->slug); ?></td>
                        <td><?php echo htmlspecialchars($conference->title); ?></td>
                        <td><?php echo htmlspecialchars($conference->description); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($conference->start_date)); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($conference->end_date)); ?></td>
                        <td><?php echo htmlspecialchars($conference->location); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($conference->registration_open)); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($conference->registration_close)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
		</table>
	</div>
</div>
