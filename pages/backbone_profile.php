<?php
//check if user is logged in and has admin rights

checkAdmin();

$userID = $_GET['user'] ?? null;
if (!$userID) {
    header('Location: /backbone');
    exit;
}

$user = User::load($userID);
if (!$user) {
    header('Location: /backbone');
    exit;
}

$volunteers = $this->database->query(
    'SELECT v.*, vt.conference, c.title,jsonb_object_agg(t.slug, jsonb_build_object(\'name\', t.name, \'is_primary\', vt.is_primary)) as teams FROM volunteers v 
    LEFT JOIN volunteer_teams vt ON v.id=vt.volunteer
    LEFT JOIN teams t ON vt.team = t.slug 
    LEFT JOIN conferences c ON vt.conference = c.slug
    WHERE v.user = :uid group by v.id, vt.conference, v.registration_date, c.title ORDER BY v.registration_date DESC',
    [':uid' => $userID]
);

?>

<div class="backbone-page profile-page">
    <div class="page-title">
        <h1>Профил</h1>
    </div>

    <div class="pane full-width">
        <div class="pane-header">
            <h2> <?php echo htmlspecialchars($user->getName()); ?></h2>
            <h3> @<?php echo htmlspecialchars($user->getUsername() ?: 'n/a'); ?></h3>
            <p><strong>E-мейл:</strong> <?php echo htmlspecialchars($user->getEmail()); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user->getPhone() ?? 'N/A'); ?></p>
        </div>
    </div>
    <?php if (empty($user->getUsername())): ?>
        <div class="pane full-width">
            <div class="pane-header">
                <p class="bg-yellow">
                    Непълен профил! За достъп до активната комуникация (мейл, чат и пр.) се изисква завършване на профила. <br/>
                    <a href="/backbone/profile?remind-complete=<?php echo $user->getEmail()?>" class="button">re-send email for reminder</a>
                </p>
            </div>
        </div>
    <?php endif; ?>


    <div class="page-title">
        <h2>Доброволчески регистрации</h2>
    </div>

    <?php foreach ($volunteers as $volunteer): ?>
    <?php
    $bgClass = match ($volunteer->status) {
        'accepted' => 'bg-green',
        'denied' => 'bg-red',
        default => 'bg-yellow',
    };
    ?>
    <div class="pane">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($volunteer->title);?></h2>
        </div>
        <div class="profile-header">
            <?php if($volunteer->mugshot): ?>
                <img class="profile-image" src="/assets/uploads/volunteers/<?php echo htmlspecialchars($volunteer->mugshot); ?>" alt="Profile Picture">
            <?php else: ?>
                <img class="profile-image" src="/assets/img/default-profile.png" alt="Default Profile Picture">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($volunteer->name)?></h3>

        </div>
        <div class="profile-info">
            <p>
            <strong>Дата на регистрация:</strong> <?php echo htmlspecialchars(date('d.m.Y', strtotime($volunteer->registration_date))); ?>
                [
                <span class="team-badge <?php echo $bgClass;?>"><?php echo ucfirst(htmlspecialchars($volunteer->status)); ?></span>
                <?php if ($volunteer->status !== 'pending') {
                    echo ' @ ' . htmlspecialchars($volunteer->status_update_date ? date('d.m.Y', strtotime($volunteer->status_update_date)) : 'N/A');
                }?>
                ]
            </p>
            <p><strong>Размер на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size)); ?></p>
            <p><strong>Кройка на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_cut)); ?></p>
            <p><strong>Храна:</strong> <?php echo ucfirst(htmlspecialchars($volunteer->food_preferences)); ?></p>
            <p><strong>Език:</strong> <?php echo htmlspecialchars($volunteer->lang); ?></p>
            <p><strong>Екип/и/:</strong>
		        <?php if (!empty($volunteer->teams)): ?>
			        <?php foreach (json_decode($volunteer->teams) as $team => $teamData):
                         $isPrimary = $teamData->is_primary;
                         $teamName = $teamData->name;
			         ?>
                        <button
                        class="team-badge <?php echo ($isPrimary ? 'bg-green': 'btn set-primary-team-button');?>"
                        data-volunteer-id="<?php echo htmlspecialchars($volunteer->id); ?>"
                        data-team-id="<?php echo htmlspecialchars($team); ?>"
                        >
                        <?php echo htmlspecialchars($teamName); ?>
                        </button>
			        <?php endforeach; ?>
		        <?php else: ?>
                    <span class="team-badge">N/A</span>
		        <?php endif; ?>
                <button
                        data-volunteer="<?php echo htmlspecialchars($volunteer->id); ?>"
                        data-conference="<?php echo htmlspecialchars($volunteer->conference); ?>"
                        class="btn team-badge bg-lightblue assign-to-team-button">
                    +
                </button>
            </p>
            <p><strong>Предишен опит:</strong> <?php echo htmlspecialchars($volunteer->previous_experience ? 'Да' : 'Не'); ?></p>
            <p><strong>Бележки:</strong> <?php echo htmlspecialchars($volunteer->notes ?? 'N/A'); ?></p>
        </div>
    </div>

    <?php endforeach;?>
</div>
<script src="/assets/js/set-primary-team.js"></script>
<script src="/assets/js/team-add-volunteer.js"></script>

