<?php
class CategoryController {
    private $categoryModel;

    public function __construct($categoryModel) {
        $this->categoryModel = $categoryModel;
    }

    public function index() {
        $categories = $this->categoryModel->getAllActive();
        echo json_encode(['success' => true, 'data' => $categories]);
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['name']) && !empty(trim($data['name']))) {
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : '';

            $insertedId = $this->categoryModel->create($name, $description);

            if ($insertedId) {
                echo json_encode(['success' => true, 'id' => $insertedId, 'message' => 'Category created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create category']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id']) && isset($data['name']) && !empty(trim($data['name']))) {
            $id = intval($data['id']);
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : '';

            $success = $this->categoryModel->update($id, $name, $description);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update category']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID and name are required']);
        }
    }

    public function destroy() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            $id = intval($data['id']);
            
            $success = $this->categoryModel->disable($id);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Category disabled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to disable category']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
        }
    }
}
?>
