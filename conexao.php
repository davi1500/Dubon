<?php
// Define o fuso horário para o Brasil (evita horários errados no servidor)
date_default_timezone_set('America/Sao_Paulo');

// Define a pasta onde o sistema está instalado (vazio se for na raiz, ou '/pasta' se for subpasta)
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
        desconto DECIMAL(10,2) DEFAULT 0,
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

    // Atualização de Schema para Clientes (Novos Campos)
    // Tenta adicionar as colunas caso elas não existam
    $novas_colunas = ['cpf', 'cnpj', 'razao_social'];
    foreach ($novas_colunas as $col) {
        try {
            $pdo->exec("ALTER TABLE clientes ADD COLUMN $col TEXT");
        } catch (Exception $e) {
            // Coluna já existe, segue o baile
        }
    }

    // Tenta adicionar a coluna cliente_id na tabela servicos (caso não exista)
    // Isso conecta o serviço ao cadastro do cliente
    try {
        $pdo->exec("ALTER TABLE servicos ADD COLUMN cliente_id INTEGER");
    } catch (Exception $e) {
        // Coluna já existe, ignora o erro
    }

    // Tenta adicionar a coluna desconto na tabela servicos (caso não exista)
    try {
        $pdo->exec("ALTER TABLE servicos ADD COLUMN desconto DECIMAL(10,2) DEFAULT 0");
    } catch (Exception $e) {
        // Coluna já existe
    }

    // 5. Tabela de Categorias
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
        id TEXT PRIMARY KEY,
        nome TEXT NOT NULL,
        icone_bootstrap TEXT,
        icone_emoji TEXT
    )");

    // 6. Tabela de Catálogo de Serviços
    $pdo->exec("CREATE TABLE IF NOT EXISTS catalogo (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        categoria_id TEXT,
        custo DECIMAL(10,2),
        valor DECIMAL(10,2),
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    )");

    // Tenta adicionar a coluna de garantia no catalogo
    try {
        $pdo->exec("ALTER TABLE catalogo ADD COLUMN garantia_dias INTEGER DEFAULT 0");
    } catch (Exception $e) {
        // Coluna já existe
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

    // --- MIGRAÇÃO AUTOMÁTICA DE CATÁLOGO (JSON -> DB) ---
    // Se a tabela de categorias estiver vazia, tenta importar do JSON antigo
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM categorias");
    if ($stmtCat->fetchColumn() == 0) {
        $jsonFile = __DIR__ . '/categorias.json';
        if (file_exists($jsonFile)) {
            $cats = json_decode(file_get_contents($jsonFile), true);
            if ($cats) {
                $stmt = $pdo->prepare("INSERT INTO categorias (id, nome, icone_bootstrap, icone_emoji) VALUES (?, ?, ?, ?)");
                foreach ($cats as $c) {
                    $stmt->execute([$c['id'], $c['nome'], $c['icone_bootstrap'], $c['icone_emoji']]);
                }
            }
        }
    }

    // Se a tabela de catálogo estiver vazia, tenta importar do JSON antigo
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM catalogo");
    if ($stmtCat->fetchColumn() == 0) {
        $jsonFile = __DIR__ . '/catalogo.json';
        if (file_exists($jsonFile)) {
            $items = json_decode(file_get_contents($jsonFile), true);
            if ($items) {
                $stmt = $pdo->prepare("INSERT INTO catalogo (nome, categoria_id, custo, valor) VALUES (?, ?, ?, ?)");
                foreach ($items as $i) {
                    $stmt->execute([$i['nome'], $i['categoria'], $i['custo'], $i['valor']]);
                }
            }
        }
    }

    // 7. Tabela de Fornecedores
    $pdo->exec("CREATE TABLE IF NOT EXISTS fornecedores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        contato TEXT,
        telefone TEXT,
        email TEXT,
        endereco TEXT
    )");

    // 8. Tabela de Produtos (Estoque/Peças)
    $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        descricao TEXT,
        preco_custo DECIMAL(10,2),
        preco_venda DECIMAL(10,2),
        estoque INTEGER DEFAULT 0,
        fornecedor_id INTEGER,
        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
    )");

    // 9. Tabela de Configurações da Empresa
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave TEXT PRIMARY KEY,
        valor TEXT
    )");

    // 10. Tabela de Ligação Serviços <-> Produtos (Peças usadas na OS)
    $pdo->exec("CREATE TABLE IF NOT EXISTS servicos_produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        servico_id INTEGER NOT NULL,
        produto_id INTEGER NOT NULL,
        quantidade DECIMAL(10,2) DEFAULT 1,
        preco_venda DECIMAL(10,2),
        FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
    )");

} catch (PDOException $e) {
    die("Erro crítico no banco de dados: " . $e->getMessage());
}
?>