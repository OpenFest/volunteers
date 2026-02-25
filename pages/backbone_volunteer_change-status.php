<?php

checkAdmin();

$volunteerId = $_GET['volunteer'] ?? null;
$status = $_GET['status'] ?? null;
if (!$volunteerId) {
	header('Location: /backbone');
	exit;
}

if (!in_array($status, [ 'accept', 'deny'])) {
	echo "<h3 class='login-error'>Невалиден статус.</h3>";
	exit;
}

$volunteer = $this->database->query(
	'SELECT * FROM volunteers WHERE id = :id',
	[':id' => $volunteerId]
);
$volunteer = $volunteer[0] ?? null;
if (!$volunteer) {
	echo "<h3 class='login-error'>Доброволецът не е намерен.</h3>";
	exit;
}

$newState = $status === 'accept' ? 'accepted' : 'denied';
if  ($newState === 'accepted' ) {
	//check current teams:
	$teams = $this->database->query(
		'SELECT team, is_primary FROM volunteer_teams WHERE volunteer = :volunteer',
		[':volunteer' => $volunteerId]
	);
	$foundPrimary = false;
	foreach ($teams as $team) {
		if ($team->is_primary) {
			$foundPrimary = true;
		}
	}
	
	if (!$foundPrimary) {
	?>
		<div class="backbone-page">
			<div class="page-title">
				<h1>Задаване на основен екип</h1>
				<h3><?php echo $volunteer->name . ' няма зададен основен екип. Моля, изберете един!' ?></h3>
			</div>
			<div class="pane full-width">
				<form method="post" id="primary-team-form">
					<input type="hidden" name="volunteer" value="<?php echo $volunteerId; ?>">
					<div class="input radio-options">
					<?php foreach ($teams as $team): ?>
						<div class="radio">
							<label for="team-<?php echo $team->team; ?>">
								<input type="radio" id="team-<?php echo $team->team; ?>" name="team" value="<?php echo $team->team; ?>" required>
								<?php echo $team->team;?>
							</label>
						</div>
					<?php endforeach; ?>
					</div>
					<div class="input">
						<button  type="submit">Запази</button>
					</div>
				</form>
			</div>
		</div>
		<script src="/assets/js/primary-team-form.js"></script>
		<?php
		return;
	}
}

$this->database->query("UPDATE volunteers SET status = :status, status_update_date = now() WHERE id = :id",
[':status' => $newState, ':id' => $volunteerId]
);

if ($newState === 'accepted') {
	//check user and, if active, update LDAP groups
	if ($volunteer->user) {
		$user = User::load($volunteer->user);
		if ($user->isActive()) {
			try {
				$activeConference = Conference::getActive();
				if ($activeConference) {
					_log('Adding accepted volunteer to LDAP groups: ' . $user->getUsername() . ' for conference ' . $activeConference->getSlug());
					$user->addToLdapGroups($activeConference->getSlug());
					_log('Successfully added accepted volunteer to LDAP groups: ' . $user->getUsername());
				} else {
					_log('No active conference found when adding accepted volunteer to LDAP groups: ' . $user->getUsername(), LOG_WARNING);
				}
				sendActivationMail($user->getEmail(), $activeConference->getTitle(), $user->getName());
				header('Location: /backbone/volunteers');
				exit;
			} catch (Exception $e) {
				_log('Failed to add user to LDAP groups: ' . $user->getUsername() . ' - ' . $e->getMessage(), LOG_ERR);
				echo "<h3 class='login-error'>Volunteer Accepted! Error during LDAP allocation!</h3>";
				exit;
			}
		}
		_log('Inactive user accepted as volunteer: ' . $user->getUsername() . ' (' . $user->getEmail() . ')', LOG_WARNING);
		echo "<h3 class='login-error'>Volunteer Accepted! User is not active!</h3>";
	} else {
		_log('Accepted volunteer with no linked user: ' . $volunteer->name . ' (' . $volunteer->email . ')', LOG_WARNING);
		echo "<h3 class='login-error'>Volunteer Accepted! No linked user detected!</h3>";
	}
}


function sendActivationMail($to, $confTitle, $name): void
{
	$subject = "Активиране на доброволчески акаунт [".$confTitle."]";
	$message = <<<EOT
Здравейте и добре дошли {$name}!

Радваме се, че проявявате желание да помогнете да направим заедно най-голямото събитие за Open Source в България (и не само:))!

Няколко неща, които ще са ви полезни, за да се включите в комуникацията на събитието и на екипа, който сте си избрали:

website: https://openfest.org

mailing list: team@openfest.org - тук ще бъдете автоматично добавени, пишем анонси и ако някой има въпроси или проблеми, важно е да го следите

регистрирате се тук: https://auth.openfest.org/realms/openfest/account/ - Single Sign On акаунт за (почти) всичко около openfest

matrix:    https://chat.openfest.org - основното място за комуникация общо и по екипи (mobile app: Element)
git:       https://git.openfest.org - git repository
nextcloud: https://nc.openfest.org - тук са файловете, които не са в git, документи, медия и т.н.

социални медии - чувствайте се свободни да разпространявате информацията за събитието на приятели, познати и всички останали!
twitter:   https://twitter.com/openfestbg
facebook:  https://www.facebook.com/OpenFestBulgaria
instagram: https://www.instagram.com/OpenFestBulgaria
linkedin:  https://bg.linkedin.com/company/openfest-bulgaria
mastodon:  https://mastodon.social/@openfest

youtube:   https://www.youtube.com/@openfestbulgaria

Първото нещо, което е хубаво да направите, след като се регистрирате и влезете в matrix, е да пишете в канала на екипа си.
Ако имате въпроси или проблеми, било то технически или каквито и да е други, можете да пишете на Координатора на доброволците или в General канала в matrix.

Следващо важно е Генералният Инструктаж.
Разказваме всякаква много полезна информация за събитието, ориентиране в мястото на което ще бъдем, зали, организация, обща информация и събиране на живо по екипи ако още не се е случило (в зависимост от екипа).
Присъствието ви е важно, за да знаете какво и как :)

Следете matrix и пощенския списък team@openfest.org за повече информация.

Приятно доброволстване!
Екипът на Openfest
EOT;

	$headers = "From: no-reply@{$_SERVER['HTTP_HOST']}\r\nReply-To: no-reply@{$_SERVER['HTTP_HOST']}\r\n";
	if (!mail($to, $subject, $message, $headers)) {
			    _log("Failed to send volunteer activation email to: " . $to, LOG_ERR);
		echo "<h3 class='login-error'>Грешка при изпращане на имейл. Моля, опитайте по-късно.</h3>";
	}
	_log('Volunteer activation email sent to: ' . $to);
	
	
}