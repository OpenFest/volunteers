<?php
$activeConf = 'of-2025';

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
	// CSRF token is missing or invalid
	echo "<h1>Грешка при изпращане на формуляра</h1>";
	echo "<p>Моля, опитайте отново.</p>";
	return; // Stop further processing
}

if (empty($_POST['volunteer'])) {
    // No volunteer data submitted
    echo "<h1>Грешка при изпращане на формуляра</h1>";
    echo "<p>Моля, опитайте отново.</p>";
    return; // Stop further processing
}

$allTeams = $this->database->query("SELECT * FROM teams where conference = :conference", ['conference' => $activeConf]);
$volunteerTeams = [];
foreach ($allTeams as $row) {
	$volunteerTeams[] = $row->slug;
}

//validate data
$volunteerData = (object) $_POST['volunteer'];
$errors = [];

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

if (empty($volunteerData->tshirt_size) || !in_array($volunteerData->tshirt_size, ['xs', 's', 'm', 'l', 'xl', 'xxl'])) {
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

if (!empty($errors)) {
	// Display errors
?>
<h1>Моля, корегирайте следните грешки:</h1>
<ul>
<?php
	foreach ($errors as $error) {
		echo "<li>$error</li>";
	}
?>

	<a href="javascript:history.back()">Върнете се назад</a>
</ul>
<?php
	return; // Stop further processing if there are errors (don't use exit, as template rendering won't work properly)
}
// Process the form data:

// 1. Check email uniqueness in users table
$userID = 0;
$existingUsers = $this->database->query(
    'SELECT uid FROM users WHERE email = :email',
    [':email' => $volunteerData->email]
);
if (empty($existingUsers)) {
    // Email not found in users table, proceed to insert
    $newUser = $this->database->query(
        'INSERT INTO users (uid, email, name, phone, lang, tshirt_size ,tshirt_cut, food_preferences) VALUES (:uid, :email, :name, :phone, :lang, :tshirt_size, :tshirt_cut, :food_preferences) RETURNING uid',
        [
            ':uid' => uuid(),
	        ':email' => $volunteerData->email,
            ':name' => $volunteerData->name,
            ':phone' => $volunteerData->phone,
            ':lang' => $volunteerData->language,
            ':tshirt_size' => $volunteerData->tshirt_size,
            ':tshirt_cut' => $volunteerData->tshirt_cut,
            ':food_preferences' => $volunteerData->food_preferences,
        ]
    );
    if ($newUser) {
        $userID = $newUser[0]->uid;
    } else {
        // If there was an error inserting the user, show an error message
        echo "<h1>Грешка при регистрацията</h1>";
        echo "<p>Моля, опитайте отново по-късно.</p>";
        return; // Stop further processing
    }
} else {
    // Email already exists in users table, use the existing user ID
    $userID = $existingUsers[0]->uid;
}


$newVolunteer = $this->database->query(
	'INSERT INTO volunteers (clarion_email, "user", mugshot) VALUES (null, :userID, null) RETURNING id',
	[':userID' => $userID]
);

if ($newVolunteer) {
	// insert into volunteers_teams
	$volunteerId = $newVolunteer[0]->id;
	$r =  TRUE;
	foreach ($volunteerData->volunteer_team_ids as $team) {
		$r = $r && $this->database->query(
			'INSERT INTO volunteer_teams(volunteer, conference, team) VALUES (:volunteerID, :conference, :volunteerTeam)',
            [':volunteerID' => $volunteerId, ':volunteerTeam' => $team, ':conference' => $activeConf]
		);
	}
	if (!$r) {
		// If there was an error inserting into volunteers_teams, show an error message
		echo "<h1>Грешка при регистрацията</h1>";
		echo "<p>Моля, опитайте отново по-късно.</p>";
		return; // Stop further processing
	}
	// If the volunteer was successfully added, you can redirect or show a success message
	echo "<h1>Благодарим ви за регистрацията!</h1>";
	echo "<p>Вашата регистрация е успешна. Ще се свържем с вас скоро.</p>";
} else {
	// If there was an error adding the volunteer, show an error message
	echo "<h1>Грешка при регистрацията</h1>";
	echo "<p>Моля, опитайте отново по-късно.</p>";
}

?>

