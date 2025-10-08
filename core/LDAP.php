<?php

class LDAP
{
	const attributes = [
		'uid',
		'mail',
		'givenname',
		'sn',
		'cn',
		'dn',
		'memberof',
	];
	private string $server;
	private string $baseDN;
	private string $groupsDN;
	private string $volunteersDN;
	private string $bindDN;
	private string $bindPassword;
	private mixed $ds;

	public function __construct($server, $baseDN, $bindDN, $bindPassword)
	{
		$this->server = $server;
		$this->baseDN = $baseDN;
		$this->groupsDN = 'ou=Groups,' . $baseDN;
		$this->volunteersDN = 'ou=Volunteers,' . $this->groupsDN;
		$this->bindDN = $bindDN;
		$this->bindPassword = $bindPassword;
		$this->connect();
	}

	public function __destruct()
	{
		if ($this->ds) {
			ldap_unbind($this->ds);
			$this->ds = NULL;
		}
	}

	public function connect()
	{
		$this->ds = NULL;
		if (!($ds = $this->bind($this->bindDN, $this->bindPassword))) {
			throw new Exception("Could not bind to LDAP server with DN: " . $this->bindDN);
		}
		$this->ds = $ds;
		return $ds;
	}

	public function getEntries($filter)
	{

		$sr = ldap_search($this->ds, $this->baseDN, $filter, self::attributes);
		if (!$sr) {
			throw new Exception("LDAP search failed: " . ldap_error($this->ds));
		}
		$entries = ldap_get_entries($this->ds, $sr);
		if ($entries === FALSE) {
			throw new Exception("Could not get entries from LDAP search: " . ldap_error($this->ds));
		}
		if ($entries['count'] === 0) {
			return [];
		}
		$result = [];
		foreach ($entries as $entry) {
			if (!is_array($entry) || !isset($entry['dn'])) {
				continue; // Skip non-array entries or entries without 'dn'
			}
			$result[] = $this->convertToObject($entry);
		}
		return $result;

	}

	public function getUser($username)
	{
		$username = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
		$entries = $this->getEntries(
			'(&(objectClass=person)(|(uid=' . $username . ')(mail=' . $username . ')(maillocaladdress=' . $username . ')))'
		);

		return array_shift($entries);

	}

	public function testBind(string|stdClass $user, string $password)
	{
		if (is_string($user)) {
			$user = $this->getUser($user);
		}
		if (!$user) {
			return FALSE;
		}
		$ds = $this->bind($user->dn, $password);
		if ($ds) {
			ldap_close($ds);
		}
		return !!$ds; // bool

	}

	public function bind($dn, $password)
	{
		$ds = ldap_connect($this->server);
		if (!$ds) {
			throw new Exception("Could not connect to LDAP server: " . $this->server);
		}
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		if (!ldap_bind($ds, $dn, $password)) {
			throw new Exception("Could not bind to LDAP server with DN: " . $dn);
		}

		return $ds;
	}

	/**
	 * Check if a user is a member of a specific group
	 * @param stdClass $ldapUser
	 * @param string $group
	 * @return bool
	 */
	public function isMember(stdClass $ldapUser, string|array $group): bool
	{
		if (is_array($group)) {
			// If $group is an array, check if the user is a member of any of the groups
			foreach ($group as $g) {
				if ($this->isMember($ldapUser, $g)) {
					return TRUE;
				}
			}
			return FALSE; // User is not a member of any group in the array
		}

		//single group check
		return in_array($group, $ldapUser->memberof, TRUE);
	}

	public function addGroup(string $groupName, string $description = ''): bool
	{
		$groupDN = 'cn=' . ldap_escape($groupName, '', LDAP_ESCAPE_DN) . ',' . $this->groupsDN;
		$entry = [
			'objectClass' => ['top', 'groupOfNames'],
			'cn' => $groupName,
			'description' => $description,
			'member' => ['cn=dummy,dc=example,dc=com'], // Placeholder member, can be changed later
		];

		if (!ldap_add($this->ds, $groupDN, $entry)) {
			throw new Exception("Could not add group: " . ldap_error($this->ds));
		}
		return TRUE;
	}

	public function addMember(stdClass $ldapUser, string|array $group): bool
	{
		if (is_array($group)) {
			// If $group is an array, add the user to each group
			foreach ($group as $g) {
				$this->addMember($ldapUser, $g);
			}
			return TRUE; // Successfully added to all groups
		}

		//single group check
		if (!$this->isMember($ldapUser, $group)) {
			$groupDN = 'cn=' . ldap_escape($group, '', LDAP_ESCAPE_DN) . ',' . $this->groupsDN;
			if (!ldap_mod_add($this->ds, $groupDN, ['member' => $ldapUser->dn])) {
				throw new Exception("Could not add member to group: " . ldap_error($this->ds));
			}
		}
		return TRUE;
	}

	private function convertToObject($entry): object
	{
		$entryObj = new stdClass();
		foreach(self::attributes as $attribute) {
			$entryObj->$attribute = NULL; // Initialize all attributes to NULL
		}

		foreach ($entry as $key => $value) {
			if (is_int($key) || $key === 'count') continue;

			if ($key === 'dn') {
				$entryObj->dn = $value;
			} else if ($key === 'memberof') {
				$groups = [];
				for ($i = 0; $i < $entry['memberof']['count']; $i++) {
					if (preg_match('/cn=([^,]+)/i', $entry['memberof'][$i], $matches)) {
						$groups[] = $matches[1];
					}
				}
				$entryObj->$key = $groups;
			} else {
				if (isset($value['count'])) {
					if ($value['count'] === 1) {
						$entryObj->$key = $value[0];
					} else {
						$entryObj->$key = array_slice($value, 0, $value['count']);
					}
				}
			}
		}

		return $entryObj;
	}
}