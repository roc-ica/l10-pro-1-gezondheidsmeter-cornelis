<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
	protected $pdo;

	public function __construct()
	{
		$this->pdo = Database::getConnection();
	}

	public function showRegister()
	{
		require __DIR__ . '/../views/auth/register.php';
	}

	public function showLogin(string $message = null)
	{
		// Make $message available to the included view
		require __DIR__ . '/../views/auth/login.php';
	}


	public function register(array $data): array
	{
		$username = trim($data['username'] ?? '');
		$email = trim($data['email'] ?? '');
		$password = $data['password'] ?? '';
		$passwordConfirm = $data['password_confirm'] ?? '';

		if (!$username || !$email || !$password) {
			return ['success' => false, 'message' => 'Vul alle velden in.', 'errors' => []];
		}

		// Check password confirmation
		if ($password !== $passwordConfirm) {
			return [
				'success' => false,
				'message' => 'Wachtwoorden komen niet overeen.',
				'errors' => ['password_confirm' => true]
			];
		}

		$res = User::register($username, $email, $password);
		return $res;
	}

	public function login(array $data): array
	{
		$username = trim($data['username'] ?? '');
		$password = $data['password'] ?? '';

		if (!$username || !$password) {
			return ['success' => false, 'message' => 'Vul gebruikersnaam en wachtwoord in.'];
		}

		// Gebruik User::login() om authenticatie + sessie centraal af te handelen
		$res = User::login($username, $password);
		return $res;
	}
}

