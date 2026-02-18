<?php
function activate($database): User|bool|null
{

    //detect user
    if (empty($_GET['user'])) {
        echo "<h3 class='login-error'>Unknown user.</h3>";
        return FALSE;
    }

    $userID = $_GET['user'];
    $user = $database->query(
        'SELECT * from users where uid = :uid',
        [':uid' => $userID]
    );
    if (empty($user)) {
        echo "<h3 class='login-error'>Unknown user.</h3>";
        return FALSE;
    }
    //if active, return error (already active)
    $user = $user[0];
    if ($user->active) {
        echo "<h3 class='login-error'>User is already active.</h3>";
        return FALSE;
    }
    // Set the user as active
    $database->query(
        'UPDATE users SET active = TRUE WHERE uid = :uid',
        [':uid' => $user->uid]
    );

	return User::load($user);
}

if ($user = activate($this->database)) {
    _log('User activated: ' . $user->getUsername() . ' (' . $user->getEmail() . ')');
    header("Location: /backbone/users");
    exit;
}

