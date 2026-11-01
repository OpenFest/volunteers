<?php
//check if user is logged in and has admin rights

checkAdmin();

if (isset($_GET['remind-complete'])) {
    $email = $_GET['remind-complete'];
    $user = User::loadByEmail($email);
    if ($user) {
        $user->resetToken();
        $token = User::generateToken($user->getEmail());
        $resetLink = "https://{$_SERVER['HTTP_HOST']}/profile/complete?token={$token}";
        $subject = "Complete your profile";
        $message = <<<EOT
Hello {$user->getName()},
To complete your profile and get access to the conference communication, please click the following link:
{$resetLink}
This link will be valid for 24 hours.
If you do not complete your profile within this period, you will need to request a new profile completion link. In that case, please contact us!

Best regards,
The conference team
EOT;
        if (!_mail($user->getEmail(), $subject, $message)) {
            echo "<h3 class='login-error'>Error sending email. Please try again later.</h3>";
            return;
        }
        $user->setToken($token, '24 hours');
        _log('Resent profile completion email to user: ' . $user->getUsername() . ' (' . $user->getEmail() . ')');

        echo '<div class="pane full-width"><div class="pane-header"><p class="bg-green">Activation email sent to ' . htmlspecialchars($email) . '</p></div></div>';
    } else {
        echo '<div class="pane full-width"><div class="pane-header"><p class="bg-red">No user found with email ' . htmlspecialchars($email) . '</p></div></div>';
    }
}

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

$_SESSION['access_csrf_token'] ??= bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save-access'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['access_csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo "<h3 class='login-error'>Invalid CSRF token.</h3>";
        return;
    }
    $modules = is_array($_POST['access'] ?? null) ? $_POST['access'] : [];
    Access::sync($user->getId(), $modules);
    _log('Access modules for user ' . $user->getEmail() . ' set to: ' . implode(',', array_filter($modules, 'Access::isModule')));
    header('Location: /backbone/profile?user=' . urlencode($user->getId()));
    exit;
}
$userAccess = Access::forUser($user->getId());

$volunteers = $this->database->query(
    'SELECT v.*, vt.conference, c.title,jsonb_object_agg(t.slug, jsonb_build_object(\'name\', t.name, \'is_primary\', vt.is_primary)) as teams,
    bool_or(vt.is_leader) as is_leader FROM volunteers v 
    LEFT JOIN volunteer_teams vt ON v.id=vt.volunteer
    LEFT JOIN teams t ON vt.team = t.slug 
    LEFT JOIN conferences c ON vt.conference = c.slug
    WHERE v.user = :uid group by v.id, vt.conference, v.registration_date, c.title ORDER BY v.registration_date DESC',
    [':uid' => $userID]
);

?>

<div class="backbone-page profile-page">
    <div class="page-title">
        <h1>Profile</h1>
    </div>

    <div class="pane full-width">
        <div class="pane-header">
            <h2> <?php echo htmlspecialchars($user->getName()); ?></h2>
            <h3> @<?php echo htmlspecialchars($user->getUsername() ?: 'n/a'); ?></h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user->getEmail()); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user->getPhone() ?? 'N/A'); ?></p>
        </div>
    </div>
    <div class="pane full-width">
        <div class="pane-header">
            <h2>Backbone access</h2>
            <?php if ($user->isAdmin()): ?>
                <p>Admin: full access to all modules.</p>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['access_csrf_token']); ?>">
                    <?php foreach (Access::MODULES as $key => [$label]): ?>
                        <label>
                            <input type="checkbox" name="access[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo in_array($key, $userAccess, true) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" name="save-access" value="1" class="button">Save</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($user->getUsername())): ?>
        <div class="pane full-width">
            <div class="pane-header">
                <p class="bg-yellow">
                    Incomplete profile! Completing the profile is required for access to active communication (email, chat, etc.). <br/>
                    <a href="/backbone/profile?remind-complete=<?php echo $user->getEmail()?>" class="button">re-send email for reminder</a>
                </p>
            </div>
        </div>
    <?php endif; ?>


    <div class="page-title">
        <h2>Volunteer registrations</h2>
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
                <img class="profile-image zoomable" src="/assets/uploads/volunteers/<?php echo htmlspecialchars($volunteer->mugshot); ?>" alt="Profile Picture">
            <?php else: ?>
                <img class="profile-image" src="/assets/img/default-profile.png" alt="Default Profile Picture">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($volunteer->name)?></h3>

        </div>
        <div class="profile-info">
            <p>
            <strong>Registration date:</strong> <?php echo htmlspecialchars(date('d.m.Y', strtotime($volunteer->registration_date))); ?>
                [
                <span class="team-badge <?php echo $bgClass;?>"><?php echo ucfirst(htmlspecialchars($volunteer->status)); ?></span>
                <?php if ($volunteer->status !== 'pending') {
                    echo ' @ ' . htmlspecialchars($volunteer->status_update_date ? date('d.m.Y', strtotime($volunteer->status_update_date)) : 'N/A');
                }?>
                ]
            </p>
            <p><strong>T-shirt size:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_size)); ?></p>
            <p><strong>T-shirt cut:</strong> <?php echo strtoupper(htmlspecialchars($volunteer->tshirt_cut)); ?></p>
            <p><strong>Food:</strong> <?php echo ucfirst(htmlspecialchars($volunteer->food_preferences)); ?></p>
            <p><strong>Language:</strong> <?php echo htmlspecialchars($volunteer->lang); ?></p>
            <p><strong>Team(s):</strong>
		        <?php if (!empty($volunteer->teams)): ?>
			        <?php foreach (json_decode($volunteer->teams) as $team => $teamData):
                         $isPrimary = $teamData->is_primary;
                         $teamName = $teamData->name;
			         ?>
                        <button
                        class="team-badge <?php echo ($isPrimary ? 'bg-green': 'btn set-primary-team-button');?>"
                        <?php echo (!$isPrimary && $volunteer->is_leader) ? 'disabled title="Team leaders cannot change their primary team"' : ''; ?>
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
                        class="btn team-badge assign-to-team-button">
                    +
                </button>
            </p>
            <p><strong>Previous experience:</strong> <?php echo nl2br(htmlspecialchars($volunteer->previous_experience)); ?></p>
            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($volunteer->notes)); ?></p>
        </div>
    </div>

    <?php endforeach;?>
</div>
<script src="/assets/js/set-primary-team.js"></script>
<script src="/assets/js/team-add-volunteer.js"></script>

