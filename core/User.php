<?php

class User
{

	private string $id;
	private string $email;
	private string $username;
	private string $name;
	private ?string $phone;
	private bool $isActive;
	private bool $isAdmin;

	public function __construct(string $id, string $email, string $username, string $name, ?string $phone, bool $isAdmin = false, bool $isActive = true)
	{
		$this->id = $id;
		$this->email = $email;
		$this->username = $username;
		$this->name = $name;
		$this->phone = $phone;
		$this->isAdmin = $isAdmin;
		$this->isActive = $isActive;
	}
	
	public static function generateToken($data): string
	{
		//sha512 uuid to generate a verification token
		$verificationToken = hash('sha512', uuid() . $data . time());
		return substr($verificationToken, 0, 29) . '-' . substr($verificationToken, -30);
		
	}
	
	public static function load(object|string $user): ?User
	{
		if (is_string($user)) {
			$user = Database::getInstance()->query(
				'SELECT * FROM users WHERE uid = :uid',
				[':uid' => $user]
			);
			$user = $user[0] ?? null;
		}
		
		if (empty($user)) {
			return null;
		}
		return new self(
			$user->uid,
			$user->email,
			$user->username ?? '',
			$user->name,
			$user->phone,
			(bool)$user->admin,
			(bool)$user->active
		);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function isActive(): bool
	{
		return $this->isActive;
	}
	public function isAdmin(): bool
	{
		return $this->isAdmin;
	}
	public function getUsername(): string
	{
		return $this->username;
	}
	public function getName(): string
	{
		return $this->name;
	}

	public function getPhone(): ?string
	{
		return $this->phone;
	}


	public function reload(): void
	{
		$res = Database::getInstance()->query(
			'SELECT * FROM users WHERE uid = :uid',
			[':uid' => $this->id]
		);
		if (empty($res)) {
			throw new Exception('User not found');
		}
		
		$user = $res[0];
		$this->email = $user->email;
		$this->username = $user->username ?? '';
		$this->name = $user->name;
		$this->phone = $user->phone;
		$this->isAdmin = (bool)$user->admin;
		$this->isActive = (bool)$user->active;
		
	}
	

	/**
	 * @throws Exception
	 */
	public function addToLdapGroups($conference): void
	{
		_log('Adding user ' . $this->username . ' to LDAP groups for conference ' . $conference);
		$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
		$teams = Database::getInstance()->query(
			'SELECT t.slug, t.conference FROM volunteer_teams vt LEFT JOIN teams t ON (vt.team = t.slug AND vt.conference = t.conference) LEFT JOIN volunteers v ON (vt.volunteer = v.id) LEFT JOIN users u ON (v."user" = u.uid) WHERE u.uid= :uid and t.conference = :conference',
			[':uid' => $this->id, ':conference' => $conference]
		);
		$ldapUser = $this->getLdapUser();
		if (!$ldapUser) {
			throw new Exception('User not found in LDAP');
		}
		foreach ($teams as $team) {
			$ldap->addMember($ldapUser, $team->slug, $team->conference);
		}
	}

	public function toObject(): object
	{
		return (object)[
			'uid' => $this->id,
			'email' => $this->email,
			'username' => $this->username,
			'name' => $this->name,
			'phone' => $this->phone,
			'admin' => $this->isAdmin,
			'active' => $this->isActive,
		];

	}
	
	/**
	 * Sets a token for the user with a specified expiry interval.
	 * @param string $token - the token to set for the user
	 * @param string $validity - the interval for the token expiry (e.g. '1
	 *     day', '2 hours')
	 * @return void
	 */
	public function setToken(string $token, string $validity): void
	{
		$end = strtotime('now + ' . $validity); // validate the validity format
		$durInSec = $end - time();
		
		$sql = 'UPDATE users SET token = :token, token_expiry = now() + interval \'' .$durInSec .' seconds\' WHERE uid = :uid';
		Database::getInstance()->query($sql, [
			':token' => $token,
			':uid' => $this->id
		]);
		
	}
	
	public function resetToken(): void
	{
		$sql = 'UPDATE users SET token = NULL, token_expiry = NULL WHERE uid = :uid';
		Database::getInstance()->query($sql, [
			':uid' => $this->id
		]);
	}
	
	public function setPassword(mixed $password): bool
	{
		_log('Setting password for user ' . $this->username);
		
		try {
			$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
			$ldap->changePassword($this->getLdapUser(), $password);
		} catch (Exception $e) {
			_log('Failed to change password for user ' . $this->username . ': ' . $e->getMessage(), LOG_ERR);
			return false;
		}
		return true;
	}
	
	/**
	 * @throws Exception
	 */
	private function getLdapUser()
	{
		$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
		return $ldap->getUser($this->username);

	}

}