<?php

function getPDO(): PDO
{
	static $pdo = null;

	if ($pdo instanceof PDO) {
		return $pdo;
	}

	$dsn = getenv('DB_DSN') ?: 'mysql:host=db;port=3306;dbname=cash;charset=utf8mb4';
	$user = getenv('DB_USER') ?: 'app_user';
	$pass = getenv('DB_PASSWORD') ?: 'app_pass';

	try {
		$pdo = new PDO($dsn, $user, $pass, [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);
	} catch (PDOException $e) {
		throw new RuntimeException('Erreur DB : ' . $e->getMessage(), (int)$e->getCode(), $e);
	}

	return $pdo;
}

?>
