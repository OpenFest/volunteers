<?php
//check if user is logged in and has admin rights

checkAuth();

$user = $_SESSION['user'];

$volunteers = $this->database->query(
    'SELECT v.*, vt.conference, c.title, json_object_agg(t.name, vt.is_primary) as teams FROM volunteers v 
    LEFT JOIN volunteer_teams vt ON v.id=vt.volunteer
    LEFT JOIN teams t ON vt.team = t.slug 
    LEFT JOIN conferences c ON vt.conference = c.slug
    WHERE v.user = :uid group by v.id, vt.conference, v.registration_date, c.title ORDER BY v.registration_date DESC',
    [':uid' => $user->getId()]
);

$activeConference = Conference::getActive();

$registeredForActiveConf = false;
if ($activeConference) {
    foreach ($volunteers as $volunteer) {
        if ($volunteer->conference === $activeConference->getSlug()) {
            $registeredForActiveConf = true;
            break;
        }
    }
}

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['profile_csrf_token'];
$profileError = $_SESSION['profile_error'] ?? null;
$profileNotice = $_SESSION['profile_notice'] ?? null;
unset($_SESSION['profile_error'], $_SESSION['profile_notice']);
?>

<div class="profile-page">
    <div class="page-title">
        <h1>Профил</h1>
    </div>
<?php if ($profileError): ?>
    <div class="login-error"><p><?php echo htmlspecialchars($profileError); ?></p></div>
<?php endif; ?>
<?php if ($profileNotice): ?>
    <div class="pane bg-green"><p><?php echo htmlspecialchars($profileNotice); ?></p></div>
<?php endif; ?>
<?php
if ($user->isActive() === false): ?>
    <div class="login-error">
        <p>Този акаунт все още не е активиран от администратор. Моля, изчакайте потвърждение по имейл.</p>
    </div>
<?php endif; ?>
    <div class="pane">
        <div class="pane-header">
            <h2>Здравей, <?php echo htmlspecialchars($user->getName()); ?>!</h2>
            <p><strong>@</strong><?php echo htmlspecialchars($user->getUsername() ?: 'n/a');?></p>
            <p><strong>E-мейл:</strong> <?php echo htmlspecialchars($user->getEmail()); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user->getPhone() ?? 'N/A'); ?></p>

	        <?php if ($user->isAdmin()): ?>
                <p><strong>Role:</strong> Admin</p>
                <a href="/backbone" class="button">Go to Admin Dashboard</a>
	        <?php endif; ?>
        </div>
        <?php if (empty($user->getUsername())): ?>
            <div class="pane-header">
                <p class="bg-yellow">
                    Непълен профил! За достъп до активната комуникация (мейл, чат и пр.) се изисква
                    <a href="/profile/complete" class="button">завършване на профила</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($activeConference): ?>
        <div class="pane bg-green">
                <p>Активна конференция: <strong><?php echo htmlspecialchars($activeConference->getTitle()); ?></strong></p>
            <?php if (!$registeredForActiveConf): ?>
                <p><a href="/volunteers/new" class="btn bg-lightblue">Включи се</a></p>
            <?php else: ?>
                <p><a href="/volunteers/new" class="btn bg-yellow">Допълнителна регистрация</a> </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="pane bg-lightblue">
            <p>В момента няма активна конференция. Моля, следете за новини!</p>
        </div>
    <?php endif; ?>

    <div class="page-title">
        <h2>Твоите доброволчески регистрации</h2>
        <p>Тук можеш да видиш информация за твоите регистрации като доброволeц.</p>
    </div>
    <?php foreach ($volunteers as $volunteer): ?>
        <?php
        $bgClass = match ($volunteer->status) {
            'accepted' => 'bg-green',
            'denied' => 'bg-red',
            default => 'bg-yellow',
        };
        $mugshotId = 'mugshot-' . (int)$volunteer->id . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$volunteer->conference);
        $isActiveRegistration = $activeConference && $volunteer->conference === $activeConference->getSlug();
        ?>
    <div class="pane<?php echo $isActiveRegistration ? ' active-conference' : ''; ?>">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($volunteer->title);?></h2>
            <?php if ($isActiveRegistration): ?>
                <span class="team-badge bg-green">Активна конференция</span>
            <?php endif; ?>
        </div>
        <div class="profile-header">
            <?php if($volunteer->mugshot): ?>
                <img class="profile-image" id="<?php echo $mugshotId; ?>" src="/assets/uploads/volunteers/<?php echo htmlspecialchars($volunteer->mugshot); ?>" alt="Profile Picture">
            <?php else: ?>
                <img class="profile-image" id="<?php echo $mugshotId; ?>" src="/assets/img/default-profile.png" alt="Default Profile Picture">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($volunteer->name)?></h3>
            <?php if ($isActiveRegistration): ?>
                <form action="/profile/details" method="post" enctype="multipart/form-data" class="mugshot-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="volunteer_id" value="<?php echo (int)$volunteer->id; ?>">
                    <input type="file" name="picture" accept="image/jpeg,image/png,image/gif" data-preview="<?php echo $mugshotId; ?>" required>
                    <button class="btn" type="submit">Смени снимката</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <p>
                <strong>Дата на регистрация:</strong> <?php echo htmlspecialchars(date('d.m.Y', strtotime($volunteer->registration_date))); ?>
                 <span class="team-badge <?php echo $bgClass;?>"><?php echo ucfirst(htmlspecialchars($volunteer->status)); ?></span>
            </p>
            <?php if ($isActiveRegistration): ?>
                <form action="/profile/details" method="post" class="details-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="volunteer_id" value="<?php echo (int)$volunteer->id; ?>">
                    <?php
                    $fields = [
                        'tshirt_size' => ['Размер на тениска', ['s' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL', 'xxl' => 'XXL', 'xxxl' => 'XXXL']],
                        'tshirt_cut' => ['Кройка на тениска', ['unisex' => 'Унисекс', 'female' => 'Дамска']],
                        'food_preferences' => ['Храна', ['none' => 'Нищо специфично', 'vegetarian' => 'Вегетарианец', 'vegan' => 'Веган']],
                        'lang' => ['Език', ['bg' => 'Български', 'en' => 'Английски']],
                    ];
                    foreach ($fields as $field => [$label, $options]): ?>
                        <p>
                            <label for="<?php echo $field . '_' . (int)$volunteer->id; ?>"><strong><?php echo $label; ?>:</strong></label>
                            <select name="<?php echo $field; ?>" id="<?php echo $field . '_' . (int)$volunteer->id; ?>">
                                <?php foreach ($options as $value => $optionLabel): ?>
                                    <option value="<?php echo $value; ?>"<?php echo $volunteer->$field === $value ? ' selected' : ''; ?>><?php echo $optionLabel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    <?php endforeach; ?>
                    <button class="btn" type="submit">Запази</button>
                </form>
            <?php else: ?>
            <p><strong>Размер на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size)); ?></p>
            <p><strong>Кройка на тениска:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_cut)); ?></p>
            <p><strong>Храна:</strong> <?php echo ucfirst(htmlspecialchars($volunteer->food_preferences)); ?></p>
            <p><strong>Език:</strong> <?php echo htmlspecialchars($volunteer->lang); ?></p>
            <?php endif; ?>
            <p><strong>Екип/и/:</strong>
		        <?php if (!empty($volunteer->teams)): ?>
			        <?php foreach (json_decode($volunteer->teams) as $team => $isPrimary): ?>
                        <span class="team-badge <?php echo ($isPrimary ? 'bg-green': '');?>"><?php echo htmlspecialchars($team); ?></span>
			        <?php endforeach; ?>
		        <?php else: ?>
                    <span class="team-badge">N/A</span>
		        <?php endif; ?>
            </p>
            <p><strong>Предишен опит:</strong> <?php echo nl2br(htmlspecialchars($volunteer->previous_experience)); ?></p>
            <p><strong>Бележки:</strong> <?php echo nl2br(htmlspecialchars($volunteer->notes)); ?></p>
        </div>
    </div>

    <?php endforeach;?>
</div>

<script>
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const img = document.getElementById(input.dataset.preview);
            const file = input.files[0];
            if (!img || !file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function (e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
        });
    });
</script>
