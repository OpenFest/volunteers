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

	public function getPhone()
	{
		return $this->phone;

	}


	public static function load(object|string $user)
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
		return new User(
			$user->uid,
			$user->email,
			$user->username ?? '',
			$user->name,
			$user->phone,
			(bool)$user->admin,
			(bool)$user->active
		);
	}

	public function addToLdapGroups($conference)
	{
		_log('Adding user ' . $this->username . ' to LDAP groups for conference ' . $conference);
		$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
		$teams = Database::getInstance()->query(
			'SELECT t.slug, t.conference FROM volunteer_teams vt LEFT JOIN teams t ON (vt.team = t.slug AND vt.conference = t.conference) LEFT JOIN volunteers v ON (vt.volunteer = v.id) LEFT JOIN users u ON (v."user" = u.uid) WHERE u.uid= :uid and t.conference = :conference',
			[':uid' => $this->id, ':conference' => $conference]
		);
		foreach ($teams as $team) {
			$ldap->addMember($this->getLdapUser(), $team->slug, $team->conference);
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

	private function getLdapUser()
	{
		$ldap = new LDAP(LDAP_SERVER, LDAP_BASE_USERS_DN, LDAP_BASE_GROUPS_DN, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
		return $ldap->getUser($this->username);

	}
}