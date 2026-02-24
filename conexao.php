<?php
// Define o fuso horário para o Brasil (evita horários errados no servidor)
date_default_timezone_set('America/Sao_Paulo');

// Configuração do Banco de Dados SQLite
$db_file = __DIR__ . '/database.sqlite';
$dsn = 'sqlite:' . $db_file;

try {
    // Cria a conexão
    $pdo = new PDO($dsn);
    
    // Configurações de Erro e Fetch
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- CRIAÇÃO AUTOMÁTICA DAS TABELAS ---
    
    // 1. Tabela de Usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        nivel TEXT NOT NULL,
        nome TEXT NOT NULL
    )");

    // 2. Tabela de Serviços (Cabeçalho)
    $pdo->exec("CREATE TABLE IF NOT EXISTS servicos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente TEXT NOT NULL,
        data_servico DATE,
        status TEXT,
        valor_total DECIMAL(10,2),
        valor_pago DECIMAL(10,2),
        garantia INTEGER,
        obs TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Tabela de Itens do Serviço
    $pdo->exec("CREATE TABLE IF NOT EXISTS servicos_itens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        servico_id INTEGER NOT NULL,
        descricao TEXT NOT NULL,
        valor DECIMAL(10,2),
        quantidade INTEGER DEFAULT 1,
        categoria TEXT,
        FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
    )");

    // 4. Tabela de Clientes (NOVO)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        telefone TEXT,
        endereco TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tenta adicionar a coluna cliente_id na tabela servicos (caso não exista)
    // Isso conecta o serviço ao cadastro do cliente
    try {
        $pdo->exec("ALTER TABLE servicos ADD COLUMN cliente_id INTEGER");
    } catch (Exception $e) {
        // Coluna já existe, ignora o erro
    }

    // --- MIGRAÇÃO AUTOMÁTICA DE CLIENTES ---
    // Se a tabela de clientes estiver vazia, popula com os nomes únicos dos serviços já existentes
    $pdo->exec("INSERT INTO clientes (nome) SELECT DISTINCT cliente FROM servicos WHERE cliente NOT IN (SELECT nome FROM clientes)");

    // --- SEED (DADOS INICIAIS) ---
    // Verifica se existe algum usuário. Se a tabela estiver vazia, cria o admin padrão.
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO usuarios (usuario, senha, nivel, nome) VALUES ('admin', '123', 'admin', 'Administrador')");
    }

} catch (PDOException $e) {
    die("Erro crítico no banco de dados: " . $e->getMessage());
}
?>