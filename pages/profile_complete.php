<?php
//check if user is logged in

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    if (empty($token)) {
        echo "<h3 class='login-error'>Невалиден токен.</h3>";
        return;
    }

    $user = $this->database->query(
        'SELECT * FROM users WHERE token = :token AND token_expiry > NOW()',
        [':token' => $token]
    );
    $user = $user[0] ?? null;

    if ($user) {
        //setup clean session and log the user in
        checkAuth(TRUE);
        $userObject = User::load($user->uid);
        $_SESSION['user'] = $userObject;
        _log('User profile completion: ' . $userObject->getUsername() . ' (' . $userObject->getEmail() . ')');
        $userObject->resetToken();
        _log('User token reset.');
    } else {
        echo "<h3 class='login-error'>Невалидна връзка за завършване на профила. Моля, свържете се с администратора.</h3>";
        return;
    }
} else {
    //standard auth check for logged users
    checkAuth();
}

$user = $_SESSION['user'];
// check ldap user and redirect to profile, if exists
$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
try {
    $ldapUser = $ldap->getUser($user->getUsername());
    if ($ldapUser) {
        _log('Trying to complete profile for user: ' . $user->getUsername() . ' - LDAP user found, redirecting to profile');
        header('Location: /profile');
        exit;
    }
} catch (Exception $e) {
    _log("LDAP search failed for user: " . $user->getUsername() . " - " . $e->getMessage(), LOG_ERR);
    echo "<h3> Грешка в системата. Моля, свържете се с администратора.</h3>";
    exit;
}


//check if request is post and process data:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    //validate the data
    $passwordValidation = validatePasswordStrength($new_password);
    $existingUser = $this->database->query(
            'SELECT * FROM users WHERE username = :username AND uid != :uid',
            [':username' => $username, ':uid' => $user->getId()]
    );
    $existingEmail = $this->database->query(
            'SELECT * FROM users WHERE email = :email AND uid != :uid',
            [':email' => $email, ':uid' => $user->getId()]
    );
    if (empty($name) || empty($email) || empty($username) || empty($new_password) || empty($confirm_new_password)) {
        echo "<h3 class='login-error'>Моля, попълнете всички задължителни полета.</h3>";
    } elseif ($new_password !== $confirm_new_password) {
        echo "<h3 class='login-error'>Паролите не съвпадат.</h3>";
    } else if (!$passwordValidation['valid']) {
        echo "<h3 class='login-error'>Паролата не отговаря на изискванията за сигурност:</h3>";
        echo "<ul class='login-error'>";
        foreach ($passwordValidation['errors'] as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    } elseif (!preg_match('/^[a-zA-Z0-9._]{3,}$/', $username)) {
            echo "<h3 class='login-error'>Потребителското име трябва да съдържа само букви, цифри, точки и долни черти и да е поне 3 символа дълго.</h3>";
    } elseif (count($existingUser)) {
                echo "<h3 class='login-error'>Потребителското име вече съществува. Моля, изберете друго.</h3>";
    } elseif (count($existingEmail)) {
                echo "<h3 class='login-error'>Имейлът вече съществува. Моля, използвайте друг имейл.</h3>";
    } else {
        //create ldap user
        try {
            [$firstname, $lastname] = explode(' ', $name, 2) + [1 => ''];
            $ldap->addUser($username, $new_password, $firstname, $lastname, $email);
            _log('LDAP user created for profile completion: ' . $username);
        } catch (Exception $e) {
            _log("Failed to create LDAP user for profile completion: " . $username . " - " . $e->getMessage(), LOG_ERR);
            echo "<h3 class='login-error'>Грешка в системата! Моля, свържете се с администратор.</h3>";
            return;
        }

        //update the user in the database
        try {
            $this->database->query(
                    'UPDATE users SET name = :name, phone = :phone, email = :email, username = :username WHERE uid = :uid',
                    [
                            ':name' => $name,
                            ':phone' => $phone,
                            ':email' => $email,
                            ':username' => $username,
                            ':uid' => $user->getId()
                    ]
            );

            $user->reload();
            $activeConference = Conference::getActive();
            if ($activeConference) {
                $user->addToLdapGroups($activeConference->getSlug());
            }
            header('Location: /profile');
            exit;
        } catch (Exception $e) {
            _log("Error updating user profile: " . $e->getMessage(), LOG_ERR);
            echo "<h3 class='login-error'>Грешка при обновяване на профила. Моля, опитайте отново по-късно.</h3>";
        }
    }
}

//set last params case of errors or default values
$name = $name ?? $user->getName() ?? '';
$phone = $phone ?? $user->getPhone() ?? '';
$email = $email ?? $user->getEmail() ?? '';
$username = $username ?? $user->getUsername() ?? '';
?>

<div class="profile-page">
    <div class="page-title">
        <h1>Завършване на Профил</h1>
    </div>
<?php
if (!$user->isActive()): ?>
    <div class="login-error">
        <p>Този акаунт все още не е активиран от администратор. Моля, изчакайте потвърждение по имейл.</p>
    </div>
<?php else: ?>
    <div class="pane full-width">
        <form action="/profile/complete" method="POST" class="full-width" >
            <div class="pane full-width">
                <div class="pane-header"> ℹ️ Основна информация</div>
                <div class="pane-content">
                    <div class="form-group">
                        <label for="name">Име (препоръчително латиница)</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>
            </div>
            <div class="pane full-width">
                <div class="pane-header"> 🕵️‍ Акаунт</div>
                <div class="pane-content">
                    <div class="form-group">
                        <label for="username">Потребителско име</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Парола</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Потвърди паролата</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                    </div>

                </div>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Запази промените</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="/assets/js/password-strength.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize password strength checker
        const passwordChecker = new PasswordStrengthChecker('new_password', 'confirm_new_password', {
            minLength: 8,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: true
        });

        // Validate on form submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!passwordChecker.isValid()) {
                    e.preventDefault();
                    alert('Моля, поправете грешките в паролата преди да продължите.');
                }
            });
        }
    });
</script>
