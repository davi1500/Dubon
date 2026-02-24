<?php

// Carrega a classe Router
require 'Router.php';

// Cria uma instância do roteador
$router = new Router();

// Define as rotas GET
$router->get('', 'HomeController@index'); // Página inicial
$router->get('servicos', 'HomeController@index'); // Alias para a inicial
$router->get('clientes', 'ClienteController@index');
$router->get('admin/dashboard', 'DashboardController@index');

// Define as rotas POST (para formulários)
// $router->post('servicos/salvar', 'HomeController@store');

// Retorna o roteador configurado para o index.php
return $router;
