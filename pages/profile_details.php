<?php
checkAuth();

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /profile');
    exit;
}

$fail = function (string $message) {
    $_SESSION['profile_error'] = $message;
    header('Location: /profile');
    exit;
};

if (empty($_POST['csrf_token']) || empty($_SESSION['profile_csrf_token'])
    || !hash_equals($_SESSION['profile_csrf_token'], $_POST['csrf_token'])) {
    _log('CSRF token validation failed during volunteer details update.', LOG_WARNING);
    $fail('Грешка при изпращане на формуляра. Моля, опитайте отново.');
}

$activeConference = Conference::getActive();
$volunteerId = filter_var($_POST['volunteer_id'] ?? null, FILTER_VALIDATE_INT);
if (!$activeConference || !$volunteerId) {
    $fail('Данните не могат да бъдат променени.');
}

$rows = $this->database->query(
    'SELECT v.id, v.mugshot FROM volunteers v
     JOIN volunteer_teams vt ON vt.volunteer = v.id
     WHERE v.id = :id AND v."user" = :uid AND vt.conference = :conference
     LIMIT 1',
    [':id' => $volunteerId, ':uid' => $user->getId(), ':conference' => $activeConference->getSlug()]
);
if (empty($rows)) {
    _log("Details update denied for volunteer $volunteerId, user " . $user->getId(), LOG_WARNING);
    $fail('Данните не могат да бъдат променени.');
}

$updates = [];
$newFile = null;
$uploadDir = ASSETS_DIR . 'uploads/volunteers/';

if (isset($_POST['tshirt_size'])) {
    $details = [
        'lang' => [$_POST['lang'] ?? '', ['bg', 'en']],
        'tshirt_size' => [$_POST['tshirt_size'], ['s', 'm', 'l', 'xl', 'xxl', 'xxxl']],
        'tshirt_cut' => [$_POST['tshirt_cut'] ?? '', ['unisex', 'female']],
        'food_preferences' => [$_POST['food_preferences'] ?? '', ['vegetarian', 'vegan', 'none']],
    ];
    foreach ($details as $column => [$value, $allowed]) {
        if (!in_array($value, $allowed, true)) {
            $fail('Невалидни данни. Моля, опитайте отново.');
        }
        $updates[$column] = $value;
    }
}

$picture = $_FILES['picture'] ?? null;
if ($picture && $picture['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($picture['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($picture['tmp_name'])) {
        $fail('Грешка при качване на снимката. Моля, опитайте отново.');
    }
    if ($picture['size'] > 2 * 1024 * 1024) {
        $fail('Снимката не трябва да е по-голяма от 2MB.');
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($picture['tmp_name']);
    if (!isset($extensions[$mime])) {
        $fail('Моля, качете валидна снимка (JPEG, PNG или GIF).');
    }
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        _log('Failed to create upload directory: ' . $uploadDir, LOG_ERR);
        $fail('Грешка при качване на снимката. Моля, опитайте по-късно.');
    }
    $newFile = uniqid('volunteer_', true) . '.' . $extensions[$mime];
    if (!move_uploaded_file($picture['tmp_name'], $uploadDir . $newFile)) {
        _log('Failed to move uploaded file to: ' . $uploadDir . $newFile, LOG_ERR);
        $fail('Грешка при качване на снимката. Моля, опитайте по-късно.');
    }
    $updates['mugshot'] = $newFile;
}

if (empty($updates)) {
    $fail('Няма промени за запазване.');
}

$assignments = [];
$params = [':id' => $volunteerId];
foreach ($updates as $column => $value) {
    $assignments[] = "$column = :$column";
    $params[":$column"] = $value;
}
$this->database->query('UPDATE volunteers SET ' . implode(', ', $assignments) . ' WHERE id = :id', $params);

$oldMugshot = $rows[0]->mugshot ?? null;
if ($newFile && !empty($oldMugshot) && basename($oldMugshot) === $oldMugshot) {
    @unlink($uploadDir . $oldMugshot);
}

_log("Profile updated for volunteer $volunteerId: " . implode(', ', array_keys($updates)));
$_SESSION['profile_notice'] = 'Промените са запазени.';
header('Location: /profile');
exit;
