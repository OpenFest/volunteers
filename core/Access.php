<?php

/**
 * Module-based access to parts of the backbone.
 * A user can hold many modules, a module can be held by many users; admins hold all of them.
 */
class Access
{
	/** module key => [label, backbone path] */
	public const MODULES = [
		'shirts' => ['Shirts', '/backbone/shirts'],
	];

	public static function isModule(string $module): bool
	{
		return isset(self::MODULES[$module]);
	}

	/** @return string[] module keys granted to the user (admin implicitness not included) */
	public static function forUser(string $uid): array
	{
		$rows = Database::getInstance()->query(
			'SELECT module FROM user_access WHERE "user" = :uid',
			[':uid' => $uid]
		);
		return array_values(array_filter(
			array_map(fn($r) => $r->module, $rows),
			[self::class, 'isModule']
		));
	}

	/** @return object[] users explicitly granted the module (name, email) */
	public static function usersFor(string $module): array
	{
		return Database::getInstance()->query(
			'SELECT u.uid, u.name, u.email FROM user_access ua JOIN users u ON ua.user = u.uid WHERE ua.module = :module ORDER BY u.name',
			[':module' => $module]
		);
	}

	public static function grant(string $uid, string $module): void
	{
		if (!self::isModule($module)) {
			return;
		}
		Database::getInstance()->execute(
			'INSERT INTO user_access ("user", module) VALUES (:uid, :module) ON CONFLICT DO NOTHING',
			[':uid' => $uid, ':module' => $module]
		);
	}

	public static function revoke(string $uid, string $module): void
	{
		Database::getInstance()->execute(
			'DELETE FROM user_access WHERE "user" = :uid AND module = :module',
			[':uid' => $uid, ':module' => $module]
		);
	}

	/** Replace the user's modules with the given list (unknown modules ignored). */
	public static function sync(string $uid, array $modules): void
	{
		$modules = array_filter($modules, fn($m) => is_string($m) && self::isModule($m));
		$current = self::forUser($uid);
		foreach (array_diff($current, $modules) as $module) {
			self::revoke($uid, $module);
		}
		foreach (array_diff($modules, $current) as $module) {
			self::grant($uid, $module);
		}
	}
}
