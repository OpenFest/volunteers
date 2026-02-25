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

if (!function_exists('validatePasswordStrength')) {
	/**
	 * Validate password strength against security requirements
	 *
	 * @param string $password The password to validate
	 * @param array $options Optional configuration for password requirements
	 * @return array ['valid' => bool, 'errors' => array, 'strength' => string]
	 */
	function validatePasswordStrength(string $password, array $options = []): array
	{
		// Default options
		$minLength = $options['minLength'] ?? 8;
		$requireUppercase = $options['requireUppercase'] ?? true;
		$requireLowercase = $options['requireLowercase'] ?? true;
		$requireNumbers = $options['requireNumbers'] ?? true;
		$requireSpecialChars = $options['requireSpecialChars'] ?? true;
		
		$errors = [];
		$checks = [];
		
		// Check minimum length
		if (strlen($password) < $minLength) {
			$errors[] = "Паролата трябва да съдържа поне {$minLength} символа";
			$checks['length'] = false;
		} else {
			$checks['length'] = true;
		}
		
		// Check for uppercase letter
		if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
			$errors[] = "Паролата трябва да съдържа поне една главна буква";
			$checks['uppercase'] = false;
		} else {
			$checks['uppercase'] = true;
		}
		
		// Check for lowercase letter
		if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
			$errors[] = "Паролата трябва да съдържа поне една малка буква";
			$checks['lowercase'] = false;
		} else {
			$checks['lowercase'] = true;
		}
		
		// Check for number
		if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
			$errors[] = "Паролата трябва да съдържа поне една цифра";
			$checks['number'] = false;
		} else {
			$checks['number'] = true;
		}
		
		// Check for special character
		if ($requireSpecialChars && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
			$errors[] = "Паролата трябва да съдържа поне един специален символ (!@#$%^&*)";
			$checks['special'] = false;
		} else {
			$checks['special'] = true;
		}
		
		// Calculate strength
		$passedChecks = count(array_filter($checks));
		$totalChecks = count($checks);
		$strengthPercentage = ($passedChecks / $totalChecks) * 100;
		
		if ($strengthPercentage < 40) {
			$strength = 'weak';
		} elseif ($strengthPercentage < 70) {
			$strength = 'medium';
		} elseif ($strengthPercentage < 100) {
			$strength = 'good';
		} else {
			$strength = 'strong';
		}
		
		return [
			'valid' => empty($errors),
			'errors' => $errors,
			'strength' => $strength,
			'checks' => $checks
		];
	}
}


