<?php

$router = new Router;

// --- Home / Serviços ---
$router->get('', 'HomeController@index');
$router->get('servicos/novo', 'HomeController@create');
$router->post('servicos/salvar', 'HomeController@store');
$router->get('servicos/visualizar/{id}', 'HomeController@show');
$router->get('servicos/editar/{id}', 'HomeController@edit');
$router->post('servicos/atualizar/{id}', 'HomeController@update');
$router->post('servicos/excluir/{id}', 'HomeController@delete');
$router->post('servicos/garantia/{id}', 'HomeController@storeGarantia'); // Gera OS de garantia baseada na ID

// --- Clientes ---
$router->get('clientes', 'ClienteController@index');
$router->post('clientes/salvar', 'ClienteController@store');
$router->get('clientes/historico/{id}', 'ClienteController@historico');
$router->post('clientes/excluir/{id}', 'ClienteController@delete');

// --- Produtos (Peças/Estoque) ---
$router->get('produtos', 'ProdutoController@index');
$router->post('produtos/salvar', 'ProdutoController@store');
$router->post('produtos/excluir/{id}', 'ProdutoController@delete');

// --- Materiais (Lista de Compras) ---
$router->get('materiais', 'MaterialController@index');
$router->post('materiais/salvar', 'MaterialController@store');
$router->post('materiais/alternar/{id}', 'MaterialController@toggle');
$router->post('materiais/excluir/{id}', 'MaterialController@delete');

// --- Fornecedores ---
$router->get('fornecedores', 'FornecedorController@index');
$router->post('fornecedores/salvar', 'FornecedorController@store');
$router->post('fornecedores/excluir/{id}', 'FornecedorController@delete');

// --- Usuários ---
$router->get('usuarios', 'UsuarioController@index');
$router->post('usuarios/salvar', 'UsuarioController@store');
$router->post('usuarios/excluir/{id}', 'UsuarioController@delete');

// --- Catálogo de Serviços ---
$router->get('catalogo', 'CatalogoController@index');
$router->post('catalogo/salvar', 'CatalogoController@store');
$router->post('catalogo/excluir/{id}', 'CatalogoController@delete');

// --- Financeiro / Despesas ---
$router->get('despesas', 'DespesaController@index');
$router->post('despesas/salvar', 'DespesaController@store');
$router->post('despesas/excluir/{id}', 'DespesaController@delete');
$router->post('despesas/recorrente/salvar', 'DespesaController@storeRecorrente');
$router->post('despesas/recorrente/excluir/{id}', 'DespesaController@deleteRecorrente');

// --- Configurações da Empresa ---
$router->get('empresa', 'EmpresaController@index');
$router->post('empresa/salvar', 'EmpresaController@store');

// Retorna a instância do router para quem chamou (index.php)
return $router;