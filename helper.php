<?php

use JetBrains\PhpStorm\NoReturn;

if (!function_exists("dump")) {
	function dump(...$args): void
	{
		echo '<pre>'.PHP_EOL;
		foreach ($args as $arg) {
			var_dump($arg);
		}
		echo '</pre>';
	}
}

if (!function_exists("dd")) {
	#[NoReturn]
	function dd(...$args): void
	{
		dump(...$args);
		exit;
	}
}

if (!function_exists("uuid")) {
	function uuid(): string
	{
		if (function_exists('com_create_guid')) {
			return trim(com_create_guid(), '{}');
		} else {
			$chars = md5(uniqid(mt_rand(), true));
			return sprintf('%s-%s-%s-%s-%s',
				substr($chars, 0, 8),
				substr($chars, 8, 4),
				substr($chars, 12, 4),
				substr($chars, 16, 4),
				substr($chars, 20, 12)
			);
		}
	}
}

if (!function_exists('_log')) {
	// Log a message to the syslog
	function _log($message, $level = LOG_INFO): void
	{
		if (function_exists('syslog')) {
			openlog('vol', LOG_PID | LOG_PERROR, LOG_USER);
			syslog($level, $message);
			closelog();
		} else {
			error_log($message);
		}
	}
}

if (!function_exists('checkAuth')) {
	function checkAuth($reverse = FALSE): void
	{
		if ($reverse) {
			//allow access only if not logged in (login, pass-reset, etc.), otherwise redirect to profile or backbone
			if (isset($_SESSION['user'])) {
				header('Location: /'. ($_SESSION['user']->isAdmin() ? 'backbone' : 'profile'));
				exit;
			}
			return;
		}
		if (!isset($_SESSION['user'])) {
			header('Location: /login');
			exit;
		}
	}
}

if (!function_exists('checkAdmin')) {
	function checkAdmin(): void
	{
		if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
			header('Location: /login');
			exit;
		}
	}
}


