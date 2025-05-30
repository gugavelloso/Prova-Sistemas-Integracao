<?php
require __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

try {
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    echo "Conexão com RabbitMQ bem sucedida!\n";
    $connection->close();
} catch (Exception $e) {
    echo "Erro na conexão: " . $e->getMessage() . "\n";
}
