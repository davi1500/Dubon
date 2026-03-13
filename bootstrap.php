<?php

// Habilita exibição de erros (Essencial para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão em um único lugar, garantindo que sempre estará ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega a conexão com o banco de dados que já temos.
require __DIR__ . '/conexao.php';

// Carrega a classe de Roteamento (Correção do erro Class not found)
require_once __DIR__ . '/src/Router.php';

/**
 * Função "Helper" para carregar uma View.
 * Isso nos ajuda a manter os controllers limpos.
 */
function view($name, $data = [])
{
    // Transforma as chaves do array em variáveis para a view
    // Ex: $data['pageTitle'] vira a variável $pageTitle dentro da view
    extract($data);

    return require __DIR__ . "/src/Views/{$name}.view.php";
}