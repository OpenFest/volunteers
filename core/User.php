<?php

class User
{

	private $id;
	private $email;
	private $isAdmin;

	public function __construct($id, $email, $isAdmin = false)
	{
		$this->id = $id;
		$this->email = $email;
		$this->isAdmin = $isAdmin;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function isAdmin()
	{
		return $this->isAdmin;
	}
}