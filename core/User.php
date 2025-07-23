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

	/**
	 * @param string $server
	 * @param string $dn
	 * @param string $password
	 * @return false|resource
	 */
	public static function ldapShanoBind(string $server, string $dn, string $password)
	{
        $ds = ldap_connect($server);
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        $bind = ldap_bind($ds, $dn, $password);
        if (!$bind) {
			return false;
        }

        return $ds;
	}

	/**
	 * @param $username
	 * @param $password
	 * @return bool
	 */
	public static function ldapTestPassword($username, $password): bool
	{
		$matches = [...self::ldapGetUser($username)]; // TODO: assert length of 1, err out otherwise

		if (!($match = array_shift($matches))) {
			return false;
		}
		/** @var stdClass $match */
		$ds = self::ldapShanoBind(LDAP_SERVER, $match->dn, $password);
		if($ds) {
			ldap_close($ds);
		}

		return !!$ds; // bool

	}


	/**
	 * @property string $dn
	 * @param $username
	 * @return Generator
	 */
	public static function ldapGetUser($username): Generator
	{
		$username = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
		$entries = self::ldapGetEntries(
			'(&(objectClass=person)(|(uid='.$username.')(mail='.$username.')(mailLocalAddress='.$username.')))'
		);

		foreach ($entries as $entry) {
			if (isset($entry['uid'][0]) && isset($entry['mail'][0]) && isset($entry['cn'][0])) {
				$data = (object) [
					'cn' => $entry['cn'][0],
					'email' => $entry['mail'][0],
					'name' => $entry['givenname'][0] ?? 'N/A',
					'sirName' => $entry['sn'][0] ?? 'N/A',
					'localEmail' => $entry['maillocaladdress'][0] ?? 'N/A',
					'uid' => $entry['uid'][0],
					'dn' => $entry['dn'],
				];
				yield $data;
			}
		}

	}

	/**
	 * @param $filter
	 * @return array|false
	 */
	public static function ldapGetEntries($filter)
	{
		$ds = self::ldapShanoBind(LDAP_SERVER, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
		$search = ldap_search($ds, LDAP_BASE_DN, $filter);
		if ($search === false) {
			return false;
		}

		$results = ldap_get_entries($ds, $search);
		if ($results === false) {
			return false;
		}

		ldap_close($ds);

		return $results;
	}


}