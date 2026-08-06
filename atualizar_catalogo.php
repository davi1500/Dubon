<?php
// c:\Users\davib\Desktop\Dubom\atualizar_catalogo.php
session_start();

// Proteção básica: Comente esta linha após o uso em produção, ou adicione verificação de admin.
// if (!isset($_SESSION['usuario']) || $_SESSION['nivel'] !== 'admin') {
//     die("Acesso restrito a administradores.");
// }

echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Atualizar Catálogo</title>";
echo "<style>body { font-family: sans-serif; padding: 20px; background: #f4f4f4; } .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; } .success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; } .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; }</style>";
echo "</head><body><div class='container'><h1>Atualização do Catálogo de Serviços</h1>";

try {
    require_once __DIR__ . '/conexao.php';
    
    // 1. Limpa a tabela catalogo atual
    $pdo->exec("DELETE FROM catalogo");
    
    // 2. Reseta o contador do ID (AUTOINCREMENT) para SQLite
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='catalogo'");
    
    echo "<p class='success'>✔ Tabela de catálogo antiga limpa com sucesso.</p>";
    
    // 3. Lê o arquivo novo_catalogo.json
    $jsonFile = __DIR__ . '/novo_catalogo.json';
    
    if (!file_exists($jsonFile)) {
        throw new Exception("Arquivo novo_catalogo.json não encontrado!");
    }
    
    $items = json_decode(file_get_contents($jsonFile), true);
    
    if (!$items) {
        throw new Exception("Erro ao ler o arquivo JSON. Formato inválido.");
    }
    
    // 4. Insere os novos itens
    $stmt = $pdo->prepare("INSERT INTO catalogo (nome, categoria_id, custo, valor, garantia_dias) VALUES (?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($items as $item) {
        $stmt->execute([
            $item['nome'], 
            $item['categoria'], 
            $item['custo'] ?? 0, 
            $item['valor'] ?? 0, 
            $item['garantia_dias'] ?? 90
        ]);
        $count++;
    }
    
    echo "<p class='success'>✔ $count novos serviços foram importados com sucesso para o catálogo!</p>";
    echo "<h3>O que fazer agora?</h3>";
    echo "<ul>";
    echo "<li>Apague (delete) o arquivo <code>atualizar_catalogo.php</code> do servidor por motivos de segurança.</li>";
    echo "<li>Seus funcionários agora só verão esses nomes oficiais ao criar uma nova OS.</li>";
    echo "<li>As OSs antigas permanecem com os nomes originais salvos e não sofreram alterações.</li>";
    echo "</ul>";
    echo "<br><a href='" . BASE_URL . "/' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voltar ao Sistema</a>";

} catch (Exception $e) {
    echo "<p class='error'><strong>Ocorreu um erro:</strong> " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
