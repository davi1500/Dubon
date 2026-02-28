<?php
// c:\Users\davib\Desktop\Dubom\public\router_dev.php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// 1. Se o arquivo existe na pasta public (CSS, JS, imagens), deixa o servidor entregar
if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}

// 2. Se o arquivo existe na raiz do projeto (arquivos legados: login.php, clientes.php, gerar_os.php)
// Isso permite acessar /login.php mesmo ele estando fora da pasta public
$relativePath = ltrim($path, '/');
$rootFile = __DIR__ . '/../' . $relativePath;

if (!empty($relativePath) && file_exists($rootFile) && !is_dir($rootFile)) {
    // Ajusta o diretório de trabalho para a raiz para que os 'require' funcionem
    chdir(__DIR__ . '/../');
    require $rootFile;
    return;
}

// 3. Se não encontrou arquivo físico, manda para o index.php (Sistema Novo MVC)
require __DIR__ . '/index.php';
