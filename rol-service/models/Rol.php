<?php
class Rol {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllActive() {
        $stmt = $this->pdo->query("SELECT id, name, description, state, created_at FROM rol WHERE state = 1 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create($name, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO rol (name, description) VALUES (:name, :description)");
        if ($stmt->execute(['name' => $name, 'description' => $description])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $name, $description) {
        $stmt = $this->pdo->prepare("UPDATE rol SET name = :name, description = :description WHERE id = :id");
        return $stmt->execute(['name' => $name, 'description' => $description, 'id' => $id]);
    }

    public function disable($id) {
        // Soft delete by setting state to 0
        $stmt = $this->pdo->prepare("UPDATE rol SET state = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
