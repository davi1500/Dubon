<?php
echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Migrações do Banco de Dados</title>";
echo "<style>body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f4; } .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } h1 { color: #333; } .log { padding: 10px; margin-top: 10px; border-radius: 5px; } .success { background: #e8f5e9; color: #2e7d32; } .error { background: #ffebee; color: #c62828; } .info { background: #e3f2fd; color: #1565c0; } code { background: #eee; padding: 2px 4px; border-radius: 4px; }</style>";
echo "</head><body><div class='container'><h1>Assistente de Configuração do Banco de Dados</h1>";

try {
    // Reutiliza a configuração de conexão
    require_once __DIR__ . '/conexao.php';
    echo "<p class='log success'>✔ Conexão com o banco de dados <code>database.sqlite</code> estabelecida com sucesso.</p>";

    $pdo->beginTransaction();

    echo "<p class='log info'>Iniciando criação e atualização das tabelas...</p>";

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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        cliente_id INTEGER,
        laudo_tecnico TEXT
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

    // 4. Tabela de Clientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        telefone TEXT,
        endereco TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        cpf TEXT,
        cnpj TEXT,
        razao_social TEXT,
        email TEXT
    )");

    // Atualização de Schema para Clientes (Novos Campos)
    // Garante que as colunas existam mesmo se a tabela já foi criada anteriormente
    $cols_clientes = ['cpf', 'cnpj', 'razao_social', 'email'];
    foreach ($cols_clientes as $col) {
        try {
            $pdo->exec("ALTER TABLE clientes ADD COLUMN $col TEXT");
        } catch (Exception $e) {
            // Coluna já existe, ignora o erro
        }
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
        garantia_dias INTEGER DEFAULT 0,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    )");

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

    echo "<p class='log success'>✔ Tabelas criadas/verificadas com sucesso.</p>";

    // --- SEED (DADOS INICIAIS) ---
    echo "<p class='log info'>Inserindo dados iniciais (se necessário)...</p>";

    // Verifica se existe algum usuário. Se a tabela estiver vazia, cria o admin padrão.
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO usuarios (usuario, senha, nivel, nome) VALUES ('admin', '123', 'admin', 'Administrador')");
        echo "<p class='log success'>- Usuário 'admin' com senha '123' foi criado.</p>";
    } else {
        echo "<p class='log info'>- Tabela de usuários já possui dados. Nenhum usuário foi criado.</p>";
    }

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
                echo "<p class='log success'>- Categorias importadas do arquivo <code>categorias.json</code>.</p>";
            }
        }
    } else {
        echo "<p class='log info'>- Tabela de categorias já possui dados. Nenhuma categoria foi importada.</p>";
    }

    // Se a tabela de catálogo estiver vazia, tenta importar do JSON antigo
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM catalogo");
    if ($stmtCat->fetchColumn() == 0) {
        $jsonFile = __DIR__ . '/catalogo.json';
        if (file_exists($jsonFile)) {
            $items = json_decode(file_get_contents($jsonFile), true);
            if ($items) {
                $stmt = $pdo->prepare("INSERT INTO catalogo (nome, categoria_id, custo, valor, garantia_dias) VALUES (?, ?, ?, ?, ?)");
                foreach ($items as $i) {
                    $stmt->execute([$i['nome'], $i['categoria'], $i['custo'] ?? 0, $i['valor'] ?? 0, $i['garantia_dias'] ?? 90]);
                }
                echo "<p class='log success'>- Catálogo de serviços importado do arquivo <code>catalogo.json</code>.</p>";
            }
        }
    } else {
        echo "<p class='log info'>- Tabela de catálogo já possui dados. Nenhum serviço foi importado.</p>";
    }

    $pdo->commit();

    echo "<h2 style='color: green;'>Configuração Concluída!</h2>";
    echo "<p>O banco de dados foi configurado corretamente. Agora você já pode usar o sistema.</p>";
    echo "<a href='" . BASE_URL . "/' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Acessar o Sistema</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p class='log error'><strong>Ocorreu um erro:</strong> " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";

?>