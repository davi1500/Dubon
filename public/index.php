<?php

// 1. Carrega o arquivo de inicialização (sessão, conexão com DB, helpers)
require __DIR__ . '/../bootstrap.php';

// 2. Carrega as definições de rota e obtém uma instância do Router
$router = require __DIR__ . '/../src/routes.php';

// 3. Pega a URI (URL amigável) e o método (GET, POST) da requisição atual
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

// 4. Pede ao roteador para direcionar a requisição para o Controller correto
$router->direct($uri, $method);