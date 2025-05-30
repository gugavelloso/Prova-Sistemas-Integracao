from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import redis.asyncio as redis
import asyncio
import json
import pika
import threading

app = FastAPI()

# Configuração Redis
redis_client = redis.Redis(host='localhost', port=6379, decode_responses=True)

# Lista em memória para eventos
eventos = []

# Modelo para alerta (evento)
class Evento(BaseModel):
    sensorId: str
    tipo: str
    valor: float
    nivel: str
    timestamp: str

# Função para atualizar cache Redis com a lista atual de eventos
async def atualizar_cache_eventos():
    await redis_client.set('eventos-cache', json.dumps(eventos))

# POST /event - recebe alerta e salva
@app.post("/event")
async def receber_evento(evento: Evento):
    eventos.append(evento.dict())
    await atualizar_cache_eventos()
    return {"message": "Evento salvo com sucesso", "evento": evento}

# GET /events - retorna lista de eventos
@app.get("/events")
async def listar_eventos():
    # Tenta buscar do cache
    cache = await redis_client.get('eventos-cache')
    if cache:
        return json.loads(cache)
    return eventos

# --- Consumidor RabbitMQ ---

def callback(ch, method, properties, body):
    mensagem = body.decode('utf-8')
    print(f"[RabbitMQ] Mensagem recebida na fila logística: {mensagem}")

def consumir_rabbitmq():
    connection = pika.BlockingConnection(pika.ConnectionParameters('localhost'))
    channel = connection.channel()

    # Declara a fila que será consumida (nome: 'logistica')
    channel.queue_declare(queue='logistica')

    channel.basic_consume(queue='logistica',
                          on_message_callback=callback,
                          auto_ack=True)

    print(' [*] Aguardando mensagens na fila logística. Para sair, pressione CTRL+C')
    channel.start_consuming()

# Rodar consumidor RabbitMQ em thread separada para não travar API
threading.Thread(target=consumir_rabbitmq, daemon=True).start()
