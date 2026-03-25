<?php

use App\Lib\Http\Request;
use App\Lib\Http\Router;
use App\Lib\Security\CsrfGuard;

require_once __DIR__ . '/../vendor/autoload.php';

$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($requestOrigin !== null && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$csrfGuard = new CsrfGuard($allowedOrigins);
$csrfError = $csrfGuard->validate($_SERVER);
if ($csrfError !== null) {
    header('Content-Type: application/json');
    http_response_code((int) ($csrfError['status'] ?? 403));
    echo json_encode([
        'error' => (string) ($csrfError['error'] ?? 'forbidden'),
    ]);
    exit();
}

try {
    
    $request = new Request();
    $response = Router::route($request);

    header($response->getHeadersAsString());
    http_response_code($response->getStatus());
    echo $response->getContent();
    exit();
} catch(\Exception $e) {
    echo $e->getMessage();
}
