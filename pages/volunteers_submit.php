<?php

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
	// CSRF token is missing or invalid
    _log('CSRF token validation failed during volunteer registration submission.', LOG_WARNING);
	echo "<h1>Грешка при изпращане на формуляра</h1>";
	echo "<p>Моля, опитайте отново.</p>";
	return; // Stop further processing
}

if (empty($_POST['volunteer'])) {
	// No volunteer data submitted
    _log('No volunteer data submitted during registration.', LOG_WARNING);
	echo "<h1>Грешка при изпращане на формуляра</h1>";
	echo "<p>Моля, опитайте отново.</p>";
	return; // Stop further processing
}
$activeConf = $this->database->query('SELECT slug FROM conferences WHERE registration_open <= now() AND registration_close >= now() ORDER BY start_date DESC LIMIT 1');

if (empty($activeConf)) {
    _log("No active conference found during volunteer registration.", LOG_WARNING);
    // No active conference found
    echo "<h1>Грешка при изпращане на формуляра</h1>";
    echo "<p>Моля, опитайте отново по-късно.</p>";
    return; // Stop further processing
}
$activeConf = $activeConf[0];

$allTeams = $this->database->query("SELECT * FROM teams where conference = :conference", ['conference' => $activeConf->slug]);
$volunteerTeams = [];
foreach ($allTeams as $row) {
	$volunteerTeams[] = $row->slug;
}

//validate data
$volunteerData = (object) $_POST['volunteer'];
$volunteerData->picture = $_FILES['picture'] ?? NULL; // Handle file upload
$errors = [];
_log('Processing volunteer registration submission...');

if (empty($volunteerData->name)) {
	$errors[] = 'Името е задължително.';
}

if (empty($volunteerData->email) || !filter_var($volunteerData->email, FILTER_VALIDATE_EMAIL)) {
	$errors[] = 'Моля, въведете валиден имейл адрес.';
}
if (empty($volunteerData->phone) || !preg_match('/^\+?[0-9\s]+$/', $volunteerData->phone)) {
	$errors[] = 'Моля, въведете валиден телефонен номер.';
}
if (empty($volunteerData->volunteer_team_ids) || !is_array($volunteerData->volunteer_team_ids)) {
	$errors[] = 'Моля, изберете поне един екип доброволци.';
} else {
	foreach ($volunteerData->volunteer_team_ids as $team) {
		if (!in_array($team, $volunteerTeams)) {
			$errors[] = "Екипът '$team' не е валиден.";
		}
	}
}
if (empty($volunteerData->language) || !in_array($volunteerData->language, ['bg', 'en'])) {
	$errors[] = 'Моля, изберете валиден език.';
}

if (empty($volunteerData->tshirt_size) || !in_array($volunteerData->tshirt_size, ['s', 'm', 'l', 'xl', 'xxl', 'xxxl'])) {
	$errors[] = 'Моля, изберете валиден размер на тениската.';
}

if (empty($volunteerData->tshirt_cut) || !in_array($volunteerData->tshirt_cut, ['unisex', 'female'])) {
	$errors[] = 'Моля, изберете валиден тип на тениската.';
}

if (empty($volunteerData->food_preferences) || !in_array($volunteerData->food_preferences, ['vegetarian', 'vegan', 'none'])) {
	$errors[] = 'Моля, изберете валидна предпочитана храна.';
}

if (empty($volunteerData->terms_accepted)) {
	$errors[] = 'Моля, приемете условията за участие.';
}
if (!empty($volunteerData->picture) && !empty($volunteerData->picture['tmp_name'])) {
    // Validate the uploaded picture
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $picture = $volunteerData->picture;
    if (!in_array($picture['type'], $allowedMimeTypes)) {
        $errors[] = 'Моля, качете валидна снимка (JPEG, PNG или GIF).';
    } elseif ($picture['size'] > 2 * 1024 * 1024) { // 2MB limit
        $errors[] = 'Снимката не трябва да е по-голяма от 2MB.';
    }
}

if (!empty($errors)) {

	// Display errors
	?>
    <h1>Моля, корегирайте следните грешки:</h1>
    <ul>
		<?php
		foreach ($errors as $error) {
            _log($error);
			echo "<li>$error</li>";
		}
		?>
    </ul>
    <br/>
    <a href="javascript:history.back()">Върнете се назад</a><br/>
    <span class="small red">(ако има прикачена снимка, ще трябва да се добави отново)</span>
	<?php
	return; // Stop further processing if there are errors (don't use exit, as template rendering won't work properly)
}
// Process the form data:
// 0. Store the picture if provided
if (!empty($volunteerData->picture) && !empty($volunteerData->picture['tmp_name'])) {
    $picture = $volunteerData->picture;
    $uploadDir = ASSETS_DIR . 'uploads/volunteers/';
    $fileName = uniqid('volunteer_', true) . '.' . pathinfo($picture['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        if(!mkdir($uploadDir, 0755, true)) {
            _log('Failed to create upload directory: ' . $uploadDir, LOG_ERR);
            echo "<h1>Грешка при създаване на директорията за качване</h1>";
            echo "<p>Моля, опитайте отново по-късно.</p>";
            return; // Stop further processing
        }
    }

    // Move the uploaded file to the designated directory
    if (move_uploaded_file($picture['tmp_name'], $filePath)) {
        // Successfully uploaded
        $volunteerData->mugshot = $fileName;
    } else {
        // Handle upload error
        _log('Failed to move uploaded file to: ' . $filePath, LOG_ERR);
        echo "<h1>Грешка при качване на снимката</h1>";
        echo "<p>Моля, опитайте отново по-късно.</p>";
        return; // Stop further processing
    }
} else {
    $volunteerData->mugshot = null; // No picture provided
}

// 1. Check email uniqueness in users' table
$userID = 0;
$existingUsers = $this->database->query(
	'SELECT uid FROM users WHERE email = :email',
	[':email' => $volunteerData->email]
);
//sha512 uuid to generate a verification token
$verificationToken = hash('sha512', uuid() . $volunteerData->email . time());
$verificationToken = substr($verificationToken, 0, 29) . '-' . substr($verificationToken, -30);

if (empty($existingUsers)) {
	// Email not found in users' table, proceed to insert

	$email = $volunteerData->email;
	$name = $volunteerData->name;
	$phone = $volunteerData->phone;
    $admin = FALSE;
    $username = NULL;

    //check ldap for existing user
    $ldap = new LDAP(LDAP_SERVER,  LDAP_BASE_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
    $ldapUser = $ldap->getUser($volunteerData->email);
    if ($ldapUser) {
        _log('Detected LDAP user for email: ' . $volunteerData->email);
        $email = $ldapUser->mail;
        $name = $ldapUser->givenname . ' ' . $ldapUser->sn;
        $admin = $ldap->isMember($ldapUser, ['core', 'global admin']);
        $username = $ldapUser->uid ?? NULL; // Use uid if available
    } else {
        _log('LDAP user not found for email: ' . $volunteerData->email, LOG_WARNING);
    }


	$newUser = $this->database->query(
		'INSERT INTO users 
        (uid, email, name, phone, admin, username, token, token_expiry) 
        VALUES 
        (:uid, :email, :name, :phone, :admin, :username, :token, now() + interval \'1 day\') RETURNING uid',
		[
			':uid' => uuid(),
			':email' => $email,
			':name' => $name,
			':phone' => $phone,
            ':admin' => $admin ? 'true' : 'false',
            ':username' => $username,
            ':token' => $verificationToken

		]
	);
	if ($newUser) {
		$userID = $newUser[0]->uid;
	} else {
        _log('Failed to insert new user with email: ' . $volunteerData->email, LOG_ERR);
		// If there was an error inserting the user, show an error message
		echo "<h1>Грешка при регистрацията</h1>";
		echo "<p>Моля, опитайте отново по-късно.</p>";
		return; // Stop further processing
	}
} else {
	// Email already exists in users' table, use the existing user ID
    _log('Using existing user with email: ' . $volunteerData->email);
	$userID = $existingUsers[0]->uid;
    //reset the verification token and expiry for the existing user
    $sql = 'UPDATE users SET token = :token, token_expiry = now() + interval \'1 day\' WHERE uid = :uid';
    $this->database->query($sql, [
        ':token' => $verificationToken,
        ':uid' => $userID
    ]);
}


$newVolunteer = $this->database->query(
	'INSERT INTO 
	volunteers 
	(clarion_email, "user",  tshirt_size, tshirt_cut, food_preferences, mugshot, name, previous_experience, notes, lang)
	VALUES 
	(null, :userID, :tshirt_size, :tshirt_cut, :food_preferences, :mugshot, :name, :previous_experience, :notes, :lang) RETURNING id',
	[
		':userID' => $userID,
		':tshirt_size' => $volunteerData->tshirt_size,
		':tshirt_cut' => $volunteerData->tshirt_cut,
		':food_preferences' => $volunteerData->food_preferences,
        ':mugshot' => $volunteerData->mugshot ?? null,
        ':name' => $volunteerData->name,
		':lang' => $volunteerData->language,
        ':previous_experience' => $volunteerData->previous_experience ?? null,
        ':notes' => $volunteerData->notes ?? null
	]
);

if ($newVolunteer) {
	// insert into volunteers_teams
	$volunteerId = $newVolunteer[0]->id;
	$r = TRUE;
	foreach ($volunteerData->volunteer_team_ids as $team) {
		$r = $r && $this->database->query(
				'INSERT INTO volunteer_teams(volunteer, conference, team) VALUES (:volunteerID, :conference, :volunteerTeam)',
				[':volunteerID' => $volunteerId, ':volunteerTeam' => $team, ':conference' => $activeConf->slug]
			);
	}
	if (!$r) {
        _log('Failed to insert volunteer teams for volunteer ID: ' . $volunteerId, LOG_ERR);
		// If there was an error inserting into volunteers_teams, show an error message
		echo "<h1>Грешка при регистрацията</h1>";
		echo "<p>Моля, опитайте отново по-късно.</p>";
		return; // Stop further processing
	}
    // Send verification email
    $verificationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/verify?token=' . urlencode($verificationToken);
    $subject = 'Потвърждение на регистрацията като доброволец';
    $message = "Здравейте, " . htmlspecialchars($volunteerData->name) . ",\n\n" .
        "Благодарим ви, че се регистрирахте като доброволец за конференцията!\n\n" .
        "Моля, потвърдете регистрацията си, като кликнете върху следния линк:\n" .
        $verificationLink . "\n\n" .
        "Ако не сте се регистрирали, моля, игнорирайте този имейл.\n\n" .
        "Поздрави,\n" .
        "Екипът на конференцията";
    $headers = 'From: no-reply@openfest.org' . "\r\n" .
        'Reply-To: no-reply@openfest.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    if (mail($volunteerData->email, $subject, $message, $headers)) {
        _log('Verification email sent to: ' . $volunteerData->email);
    } else {
        _log('Failed to send verification email to: ' . $volunteerData->email, LOG_ERR);
        // If there was an error sending the email, show an error message
        echo "<h1>Грешка при изпращане на имейла за потвърждение</h1>";
        echo "<p>Моля, опитайте отново по-късно.</p>";
        return; // Stop further processing
    }
	// If the volunteer was successfully added, you can redirect or show a success message
    _log('New volunteer registered: ' . $volunteerData->name . ' (ID: ' . $volunteerId . ')');
?>
	<h1>Благодарим ви за регистрацията!</h1>
	<p>Вашата регистрация е успешна. Моля, проверете имейла си за потвърждение.</p>
    <script>
        // Clear the localStorage key used for form submission
        const storageKey = 'volunteerFormData';
        localStorage.removeItem(storageKey);
    </script>
<?php
} else {
    _log('Failed to insert new volunteer for user ID: ' . $userID, LOG_ERR);
	// If there was an error adding the volunteer, show an error message
	echo "<h1>Грешка при регистрацията</h1>";
	echo "<p>Моля, опитайте отново по-късно.</p>";
}

?>

