<?php
// api-gateway/gateway.php
header('Content-Type: application/json');

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// A simple API Gateway to route requests to appropriate microservices
$service = isset($_GET['service']) ? $_GET['service'] : null;
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;

if (!$service || !$endpoint) {
    http_response_code(400);
    echo json_encode(['error' => 'Service and endpoint are required']);
    exit;
}

// Port Mapping for Microservices
$servicePorts = [
    'category' => 8001,
    'rol' => 8002,
    'user' => 8003,
    'incident' => 8004,
    'assignment' => 8005,
    'solution' => 8006
];

if (isset($servicePorts[$service])) {
    $port = $servicePorts[$service];

    // Reenviar parámetros GET adicionales (ej: id, buscar)
    $queryParams = $_GET;
    unset($queryParams['service'], $queryParams['endpoint']);
    $queryString = http_build_query($queryParams);

    $targetUrl = "http://localhost:$port/index.php?action=$endpoint" . ($queryString ? "&$queryString" : "");

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $method = $_SERVER['REQUEST_METHOD'];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Extraer y reenviar headers (esencial para enviar tokens como Authorization JWT)
    $requestHeaders = [];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_') {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            // Omitir Host para que cURL asigne el suyo correctamente a localhost
            if ($headerName !== 'Host') {
                $requestHeaders[] = "$headerName: $value";
            }
        }
    }

    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
        $inputData = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);

        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'application/json';
        $requestHeaders[] = "Content-Type: $contentType";
        $requestHeaders[] = 'Content-Length: ' . strlen($inputData);
    }

    // Aplicar los headers unificados a cURL
    if (!empty($requestHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'Gateway error connecting to microservice on port ' . $port . ': ' . curl_error($ch)]);
    } else {
        http_response_code($httpCode);
        echo $response;
    }

    curl_close($ch);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Service not found in API Gateway map']);
}
?>