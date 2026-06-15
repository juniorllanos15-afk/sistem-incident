<?php
// assignment-service/index.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'models/Assignment.php';
require_once 'controllers/AssignmentController.php';

$assignmentModel = new Assignment($pdo);
$controller = new AssignmentController($assignmentModel);

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'list':
        $controller->index();
        break;
    case 'create':
        $controller->store();
        break;
    case 'details':
        $controller->getDetails();
        break;
    case 'technician-assignments':
        $controller->listByTechnician();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found in Assignment Service']);
        break;
}
?>
