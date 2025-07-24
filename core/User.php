<?php

class User
{

	private string $id;
	private string $email;
	private string $username;
	private string $name;
	private bool $isAdmin;

	public function __construct(string $id, string $email, string $username, string $name, bool $isAdmin = false)
	{
		$this->id = $id;
		$this->email = $email;
		$this->username = $username;
		$this->name = $name;
		$this->isAdmin = $isAdmin;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getEmail(): string
	{
		return $this->email;
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
}