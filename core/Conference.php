<?php

class Conference
{
	private $slug;
	private $title;
	private $description;
	private $startDate;
	private $endDate;
	private $location;
	private $regOpen;
	private $regClose;


	public function __construct($slug, $title, $description, $startDate, $endDate, $location, $regOpen, $regClose)
	{
		$this->slug = $slug;
		$this->title = $title;
		$this->description = $description;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
		$this->location = $location;
		$this->regOpen = $regOpen;
		$this->regClose = $regClose;
	}

	public static function getConferences(): array
	{
		$database = Database::getInstance();
		return $database->query(
			'SELECT * from conferences ORDER BY start_date DESC'
		);
	}
	public static function getActive(): ?Conference
	{
		$database = Database::getInstance();
		$result = $database->query(
			'SELECT * from conferences WHERE registration_open <= NOW() AND registration_close >= NOW() ORDER BY start_date DESC LIMIT 1'
		);
		if ($result) {
			$entry = $result[0];
			return new Conference(
				$entry->slug,
				$entry->title,
				$entry->description,
				$entry->start_date,
				$entry->end_date,
				$entry->location,
				$entry->registration_open,
				$entry->registration_close
			);
		}
		return null;
	}

	public function toObject(): object
	{
		return (object)[
			'slug' => $this->slug,
			'title' => $this->title,
			'description' => $this->description,
			'start_date' => $this->startDate,
			'end_date' => $this->endDate,
			'location' => $this->location,
			'registration_open' => $this->regOpen,
			'registration_close' => $this->regClose,
		];
	}

	/**
	 * @return mixed
	 */
	public function getSlug()
	{
		return $this->slug;
	}

	/**
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return mixed
	 */
	public function getDescription()
	{
		return $this->description;
	}
}