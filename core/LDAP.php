<?php

class LDAP
{
	const userAttributes = [
		'uid',
		'mail',
		'givenname',
		'sn',
		'cn',
		'dn',
		'memberof',
	];
	const groupAttributes = [
		'cn',
		'description',
		'dn',
		'member',
	];
	const userObjectClasses = [
		'inetOrgPerson',
		'organizationalPerson',
		'person',
	];
	const groupObjectClasses = [
		'groupOfNames',
		'inetLocalMailRecipient',
	];
	private string $server;
	private string $baseUserDN;
	private string $groupsDN;
	private string $volunteersDN;
	private string $bindDN;
	private string $bindPassword;
	private mixed $ds;

	/**
	 * @throws Exception
	 */
	public function __construct($server, $baseUserDN, $baseGroupDN, $bindDN, $bindPassword)
	{
		$this->server = $server;
		$this->baseUserDN = $baseUserDN;
		$this->groupsDN = 'ou=Volunteers,' . $baseGroupDN;
		$this->volunteersDN = 'ou=Volunteers,' . $this->baseUserDN;
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

	/**
	 * @throws Exception
	 */
	public function connect()
	{
		$this->ds = NULL;
		if (!($ds = $this->bind($this->bindDN, $this->bindPassword))) {
			throw new Exception("Could not bind to LDAP server with DN: " . $this->bindDN);
		}
		$this->ds = $ds;
		return $ds;
	}

	/**
	 * @throws Exception
	 */
	public function getEntries($dn, $filter, $type): array
	{
		$attributes = match ($type) {
			'user' => self::userAttributes,
			'group' => self::groupAttributes,
			default => throw new Exception("Unknown LDAP entry type: " . json_encode($type)),
		};

		$sr = ldap_search($this->ds, $dn, $filter, $attributes);
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
			$result[] = match ($type) {
				'user' => $this->convertToUserObject($entry),
				'group' => $this->convertToGroupObject($entry),
				default => throw new Exception("Unknown LDAP entry type: " . $type),
			};
		}
		return $result;

	}

	/**
	 * @throws Exception
	 */
	public function getUser($username)
	{
		$username = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
		$entries = $this->getEntries(
			$this->baseUserDN,
			'(&(objectClass=person)(|(uid=' . $username . ')(mail=' . $username . ')(maillocaladdress=' . $username . ')))',
			'user'
		);

		return array_shift($entries);

	}

	/**
	 * @throws Exception
	 */
	public function testBind(string|stdClass $user, string $password): bool
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

	/**
	 * @throws Exception
	 */
	public function bind($dn, $password)
	{
		$ds = ldap_connect($this->server);
		if (!$ds) {
			$msg = "LDAP connect failed to server: " . $this->server;
			_log($msg);
			throw new Exception($msg);
		}
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
//		if (!ldap_start_tls($ds)) {
//			$msg = "Could not start TLS on LDAP server: " . $this->server;
//			_log($msg);
//			throw new Exception($msg);
//		}
		if (!ldap_bind($ds, $dn, $password)) {
			$msg = "Could not bind to LDAP server with DN: " . $dn;
			_log($msg);
			throw new Exception($msg);
		}

		return $ds;
	}

	/**
	 * Check if a user is a member of a specific group
	 * @param stdClass $ldapUser
	 * @param string|array $group
	 * @return bool
	 */
	public function isMember(stdClass $ldapUser, string|array $group): bool
	{
		if (empty($ldapUser->memberof) || !is_array($ldapUser->memberof)) {
			_log("User " . $ldapUser->uid . " has no group memberships.");
			return FALSE;
		}
		if (is_array($group)) {
			// If $group is an array, check if the user is a member of at least one of the groups
			foreach ($group as $g) {
				if ($this->isMember($ldapUser, $g)) {
					return TRUE;
				}
			}
			_log("Unable to find user " . $ldapUser->uid . " in any of the groups: " . implode(', ', $group));
			return FALSE; // User is not a member of any group in the array
		}

		_log('Looking for user ' . $ldapUser->uid . ' in group ' . $group);
		_log('User details: ' . print_r($ldapUser, TRUE));
		//single group check
		return in_array($group, $ldapUser->memberof, TRUE);
	}

	/**
	 * @throws Exception
	 */
	public function addUser($username, $password, $givenName, $sn, $mail): bool
	{
		$userDN = 'uid=' . ldap_escape($username, '', LDAP_ESCAPE_DN) . ',' . $this->volunteersDN;
		$entry = [
			'uid' => $username,
			'mail' => $mail,
			'givenName' => $givenName,
			'sn' => $sn,
			'cn' => $givenName . ' ' . $sn,
			'userPassword' => $password,
			'objectClass' => array_merge(['top'], self::userObjectClasses),
		];

		dump($userDN, $entry, $this->ds);
		if (!ldap_add($this->ds, $userDN, $entry)) {
			$msg = "Could not add user: " . ldap_error($this->ds);
			_log($msg);
			throw new Exception($msg);
		}
		_log("LDAP user added: " . $username);
		return TRUE;

	}

	/**
	 * @throws Exception
	 */
	public function addOrganizationalUnit(string $ouName, string $description = ''): bool
	{
		$ouDN = 'ou=' . ldap_escape($ouName, '', LDAP_ESCAPE_DN) . ',' . $this->groupsDN;
		_log('Adding LDAP organizational unit: ' . $ouDN);
		$entry = [
			'objectClass' => ['top', 'organizationalUnit'],
			'ou' => $ouName,
			'description' => $description,
		];
		_log('Adding LDAP organizational unit entry: ' . print_r($entry, TRUE));

		if (!ldap_add($this->ds, $ouDN, $entry)) {
			$msg = "Could not add organizational unit: " . ldap_error($this->ds);
			_log($msg);
			throw new Exception($msg);
		}
		_log("LDAP organizational unit added: " . $ouName);
		return TRUE;


	}

	/**
	 * @throws Exception
	 */
	public function addGroup(string $groupName, $groupOU , string $description = ''): bool
	{
		$groupDN = 'cn=' . ldap_escape($groupName, '', LDAP_ESCAPE_DN) . ','. ($groupOU ? 'ou=' . $groupOU . ',' : '') . $this->groupsDN;
		_log('Adding LDAP group: ' . $groupDN);
		$entry = [
			'objectClass' => array_merge(['top'], self::groupObjectClasses),
			'cn' => $groupName,
			'description' => $description,
			'member' => ['cn=empty-membership-placeholder'], // Placeholder member, can be changed later
		];
		_log('Adding LDAP group entry: ' . print_r($entry, TRUE));

		if (!ldap_add($this->ds, $groupDN, $entry)) {
			$msg = "Could not add group: " . ldap_error($this->ds);
			_log($msg);
			throw new Exception($msg);
		}
		_log("LDAP group added: " . $groupName);
		return TRUE;
	}

	/**
	 * @throws Exception
	 */
	public function addMember(stdClass $ldapUser, string|array $group, $groupOU): bool
	{
		if (is_array($group)) {
			// If $group is an array, add the user to each group
			foreach ($group as $g) {
				$this->addMember($ldapUser, $g, $groupOU);
			}
			return TRUE; // Successfully added to all groups
		}

		_log("Adding LDAP member: " . $ldapUser ->uid . " to group: " . $group);
		//single group check
		if (!$this->isMember($ldapUser, $group)) {
			_log('Not a member, proceeding to add.');
			$groupDN = 'cn=' . ldap_escape($group, '', LDAP_ESCAPE_DN) . ',' . 'ou='. $groupOU . ',' . $this->groupsDN;
			if (!ldap_mod_add($this->ds, $groupDN, ['member' => $ldapUser->dn])) {
				$msg = "Could not add member $ldapUser->uid to group $group: " . ldap_error($this->ds);
				_log($msg);
				throw new Exception("Could not add member to group: " . ldap_error($this->ds));
			}
			_log("LDAP user " . $ldapUser->uid . " added to group: " . $group);
		} else {
			_log("LDAP user " . $ldapUser->uid . " is already a member of group: " . $group);
		}
		return TRUE;
	}

	/**
	 * @throws Exception
	 */
	public function removeMember(stdClass $ldapUser, string|array $group): bool
	{
		if (is_array($group)) {
			// If $group is an array, remove the user from each group
			foreach ($group as $g) {
				$this->removeMember($ldapUser, $g);
			}
			return TRUE; // Successfully removed from all groups
		}

		//single group check
		if ($this->isMember($ldapUser, $group)) {
			$groupDN = 'cn=' . ldap_escape($group, '', LDAP_ESCAPE_DN) . ',' . $this->groupsDN;
			if (!ldap_mod_del($this->ds, $groupDN, ['member' => $ldapUser->dn])) {
				$msg = "Could not remove member $ldapUser->uid from group $group: " . ldap_error($this->ds);
				_log($msg);
				throw new Exception("Could not remove member from group: " . ldap_error($this->ds));
			}
			_log("LDAP user " . $ldapUser->uid . " removed from group: " . $group);
		}
		return TRUE;
	}

	private function convertToUserObject($entry): object
	{
		$entryObj = new stdClass();
		foreach(self::userAttributes as $attribute) {
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

	private function convertToGroupObject($entry): object
	{
		$entryObj = new stdClass();
		foreach(self::groupAttributes as $attribute) {
			$entryObj->$attribute = NULL; // Initialize all attributes to NULL
		}

		foreach ($entry as $key => $value) {
			if (is_int($key) || $key === 'count') continue;

			if ($key === 'dn') {
				$entryObj->dn = $value;
			} else if ($key === 'member') {
				$members = [];
				for ($i = 0; $i < $entry['member']['count']; $i++) {
					if (preg_match('/uid=([^,]+)/i', $entry['member'][$i], $matches)) {
						$members[] = $matches[1];
					}
				}
				$entryObj->$key = $members;
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

	public function changePassword(stdClass $LdapUser, string $password)
	{
		$entry = [
			'userPassword' => $password,
		];
		if (!ldap_mod_replace($this->ds, $LdapUser->dn, $entry)) {
			$msg = "Could not change password for user $LdapUser->uid: " . ldap_error($this->ds);
			_log($msg);
			throw new Exception("Could not change password: " . ldap_error($this->ds));
		}
	}
}