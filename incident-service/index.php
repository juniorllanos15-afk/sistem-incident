<?php
header('Content-Type: application/json');
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'models/Incident.php';
require_once 'repositories/IncidentRepositoryInterface.php';
require_once 'repositories/PDOIncidentRepository.php';
// require_once 'repositories/MemoryIncidentRepository.php'; // Descomenta esta línea si usas MemoryIncidentRepository
require_once 'controllers/IncidentController.php';

// Importar Observadores
require_once 'observer/LogIncidentObserver.php';
require_once 'observer/EmailIncidentObserver.php';

$incidentRepo = new PDOIncidentRepository($pdo);
// $incidentRepo = new MemoryIncidentRepository(); // Descomenta esta línea si usas MemoryIncidentRepository

// Adjuntar observadores
$incidentRepo->attach(new LogIncidentObserver());
$incidentRepo->attach(new EmailIncidentObserver());

$controller = new IncidentController($incidentRepo);

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
    case 'changeState':
        $controller->changeState();
        break;
    case 'details':
        $controller->getDetails();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found or invalid action in Category Microservice']);
        break;
}
?>