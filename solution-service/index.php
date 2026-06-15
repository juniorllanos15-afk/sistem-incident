<?php
// solution-service/index.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'models/Solution.php';
require_once 'controllers/SolutionController.php';

$model = new Solution($pdo);
$controller = new SolutionController($model);

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'create':
        $controller->store();
        break;
    case 'list':
        $controller->list();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found in Solution Service']);
        break;
}
?>
