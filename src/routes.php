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
$router->get('produtos', 'ProdutoController@index'); // Gestão de Produtos
$router->get('fornecedores', 'FornecedorController@index'); // Gestão de Fornecedores
$router->get('usuarios', 'UsuarioController@index'); // Gestão de Usuários
$router->get('empresa', 'EmpresaController@index'); // Gestão de Dados da Empresa

// Define as rotas POST (para formulários)
$router->post('servicos/salvar', 'HomeController@store');
$router->post('servicos/atualizar/{id}', 'HomeController@update'); // Rota para salvar as alterações
$router->post('servicos/excluir/{id}', 'HomeController@delete'); // Rota para excluir OS
$router->post('catalogo/salvar', 'CatalogoController@store'); // Salvar serviço no catálogo
$router->post('catalogo/excluir/{id}', 'CatalogoController@delete'); // Excluir serviço
$router->post('clientes/salvar', 'ClienteController@store'); // Salvar/Editar cliente
$router->post('clientes/excluir/{id}', 'ClienteController@delete'); // Excluir cliente
$router->post('produtos/salvar', 'ProdutoController@store');
$router->post('produtos/excluir/{id}', 'ProdutoController@delete');
$router->post('fornecedores/salvar', 'FornecedorController@store');
$router->post('fornecedores/excluir/{id}', 'FornecedorController@delete');
$router->post('usuarios/salvar', 'UsuarioController@store');
$router->post('usuarios/excluir/{id}', 'UsuarioController@delete');
$router->post('empresa/salvar', 'EmpresaController@store');

// Retorna o roteador configurado para o index.php
return $router;
