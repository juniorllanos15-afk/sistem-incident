<?php
class UserController {
    private $userModel;

    public function __construct($userModel) {
        $this->userModel = $userModel;
    }

    public function index() {
        $users = $this->userModel->getAllActive();
        echo json_encode(['success' => true, 'data' => $users]);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!empty($data['user_name']) && !empty($data['password']) && !empty($data['email']) && !empty($data['rol_id'])) {
            $id = $this->userModel->create(trim($data['user_name']), trim($data['password']), trim($data['email']), intval($data['rol_id']));

            if ($id) {
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'User created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required']);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!empty($data['id']) && !empty($data['user_name']) && !empty($data['email']) && !empty($data['rol_id'])) {
            $password = isset($data['password']) ? trim($data['password']) : '';
            $success = $this->userModel->update(intval($data['id']), trim($data['user_name']), $password, trim($data['email']), intval($data['rol_id']));

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Required fields are missing']);
        }
    }

    public function destroy() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['id'])) {
            if ($this->userModel->disable(intval($data['id']))) {
                echo json_encode(['success' => true, 'message' => 'User disabled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to disable user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
        }
    }

    public function show() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid user ID is required']);
            return;
        }
        $user = $this->userModel->getById($id);
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!empty($data['email']) && !empty($data['password'])) {
            $user = $this->userModel->login(trim($data['email']), trim($data['password']));

            if ($user) {
                echo json_encode(['success' => true, 'message' => 'Login successful', 'data' => $user]);
            } else {
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Invalid email or password']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
        }
    }
}
?>
