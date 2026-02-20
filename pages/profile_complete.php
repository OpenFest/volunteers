<?php
//check if user is logged in and has admin rights

checkAuth();

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
    if (empty($name) || empty($email) || empty($username) || empty($new_password) || empty($confirm_new_password)) {
        echo "<h3 class='login-error'>Моля, попълнете всички задължителни полета.</h3>";
    } elseif ($new_password !== $confirm_new_password) {
        echo "<h3 class='login-error'>Паролите не съвпадат.</h3>";
    } else {

//        //create ldap user
//        try {
//            [$firstname, $lastname] = explode(' ', $name, 2) + [1 => ''];
//            $ldap->addUser($username, $new_password, $firstname, $lastname, $email);
//            _log('LDAP user created for profile completion: ' . $username);
//        } catch (Exception $e) {
//            _log("Failed to create LDAP user for profile completion: " . $username . " - " . $e->getMessage(), LOG_ERR);
//            echo "<h3 class='login-error'>Грешка в системата! Моля, свържете се с администратор.</h3>";
//            return;
//        }

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
            header('Location: /profile');
            exit;
        } catch (Exception $e) {
            _log("Error updating user profile: " . $e->getMessage(), LOG_ERR);
            echo "<h3 class='login-error'>Грешка при обновяване на профила. Моля, опитайте отново по-късно.</h3>";
        }
    }
}
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
                        <label for="name">Име</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user->getName()); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user->getPhone() ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->getEmail()); ?>" required>
                    </div>
                </div>
            </div>
            <div class="pane full-width">
                <div class="pane-header"> 🕵️‍ Акаунт</div>
                <div class="pane-content">
                    <div class="form-group">
                        <label for="username">Потребителско име</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user->getUsername()); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Нова парола</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Потвърди новата парола</label>
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

