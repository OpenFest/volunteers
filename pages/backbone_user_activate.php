<?php
function activate($database): User|bool|null
{

    //detect user
    if (empty($_GET['user'])) {
        echo "<h3 class='login-error'>Unknown user.</h3>";
        return FALSE;
    }

    $userID = $_GET['user'];
    $user = User::load($userID);
    if (empty($user)) {
        echo "<h3 class='login-error'>Unknown user.</h3>";
        return FALSE;
    }
    //if active, return error (already active)
    if ($user->isActive()) {
        echo "<h3 class='login-error'>User is already active.</h3>";
        return FALSE;
    }
    //set session hash to confirm
    if (!isset($_SESSION['user_activation_hash'])) {
	    $hash = User::generateToken($user->getEmail());
	    $_SESSION['user_activation_hash'] = $hash;
	    
	    //show conformation page
	    echo "<h1>Activate User</h1>";
	    echo "<p>Are you sure you want to activate the user <strong>" . htmlspecialchars($user->getUsername() ?: $user->getEmail()) . "</strong>?</p>";
	    echo "<a href='?user={$user->getId()}&confirm={$hash}' class='button'>Activate</a>";
	    return FALSE;
    }
    
    if (empty($_GET['confirm']) || $_GET['confirm'] !== $_SESSION['user_activation_hash']) {
	    echo "<h3 class='login-error'>Invalid confirmation.</h3>";
	    unset($_SESSION['user_activation_hash']);
	    return FALSE;
	}
    
    unset($_SESSION['user_activation_hash']);
    
    // Set the user as active
    $database->query(
        'UPDATE users SET active = TRUE WHERE uid = :uid',
        [':uid' => $user->getId()]
    );
    
    //send mail to user to inform him about the activation
    $subject = "Вашият акаунт е активиран";
    $message = "Здравейте " . ($user->getName() ?: $user->getEmail()) . ",\n\nВашият акаунт в системата на конференцията е активиран от администратор. Можете да влезете и да започнете да използвате всички функции на платформата.\nhttp://{$_SERVER['HTTP_HOST']}/profile\n\nПоздрави,\nЕкипът на конференцията";
	$headers = "From: no-reply@{$_SERVER['HTTP_HOST']}\r\nReply-To: no-reply@{$_SERVER['HTTP_HOST']}\r\n";
	if (!mail($user->getEmail(), $subject, $message, $headers)) {
	    _log("Failed to send activation email to user: " . $user->getUsername() . " - " . $user->getEmail(), LOG_ERR);
	}
	
	_log('User activated: ' . $user->getUsername() . ' (' . $user->getEmail() . ')');

	//return updated user object
	return User::load($user->getId());
}

if ($user = activate($this->database)) {
    _log('User activated: ' . $user->getUsername() . ' (' . $user->getEmail() . ')');
    header("Location: /backbone/users");
    exit;
}

