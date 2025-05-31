# Prova Prática – Sistemas Distribuídos

## Tema : Integração de APIs no Setor de Petróleo

Projeto para integração de 3 apis com microserviços , para controle de sensores de poços petrolificos

- **API 1 - Node.js (Sensores):** Faz os testes nos sensores e manda para api em python.

- **API 2 - Python (Eventos Críticos):** Recebe o alerta da API 1 e armazena eventos, e consumindo as mensagens da fila RabbitMQ, enviadas da API 3.

- **API 3 - PHP (Logística):** Faz todo o gerenciamento das duas apis e deixa na fila do RabbitMQ.

---

## Tecnologias Utilizadas

- Docker

- Php

- Python

- JavaScript

- RabbitMQ

- Cache Redis

---

## Como iniciar os testes

### 1. Subir o serviço Redis e RabbitMQ

1 . Primeiramente no ambiente do S.O Windows , abrir o executavel do docker e executar os comandos abaixo:

docker run -d --name redis -p 6379:6379 redis

docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management

2 . Após isso confira se os serviços estão ativos pelo docker, segue exemplo imagem abaixo:

![Serviços Ativos](images/serviços_docker_iniciados.png)

### 2. Criar pasta para aplicação Json

```bash

mkdir api-sensors-node

cd api-sensors-node

npm init -y

npm install express axios redis

```

Feito isso, dentro do Vscode , criar o app.js

A aplicação implementa a simulação dos dados de sensores (temperatura e pressão) e oferece dois endpoints para testes um GET /sensor-data e Post /alert

---

### 3. Criação do app.py

Primeiro precisa criar a pasta api-events-python e executar os comandos abaixo:

```bash

mkdir api-events-python

cd api-events-python

python -m venv venv

venv\Scripts\Activate

pip install fastapi uvicorn redis pika

unicorn app:app --reload --port 5000

```

Após isso cria o app .py e leia a estrutura logica implementada , sendo ela que recebe alertas de sensores via Http Post e armazena numa lista da memoria, para o cache redis

paralelamente executa o RabbitMQ e fica esperando a chamada da logistica e imprime se mensagem recebida na fila

---

### 4. Aplicação da Logistica em PHP

Primeiro cria a pasta api-php-logistica e depois implementa os comandos abaixo

```bash

mkdir api-php-logistica

cd api-php-logistica

composer install

php -S localhost:8000

```

Após instalar o composer, ele vai pedir um nome para projeto, geralmente coloca o nome do criador do fonte + o nome da pasta e depois disso coloca o nome do pacote composer require php-amqplib/php-amqplib

---

## Fluxo de Comunicação

1. **API 1 - Node.js** faz geração dos dados do sensores do poço e envia alertas para API2 via HTTP POST `/event`.

2. **API Python** Pega as requests do alerta e faz o consumo das mensagens RabbitMQ, armazenando eventos na fila.

3. **API PHP** Publica as mensagens urgentes de prioridade no  RabbitMQ `logistica`.

4. **Redis** Faz o cache em Node.js e lista de eventos em Python.

5. **RabbitMQ** Gerencia a comunicação PHP e Python.

---

## Autor

Gustavo Adaltino