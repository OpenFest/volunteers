<?php

checkAuth(TRUE);

function handlePost($database): void
{
    $usernameOrEmail = $_POST['username'] ?? '';
    $token = $_GET['token'] ?? '';
    //if token is set, handle password reset
    if (!empty($token)) {
        $user = $database->query(
            'SELECT * FROM users WHERE token = :token AND token_expiry > NOW()',
            [':token' => $token]
        );
        if (empty($user)) {
            echo "<h3 class='login-error'>Невалиден или изтекъл токен.</h3>";
            return;
        }
        $user = User::load($user[0]->uid);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (empty($password) || empty($confirmPassword)) {
            echo "<h3 class='login-error'>Моля, попълнете всички полета.</h3>";
            return;
        }
        if ($password !== $confirmPassword) {
            echo "<h3 class='login-error'>Паролите не съвпадат.</h3>";
            return;
        }
        // Validate password strength
        $passwordValidation = validatePasswordStrength($password);
        if (!$passwordValidation['valid']) {
            echo "<h3 class='login-error'>Паролата не отговаря на изискванията за сигурност:</h3>";
            echo "<ul class='login-error'>";
            foreach ($passwordValidation['errors'] as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
            return;
        }
        if (!$user->setPassword($password)) {
            echo "<h3 class='login-error'>Грешка при нулиране на паролата. Моля, опитайте по-късно.</h3>";
            return;
        }
        $user->resetToken();
        echo "<h1>Паролата е нулирана успешно!</h1><p>Можете да влезете с новата си парола.</p>";
        //set session and redirect to profile
        $_SESSION['user'] = $user;
        header('Location: /profile');
        return;
    }

    //not a token - then handle password reset request
    if (empty($usernameOrEmail)) {
        echo "<h3 class='login-error'>Моля, въведете валиден имейл или потребителско име.</h3>";
        return;
    }

    $user = $database->query(
        'SELECT * FROM users WHERE email = :identifier OR username = :identifier',
        [':identifier' => $usernameOrEmail]
    );

    if (!empty($user)) {
        $user = User::load($user[0]->uid);
        $resetToken = User::generateToken($user->getEmail());
        $user->setToken($resetToken, '1 hour');
        $resetLink = "https://{$_SERVER['HTTP_HOST']}/password-reset?token={$resetToken}";
        $subject = "Инструкции за възстановяване на паролата";
        $message = "Здравейте " . $user->getName() . ", 
\n\nПолучихме заявка за възстановяване на паролата за вашия акаунт. Можете да нулирате паролата си, като кликнете на следната връзка:\n\n{$resetLink}\n\nТази връзка ще бъде валидна за 1 час. Ако не сте направили тази заявка, моля, игнорирайте това съобщение.\n\nПоздрави,\nЕкипът на конференцията";
        $headers = "From: no-reply@{$_SERVER['HTTP_HOST']}\ r\nReply-To: no-reply@{$_SERVER['HTTP_HOST']}\r\n";
        if (!mail($user->getEmail(), $subject, $message, $headers)) {
            echo "<h3 class='login-error'>Грешка при изпращане на имейл. Моля, опитайте по-късно.</h3>";
            return;
        }
    }

    echo "<h1>Заявката е приета!</h1><p>Ако има потребител с този имейл или потребителско име, ще получите инструкции за възстановяване на паролата.</p>";
}

function handleToken($database): void
{
    $token = $_GET['token'];
    if (empty($token)) {
        echo "<h3 class='login-error'>Невалиден токен.</h3>";
        return;
    }

    $user = $database->query(
        'SELECT * FROM users WHERE token = :token AND token_expiry > NOW()',
        [':token' => $token]
    );

    if (empty($user)) {
        echo "<h3 class='login-error'>Невалиден или изтекъл токен.</h3>";
        return;
    }

    echo '<h1>Нулиране на паролата</h1>
    <form action="/password-reset?token=' . htmlspecialchars($token) . '" method="post" class="login-form">
        <div class="input">
            <label for="password">Нова парола:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="input">
            <label for="confirm_password">Потвърдете паролата:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Нулиране на паролата</button>
        </div>
    </form>
    <script src="/assets/js/password-strength.js"></script>
    <script>
        document.addEventListener(\'DOMContentLoaded\', function() {
            const passwordChecker = new PasswordStrengthChecker(\'password\', \'confirm_password\', {
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            });
            
            const form = document.querySelector(\'form\');
            if (form) {
                form.addEventListener(\'submit\', function(e) {
                    if (!passwordChecker.isValid()) {
                        e.preventDefault();
                        alert(\'Моля, поправете грешките в паролата преди да продължите.\');
                    }
                });
            }
        });
    </script>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	handlePost($this->database);
} elseif (isset($_GET['token'])) {
	handleToken($this->database);
} else {
    echo '<form action="/password-reset" method="post" class="login-form">
        <div class="input">
            <label for="username">Username/Email:</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Забравена Парола</button>
        </div>
    </form>';
}