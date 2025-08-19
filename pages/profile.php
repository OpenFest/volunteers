<?php
//check if user is loogged in and has admin rights

if (!isset($_SESSION['user'])) {
	header('Location: /login');
	exit;
}

$user = $_SESSION['user'];

$volunteers = $this->database->query(
    'SELECT v.*,  c.title, json_agg(t.name) as teams FROM volunteers v 
    LEFT JOIN volunteer_teams vt ON v.id=vt.volunteer
    LEFT JOIN teams t ON vt.team = t.slug 
    LEFT JOIN conferences c ON vt.conference = c.slug
    WHERE v.user = :uid group by v.id, vt.conference, v.registration_date, c.title ORDER BY v.registration_date',
    [':uid' => $user->getId()]
);

?>

<div class="profile-page">
    <div class="page-title">
        <h1>Профил</h1>
    </div>

    <div class="pane full-width">
        <div class="pane-header">
            <h2>Здравей, <?php echo htmlspecialchars($user->getName()); ?>!</h2>
            <p><strong>E-мейл:</strong> <?php echo htmlspecialchars($user->getEmail()); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user->getPhone() ?? 'N/A'); ?></p>

	        <?php if ($user->isAdmin()): ?>
                <p><strong>Role:</strong> Admin</p>
                <a href="/backbone" class="button">Go to Admin Dashboard</a>
	        <?php endif; ?>
        </div>

    </div>


    <div class="page-title">
        <h2>Твоите доброволчески регистрации</h2>
        <p>Тук можеш да видиш информация за твоите регистрации като доброволeц.</p>
    </div>
    <?php foreach ($volunteers as $volunteer): ?>
    <div class="pane">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($volunteer->title);?></h2>
        </div>
        <div class="profile-header">
            <?php if($volunteer->mugshot): ?>
                <img class="profile-image" src="/assets/uploads/volunteers/<?php echo htmlspecialchars($volunteer->mugshot); ?>" alt="Profile Picture" class="profile-picture">
            <?php else: ?>
                <img src="/assets/img/default-profile.png" alt="Default Profile Picture" class="profile-picture">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($volunteer->name)?></h3>

        </div>
        <div class="profile-info">
            <p><strong>Дата на регистрация:</strong> <?php echo htmlspecialchars(date('d.m.Y', strtotime($volunteer->registration_date))); ?></p>
            <p><strong>Размер на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size)); ?></p>
            <p><strong>Кройка на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_cut)); ?></p>
            <p><strong>Храна:</strong> <?php echo ucfirst(htmlspecialchars($volunteer->food_preferences)); ?></p>
            <p><strong>Език:</strong> <?php echo htmlspecialchars($volunteer->lang); ?></p>
            <p><strong>Екип/и/:</strong>
		        <?php if (!empty($volunteer->teams)): ?>
			        <?php foreach (json_decode($volunteer->teams) as $team): ?>
                        <span class="team-badge"><?php echo htmlspecialchars($team); ?></span>
			        <?php endforeach; ?>
		        <?php else: ?>
                    <span class="team-badge">N/A</span>
		        <?php endif; ?>
            </p>
            <p><strong>Предишен опит:</strong> <?php echo htmlspecialchars($volunteer->previous_experience ? 'Да' : 'Не'); ?></p>
            <p><strong>Бележки:</strong> <?php echo htmlspecialchars($volunteer->notes ?? 'N/A'); ?></p>
        </div>
    </div>

    <?php endforeach;?>
</div>

