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
}