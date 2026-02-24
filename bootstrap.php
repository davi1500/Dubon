<?php

// Inicia a sessão em um único lugar, garantindo que sempre estará ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega a conexão com o banco de dados que já temos.
require 'conexao.php';

/**
 * Função "Helper" para carregar uma View.
 * Isso nos ajuda a manter os controllers limpos.
 */
function view($name, $data = [])
{
    // Transforma as chaves do array em variáveis para a view
    // Ex: $data['pageTitle'] vira a variável $pageTitle dentro da view
    extract($data);

    return require "src/Views/{$name}.view.php";
}