<?php
class RolController {
    private $rolModel;

    public function __construct($rolModel) {
        $this->rolModel = $rolModel;
    }

    public function index() {
        $roles = $this->rolModel->getAllActive();
        echo json_encode(['success' => true, 'data' => $roles]);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['name']) && !empty(trim($data['name']))) {
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : '';

            $insertedId = $this->rolModel->create($name, $description);

            if ($insertedId) {
                echo json_encode(['success' => true, 'id' => $insertedId, 'message' => 'Role created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create role']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Role name is required']);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['name']) && !empty(trim($data['name']))) {
            $id = intval($data['id']);
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : '';

            $success = $this->rolModel->update($id, $name, $description);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update role']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Role ID and name are required']);
        }
    }

    public function destroy() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            $id = intval($data['id']);
            
            $success = $this->rolModel->disable($id);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Role disabled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to disable role']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Role ID is required']);
        }
    }
}
?>
