<?php

class Database
{
	private $pdo;

	public function __construct()
	{
		$dsn = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT;
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_EMULATE_PREPARES => false,    //security against SQL injection (based on internet research)
		];

		try {
			$this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
		} catch (PDOException $e) {
			// Handle connection error
			echo 'Database connection failed: ' . $e->getMessage();
			exit;
		}
	}
	 /**
     * Executes a prepared statement and returns the results.
     * Use this for SELECT queries.
     *
     * @param string $sql    The SQL query.
     * @param array  $params Optional array of parameters for the prepared statement.
     * @return array An array of fetched rows.
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

	/**
     * Executes a prepared statement for INSERT, UPDATE, DELETE queries.
     *
     * @param string $sql    The SQL query.
     * @param array  $params Optional array of parameters for the prepared statement.
     * @return int The number of affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
	/**
     * Returns the ID of the last inserted row.
     *
     * @return string The ID of the last inserted row.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Returns the PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}