<?php
session_start();

// Segurança: Apenas usuários logados com nível de 'admin' podem baixar o banco de dados
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    http_response_code(403);
    die("<h2>Acesso Negado</h2><p>Você precisa estar logado como administrador para realizar o backup do sistema.</p>");
}

$file = __DIR__ . '/database.sqlite';

if (file_exists($file)) {
    // Define os cabeçalhos para forçar o download do arquivo no navegador
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="backup_dubom_' . date('Y-m-d_H-i') . '.sqlite"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    die("Erro: Arquivo de banco de dados não encontrado.");
}