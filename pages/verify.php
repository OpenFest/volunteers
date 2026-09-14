<h1>Потвърждение на регистрация</h1>
<?php
function verify($database): User|bool|null
{
//verify token from volunteers submission
	if (empty($_GET['token'])) {
		echo "<h3 class='login-error'>Невалиден токен.</h3>";
		return FALSE;
	}
	$token = $_GET['token'];
// Check if the token is valid
	$user = $database->query(
		'SELECT * from users where token = :token and token_expiry > NOW()',
		[':token' => $token]
	);

	if (empty($user)) {
		echo "<h3 class='login-error'>Невалиден токен.</h3>";
		return FALSE;
	}

// The user exists, log them in and set the active to true
    $user = $user[0];
    $user = User::load($user);

    $user->resetToken();
//verify non-verified volunteers linked to this user for the currently active conference

    $activeConference = Conference::getActive();
    if (!$activeConference) {
        echo "<h3 class='login-error'>Няма активна конференция.</h3>";
        return FALSE;
    }
    $database->query(
            'UPDATE volunteers SET verified = TRUE WHERE "user" = :uid AND verified = FALSE AND conference = :conference',
            [':uid' => $user->getId(), ':conference' => $activeConference->getSlug()]
    );
    _log("User {$user->getId()} verified for conference {$activeConference->getSlug()}");

    $volunteers = $database->query(
            'SELECT v.*, vt.conference, c.title, json_object_agg(t.name, vt.is_primary) as teams FROM volunteers v 
    LEFT JOIN volunteer_teams vt ON v.id=vt.volunteer
    LEFT JOIN teams t ON vt.team = t.slug 
    LEFT JOIN conferences c ON vt.conference = c.slug
    WHERE v.user = :uid AND vt.conference = :conference group by v.id, vt.conference, v.registration_date, c.title ORDER BY v.registration_date DESC',
            [':uid' => $user->getId(), ':conference' => $activeConference->getSlug()]
    );


    foreach ($volunteers as $volunteer) {
        //send mail to core about the verification
        $subject = "Нов доброволец за {$activeConference->getTitle()}";
        $message = <<<EOT
Здравейте,

Потребителят {$user->getName()} <{$user->getEmail()}> потвърди регистрацията си като доброволец.

Екипи: {$volunteer->teams}

Език: {$volunteer->language}
Телефон: {$volunteer->phone}
Размер на тениска: {$volunteer->tshirt_size}
Кройка на тениска: {$volunteer->tshirt_cut}
Предпочитания за храна: {$volunteer->food_preferences}

Предходен опит:
{$volunteer->previous_experience}
EOT;

        $headers = "From:no-reply@openfest.org\r\nReply-To:no-reply@openfest.org\r\n";
        $coreEmail = 'core@openfest.org';
        if (!mail($coreEmail, $subject, $message, $headers)) {
            _log("Failed to send verification notification email to core: " . $coreEmail, LOG_ERR);
        }
        _log("Sent verification notification email to core: " . $coreEmail);
    }
    return $user;
}

if ($user = verify($this->database)) {
    // Set the user in the session
    $_SESSION['user'] = $user;
    header('Location: /profile');
    exit;
}

