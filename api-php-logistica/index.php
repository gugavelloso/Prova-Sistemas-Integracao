<?php
require __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Simular lista de equipamentos
$equipments = [
    ['id' => 1, 'name' => 'Bomba Submersível'],
    ['id' => 2, 'name' => 'Compressor de Gás'],
    ['id' => 3, 'name' => 'Válvula de Controle'],
    ['id' => 4, 'name' => 'Sensor de Pressão'],
];

// Função para publicar mensagem no RabbitMQ com retorno detalhado
function publishDispatchMessage($data) {
    $host = 'localhost';
    $port = 5672;
    $user = 'guest';
    $pass = 'guest';
    $queue = 'logistica';

    try {
        $connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $channel = $connection->channel();

        // Declara a fila (durável)
        $channel->queue_declare($queue, false, true, false, false);

        $msgBody = json_encode($data);
        $msg = new AMQPMessage($msgBody, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $channel->basic_publish($msg, '', $queue);

        $channel->close();
        $connection->close();

        return [true, null];

    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

// Roteamento simples
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Define header JSON para resposta
header('Content-Type: application/json');

if ($uri === '/equipments' && $method === 'GET') {
    echo json_encode($equipments);
    exit;
}

if ($uri === '/dispatch' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Corpo JSON inválido']);
        exit;
    }

    list($success, $error) = publishDispatchMessage($input);

    if ($success) {
        echo json_encode(['message' => 'Mensagem de logística publicada com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Falha ao publicar mensagem', 'details' => $error]);
    }
    exit;
}

// Se endpoint não encontrado
http_response_code(404);
echo json_encode(['error' => 'Endpoint não encontrado']);
