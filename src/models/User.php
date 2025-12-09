<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    // public properties to make controllers/simple views easier to read
    public $id;
    public $username;
    public $email;
    public $is_admin;
    public $display_name;
    public $birthdate;
    public $gender;
    public $created_at;
    public $last_login;
    public $is_active;
    public $block_reason;
    public $deleted_at;

    protected $pdo;


    public function __construct($data = [])
    {
        $this->pdo = Database::getConnection();

        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->username = $data['username'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->is_admin = isset($data['is_admin']) ? (int) $data['is_admin'] : null;
            $this->display_name = $data['display_name'] ?? null;
            $this->birthdate = $data['birthdate'] ?? null;
            $this->gender = $data['gender'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
            $this->last_login = $data['last_login'] ?? null;
            $this->is_active = isset($data['is_active']) ? (int) $data['is_active'] : null;
            $this->block_reason = $data['block_reason'] ?? null;
            $this->deleted_at = $data['deleted_at'] ?? null;
        }
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public static function findByUsernameStatic(string $username): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public static function findByIdStatic(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function update(array $data): array
    {
        if (empty($this->id)) {
            return ['success' => false, 'message' => 'Geen gebruiker geselecteerd om te updaten.'];
        }

        $fields = [];
        $values = [];

        if (isset($data['username'])) {
            $fields[] = 'username = ?';
            $values[] = trim($data['username']);
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = trim($data['email']);
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (isset($data['is_admin'])) {
            $fields[] = 'is_admin = ?';
            $values[] = (int) $data['is_admin'];
        }
        if (isset($data['display_name'])) {
            $fields[] = 'display_name = ?';
            $values[] = $data['display_name'];
        }
        if (isset($data['birthdate'])) {
            $fields[] = 'birthdate = ?';
            $values[] = $data['birthdate'];
        }
        if (isset($data['gender'])) {
            $fields[] = 'gender = ?';
            $values[] = $data['gender'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $values[] = (int) $data['is_active'];
        }
        if (isset($data['block_reason'])) {
            $fields[] = 'block_reason = ?';
            $values[] = $data['block_reason'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'Geen velden opgegeven om te updaten.'];
        }

        $values[] = $this->id; // for WHERE
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return ['success' => true, 'message' => 'Gebruiker bijgewerkt.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database fout: ' . $e->getMessage()];
        }
    }

    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM users WHERE deleted_at IS NULL ORDER BY id DESC');
        $rows = $stmt->fetchAll();
        $results = [];
        foreach ($rows as $r) {
            $results[] = new self($r);
        }
        return $results;
    }

    public static function register(string $username, string $email, string $password): array
    {
        $username = trim($username);
        $email = trim($email);

        if ($username === '' || $email === '' || $password === '') {
            return ['success' => false, 'id' => null, 'message' => 'Vul alle verplichte velden in.'];
        }

        // Check separately so we can give a clear message which field exists
        $usernameExists = self::usernameExists($username);
        $emailExists = self::exists($email);
        if ($usernameExists || $emailExists) {
            if ($usernameExists && $emailExists) {
                $msg = 'Gebruikersnaam en e-mail bestaan al.';
            } elseif ($usernameExists) {
                $msg = 'Gebruikersnaam bestaat al.';
            } else {
                $msg = 'E-mail bestaat al.';
            }
            return ['success' => false, 'id' => null, 'message' => $msg, 'errors' => ['username_exists' => $usernameExists, 'email_exists' => $emailExists]];
        }

        $pdo = Database::getConnection();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
        try {
            $stmt->execute([$username, $email, $password_hash]);
            $id = (int) $pdo->lastInsertId();
            return ['success' => true, 'id' => $id, 'message' => 'Gebruiker geregistreerd.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'id' => null, 'message' => 'Database fout: ' . $e->getMessage()];
        }
    }

    public static function authenticate(string $password, $username = null, $email = null): ?self
    {
        $pdo = Database::getConnection();

        $lookup = null;
        if (!empty($username)) {
            $lookup = $username;
        } elseif (!empty($email)) {
            $lookup = $email;
        }

        if ($lookup === null) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$lookup, $lookup]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if (!isset($row['password_hash']) || !password_verify($password, $row['password_hash'])) {
            return null;
        }

        // controleer of account actief is
        if (isset($row['is_active']) && (int) $row['is_active'] === 0) {
            return null;
        }

        // update last_login
        try {
            $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $upd->execute([$row['id']]);
            // reflect the updated last_login in the returned object
            $row['last_login'] = date('Y-m-d H:i:s');
        } catch (\PDOException $e) {
            // ignore update failure for login
        }

        return new self($row);
    }

    public static function login(string $usernameOrEmail, string $password): array
    {
        $user = self::authenticate($password, $usernameOrEmail, $usernameOrEmail);
        if (!$user) {
            return ['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord.', 'user' => null];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['email'] = $user->email;
        $_SESSION['birthdate'] = $user->birthdate;
        $_SESSION['gender'] = $user->gender;

        return ['success' => true, 'message' => 'Inloggen gelukt.', 'user' => $user];
    }

    public static function exists($email): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public static function usernameExists($username): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    public static function getAllUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE is_admin = 0 AND deleted_at IS NULL");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Soft delete a user by setting deleted_at timestamp
     */
    public function softDelete(): array
    {
        if (empty($this->id)) {
            return ['success' => false, 'message' => 'Geen gebruiker geselecteerd om te verwijderen.'];
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ?');
            $stmt->execute([$this->id]);
            $this->deleted_at = date('Y-m-d H:i:s');
            return ['success' => true, 'message' => 'Gebruiker verwijderd.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database fout: ' . $e->getMessage()];
        }
    }

    /**
     * Restore a soft-deleted user
     */
    public function restore(): array
    {
        if (empty($this->id)) {
            return ['success' => false, 'message' => 'Geen gebruiker geselecteerd om te herstellen.'];
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NULL WHERE id = ?');
            $stmt->execute([$this->id]);
            $this->deleted_at = null;
            return ['success' => true, 'message' => 'Gebruiker hersteld.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database fout: ' . $e->getMessage()];
        }
    }
}
