<?php
// Cabeçalhos para permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Se for uma requisição OPTIONS, apenas retornar com os cabeçalhos CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurações
$apiHost = '10.1.129.46:5001';
$endpoint = '/api/military-personnel';
$url = "http://$apiHost$endpoint";

// Encaminhar parâmetros de consulta, se houver
if (!empty($_SERVER['QUERY_STRING'])) {
    $url .= '?' . $_SERVER['QUERY_STRING'];
}

// Obter conteúdo da requisição
$requestBody = file_get_contents('php://input');
$method = $_SERVER['REQUEST_METHOD'];

// Inicializar cURL
$ch = curl_init();

// Configurar opções do cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

$headers = ['Accept: application/json'];

if ($method === 'PUT' || $method === 'POST') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    $headers[] = 'Content-Type: application/json';
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Executar requisição
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Verificar erros
if (curl_errno($ch)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro na comunicação com a API: ' . curl_error($ch)
    ]);
    exit();
}

// Fechar cURL
curl_close($ch);

// Garantir que a resposta da API seja um JSON válido
if ($response === false || $response === '') {
    http_response_code($httpCode ?: 502);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'error' => 'A API externa retornou uma resposta vazia.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$decodedResponse = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'error' => 'A resposta da API externa não está em um formato JSON válido.',
        'details' => substr($response ?? '', 0, 200)
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Definir código de status HTTP e retornar o JSON validado
http_response_code($httpCode);
header('Content-Type: application/json');
echo json_encode($decodedResponse, JSON_UNESCAPED_UNICODE);
