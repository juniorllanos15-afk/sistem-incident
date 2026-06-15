<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'models/User.php';
require_once 'controllers/UserController.php';

$userModel = new User($pdo);
$controller = new UserController($userModel);

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'list': $controller->index(); break;
    case 'create': $controller->store(); break;
    case 'update': $controller->update(); break;
    case 'disable': $controller->destroy(); break;
    case 'get': $controller->show(); break;
    case 'login': $controller->login(); break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found or invalid action in User Microservice']);
        break;
}
?>
