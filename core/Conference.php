<?php

class Conference
{
	public static function getConferences(): array
	{
		$database = Database::getInstance();
		return $database->query(
			'SELECT * from conferences ORDER BY start_date DESC'
		);
	}
}