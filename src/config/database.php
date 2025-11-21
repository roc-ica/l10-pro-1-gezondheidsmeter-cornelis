<?php

class Database
{
    /**
	 * @return \PDO
	 * @throws \Exception Wanneer de database connectie mislukt.
	 */
	private static $pdo = null;

	public static function getConnection(): \PDO
	{
		if (self::$pdo instanceof \PDO) {
			return self::$pdo;
		}

		$host = getenv('DB_HOST') ?: '127.0.0.1';
		$db   = getenv('DB_NAME') ?: 'gezondheidsmeter';
		$user = getenv('DB_USER') ?: 'root';
		$pass = getenv('DB_PASS') ?: '';
		$charset = 'utf8mb4';

		$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

		$options = [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
		];

		try {
			self::$pdo = new \PDO($dsn, $user, $pass, $options);
			return self::$pdo;
		} catch (\PDOException $e) {
			// In productie wil je dit loggen in plaats van direct de fout tonen.
			throw new \Exception('Database connectie mislukt: ' . $e->getMessage());
		}
	}
}