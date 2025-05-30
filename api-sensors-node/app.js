const express = require('express');
const axios = require('axios');
const redis = require('redis');

const app = express();
app.use(express.json());

const PORT = 3000;

// Configurar cliente Redis
const redisClient = redis.createClient();

redisClient.on('error', (err) => {
  console.error('Erro no Redis:', err);
});

redisClient.connect();

// Simular dados do sensor
function gerarDadosSensor() {
  return {
    temperatura: (20 + Math.random() * 15).toFixed(2), // 20 a 35 °C
    pressao: (100 + Math.random() * 50).toFixed(2),   // 100 a 150 bar
    timestamp: new Date().toISOString()
  };
}

// GET /sensor-data com cache Redis
app.get('/sensor-data', async (req, res) => {
  try {
    // Verificar cache Redis
    const cacheDados = await redisClient.get('sensor-data');
    if (cacheDados) {
      console.log('Dados do sensor retornados do cache');
      return res.json(JSON.parse(cacheDados));
    }

    // Se não tem cache, gerar e salvar
    const dados = gerarDadosSensor();
    await redisClient.setEx('sensor-data', 30, JSON.stringify(dados)); // cache 30 segundos
    console.log('Dados do sensor gerados e salvos no cache');
    return res.json(dados);

  } catch (error) {
    console.error('Erro ao buscar sensor-data:', error);
    res.status(500).send('Erro interno');
  }
});

// POST /alert envia alerta para API Python via HTTP
app.post('/alert', async (req, res) => {
  const alerta = req.body;

  if (!alerta || Object.keys(alerta).length === 0) {
    return res.status(400).json({ error: 'Alerta vazio' });
  }

  try {
    // URL da API Python (ajuste para onde estiver rodando)
    const urlPython = 'http://localhost:5000/event';

    const response = await axios.post(urlPython, alerta);

    res.status(200).json({
      message: 'Alerta enviado para API Python com sucesso',
      pythonResponse: response.data
    });
  } catch (error) {
    console.error('Erro ao enviar alerta para API Python:', error.message);
    res.status(500).json({ error: 'Falha ao enviar alerta para API Python' });
  }
});

// Start servidor
app.listen(PORT, () => {
  console.log(`API Sensores rodando na porta ${PORT}`);
});
