<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'models/Rol.php';
require_once 'controllers/RolController.php';

$rolModel = new Rol($pdo);
$controller = new RolController($rolModel);

// Get the requested action/endpoint from the query string
$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'list':
        $controller->index();
        break;
    case 'create':
        $controller->store();
        break;
    case 'update':
        $controller->update();
        break;
    case 'disable':
        $controller->destroy();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found or invalid action in Rol Microservice']);
        break;
}
?>
