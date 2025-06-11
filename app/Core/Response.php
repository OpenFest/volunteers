<?php
namespace Core;

class Response {
	public static function send($data): void
	{
		echo $data;
	}
}
