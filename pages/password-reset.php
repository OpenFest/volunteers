<?php

/**
 * If the user is already logged in and is admin, redirect to the backbone page,
 * otherwise redirect to the home page
 * @return void
 */
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']->isAdmin()) {
        header('Location: /backbone');
    } else {
        header('Location: /profile');
    }
    exit;
}

function handlePost($database): void
{
    $usernameOrEmail = $_POST['username'] ?? '';

    if (empty($usernameOrEmail)) {
        echo "<h3 class='login-error'>Моля, въведете валиден имейл или потребителско име.</h3>";
        return;
    }

    $user = $database->query(
        'SELECT * FROM users WHERE email = :identifier OR username = :identifier',
        [':identifier' => $usernameOrEmail]
    );

    $errorDetected = FALSE;
    if (isset($user[0])) {
        //generate reset token and send email
        $user = User::load($user[0]->uid);
        $resetToken = User::generateToken($user->getEmail());
        $user->setToken($resetToken, '1 hour');
        $resetLink = "https://{$_SERVER['HTTP_HOST']}/password-reset?token={$resetToken}";
        //send email
        $subject = "Инструкции за възстановяване на паролата";
        $message = "Здравейте " . $user->getName() . ", 
\n\nПолучихме заявка за възстановяване на паролата за вашия акаунт. Можете да нулирате паролата си, като кликнете на следната връзка:\n\n{$resetLink}\n\nТази връзка ще бъде валидна за 1 час. Ако не сте направили тази заявка, моля, игнорирайте това съобщение.\n\nПоздрави,\nЕкипът на конференцията";
        $headers = "From: no-reply@{$_SERVER['HTTP_HOST']}\ r\nReply-To: no-reply@{$_SERVER['HTTP_HOST']}\r\n";
        if (!mail($user->getEmail(), $subject, $message, $headers)) {
            _log("Failed to send password reset email to: " . $user->getEmail(), LOG_ERR);
            echo "<h3 class='login-error'>Грешка при изпращане на имейл. Моля, опитайте по-късно.</h3>";
                $errorDetected = TRUE;
        }
    }

    if (!$errorDetected) {
        echo "<h1>Заявката е приета!</h1>";
        echo "<p>Ако има потребител с този имейл или потребителско име, ще получите инструкции за възстановяване на паролата.</p>";
    }
}

//if post request, process the login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	handlePost($this->database);
} else {
?>
<form action="/password-reset" method="post" class="login-form">
    <div class="input">
        <label for="username">Username/Email:</label>
        <input type="text" name="username" id="username" required>
    </div>
    <div class="form-actions">
        <button class="btn" type="submit">Забравена Парола</button>
    </div>
</form>
<?php
}
