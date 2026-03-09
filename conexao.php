<?php
// Define o fuso horário para o Brasil (evita horários errados no servidor)
date_default_timezone_set('America/Sao_Paulo');

// Define a URL base do sistema.
// Vazio ('') se estiver na raiz do domínio.
define('BASE_URL', '');

// Configuração do Banco de Dados SQLite
$db_file = __DIR__ . '/database.sqlite';
$dsn = 'sqlite:' . $db_file;

try {
    // Cria a conexão
    $pdo = new PDO($dsn);
    
    // Configurações de Erro e Fetch
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Verifica se o banco de dados foi inicializado.
    // Se a tabela 'servicos' não existir, o sistema para e avisa o usuário.
    try {
        $pdo->query("SELECT 1 FROM servicos LIMIT 1");
    } catch (Exception $e) {
        // O erro "no such table" indica que as migrações não foram executadas.
        if (strpos($e->getMessage(), 'no such table') !== false) {
            die("
                <div style='font-family: sans-serif; padding: 2rem; background: #fff3f3; border: 1px solid #ffc0c0; margin: 2rem;'>
                    <h1 style='color: #d8000c;'>Erro: Banco de Dados não Inicializado</h1>
                    <p>As tabelas do sistema ainda não foram criadas.</p>
                    <p>Por favor, acesse o arquivo <strong>migracoes.php</strong> no seu navegador para configurar o banco de dados pela primeira vez.</p>
                    <p>Exemplo: <code>http://localhost:8000/migracoes.php</code></p>
                </div>
            ");
        }
        // Se for outro erro, exibe normalmente.
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }

} catch (PDOException $e) {
    die("Erro crítico no banco de dados: " . $e->getMessage());
}