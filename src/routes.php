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
$router->get('servicos/novo', 'HomeController@create'); // Nova página de cadastro
$router->get('servicos/editar/{id}', 'HomeController@edit'); // Rota para carregar o formulário de edição
$router->get('catalogo', 'CatalogoController@index'); // Nova área de serviços

// Define as rotas POST (para formulários)
$router->post('servicos/salvar', 'HomeController@store');
$router->post('servicos/atualizar/{id}', 'HomeController@update'); // Rota para salvar as alterações
$router->post('catalogo/salvar', 'CatalogoController@store'); // Salvar serviço no catálogo
$router->post('catalogo/excluir/{id}', 'CatalogoController@delete'); // Excluir serviço

// Retorna o roteador configurado para o index.php
return $router;
