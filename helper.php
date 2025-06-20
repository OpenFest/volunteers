<?php

if (!function_exists("dump")) {
	function dump(...$args)
	{
		echo '<pre>'.PHP_EOL;
		foreach ($args as $arg) {
			var_dump($arg);
		}
		echo '</pre>';
	}
}

if (!function_exists("dd")) {
	function dd(...$args)
	{
		dump(...$args);
		exit;
	}
}

if (!function_exists("uuid")) {
	function uuid() {
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
