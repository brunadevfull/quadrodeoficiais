<?php
// Cabeçalhos para permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Se for uma requisição OPTIONS, apenas retornar com os cabeçalhos CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurações
$apiHost = '10.1.129.46:5001';
$endpoint = '/api/duty-officers';
$url = "http://$apiHost$endpoint";

// Obter conteúdo da requisição
$requestBody = file_get_contents('php://input');
$method = $_SERVER['REQUEST_METHOD'];

// Inicializar cURL
$ch = curl_init();

// Configurar opções do cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

if ($method === 'PUT' || $method === 'POST') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}

// Executar requisição
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Verificar erros
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na comunicação com a API: ' . curl_error($ch)
    ]);
    exit();
}

// Fechar cURL
curl_close($ch);

// Definir código de status HTTP
http_response_code($httpCode);

// Definir cabeçalho de tipo de conteúdo
header('Content-Type: application/json');

// Retornar a resposta
echo $response;
?>