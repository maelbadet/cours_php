<?php

final class DatabaseConnection
{
	private static ?PDO $instance = null;

	private function __construct()
	{
	}

	public static function getInstance(): PDO
	{
		if (self::$instance instanceof PDO) {
			return self::$instance;
		}

		$dsn = getenv('DB_DSN') ?: 'mysql:host=db;port=3306;dbname=cash;charset=utf8mb4';
		$user = getenv('DB_USER') ?: 'app_user';
		$pass = getenv('DB_PASSWORD') ?: 'app_pass';

		try {
			self::$instance = new PDO($dsn, $user, $pass, [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]);
		} catch (PDOException $e) {
			throw new RuntimeException('Erreur DB : ' . $e->getMessage(), (int)$e->getCode(), $e);
		}

		return self::$instance;
	}
}

function getPDO(): PDO
{
	return DatabaseConnection::getInstance();
}

?>
