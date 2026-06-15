<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT id, user_name, email, rol_id, state FROM users WHERE id = :id AND state = 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getAllActive() {
        $stmt = $this->pdo->query("SELECT id, user_name, email, rol_id, state, created_at FROM users WHERE state = 1 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create($userName, $password, $email, $rolId) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (user_name, password, email, rol_id) VALUES (:user_name, :password, :email, :rol_id)");
        if ($stmt->execute([
            'user_name' => $userName, 
            'password' => $hashedPassword, 
            'email' => $email, 
            'rol_id' => $rolId
        ])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $userName, $password, $email, $rolId) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET user_name = :user_name, password = :password, email = :email, rol_id = :rol_id WHERE id = :id");
            return $stmt->execute([
                'user_name' => $userName, 
                'password' => $hashedPassword, 
                'email' => $email, 
                'rol_id' => $rolId, 
                'id' => $id
            ]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE users SET user_name = :user_name, email = :email, rol_id = :rol_id WHERE id = :id");
            return $stmt->execute([
                'user_name' => $userName, 
                'email' => $email, 
                'rol_id' => $rolId, 
                'id' => $id
            ]);
        }
    }

    public function disable($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET state = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function login($email, $password) {
        // Fetch user matching email
        $stmt = $this->pdo->prepare("SELECT id, user_name, email, password, rol_id, state FROM users WHERE email = :email AND state = 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // If user exists and password is correct (using password_verify)
        if ($user && password_verify($password, $user['password'])) {
            // Remove the hash from the returned data for security
            unset($user['password']);
            return $user;
        }

        return false;
    }
}
?>
