<?php
// setup_produtos.php
require_once 'conexao.php';

echo "<h1>Atualizando Produtos e Preços Médios</h1>";

// Lista de Produtos com Preço de Custo e Venda Sugeridos
// Baseado na lógica: Instalação 9000 btus = R$ 600 (R$ 350 Mão de Obra + R$ 250 Peças)
$produtos = [
    // --- Tubulação (Metro) ---
    [
        "nome" => "Tubo de Cobre 1/4\" (Metro)",
        "descricao" => "Tubulação linha líquida",
        "custo" => 18.00,
        "venda" => 30.00,
        "estoque" => 50
    ],
    [
        "nome" => "Tubo de Cobre 3/8\" (Metro)",
        "descricao" => "Tubulação linha de gás 9/12k",
        "custo" => 28.00,
        "venda" => 45.00,
        "estoque" => 50
    ],
    [
        "nome" => "Tubo de Cobre 1/2\" (Metro)",
        "descricao" => "Tubulação linha de gás 18k",
        "custo" => 38.00,
        "venda" => 60.00,
        "estoque" => 30
    ],
    [
        "nome" => "Tubo de Cobre 5/8\" (Metro)",
        "descricao" => "Tubulação linha de gás 24k",
        "custo" => 48.00,
        "venda" => 80.00,
        "estoque" => 20
    ],

    // --- Isolamento Térmico (Metro) ---
    [
        "nome" => "Isolante Térmico 1/4\" (Metro)",
        "descricao" => "Esponjoso blindado branco",
        "custo" => 2.00,
        "venda" => 5.00,
        "estoque" => 50
    ],
    [
        "nome" => "Isolante Térmico 3/8\" (Metro)",
        "descricao" => "Esponjoso blindado branco",
        "custo" => 3.00,
        "venda" => 6.00,
        "estoque" => 50
    ],
    [
        "nome" => "Isolante Térmico 1/2\" (Metro)",
        "descricao" => "Esponjoso blindado branco",
        "custo" => 4.00,
        "venda" => 8.00,
        "estoque" => 30
    ],
    [
        "nome" => "Isolante Térmico 5/8\" (Metro)",
        "descricao" => "Esponjoso blindado branco",
        "custo" => 5.00,
        "venda" => 10.00,
        "estoque" => 20
    ],

    // --- Elétrica e Acabamento ---
    [
        "nome" => "Cabo PP 4x1.5mm (Metro)",
        "descricao" => "Interligação elétrica",
        "custo" => 6.00,
        "venda" => 12.00,
        "estoque" => 100
    ],
    [
        "nome" => "Fita PVC Branca (Rolo)",
        "descricao" => "Acabamento da tubulação",
        "custo" => 8.00,
        "venda" => 15.00,
        "estoque" => 20
    ],
    
    // --- Acessórios ---
    [
        "nome" => "Par de Suporte 400mm (Mão Francesa)",
        "descricao" => "Suporte unidade externa até 12k",
        "custo" => 35.00,
        "venda" => 60.00,
        "estoque" => 20
    ],
    [
        "nome" => "Par de Suporte 500mm (Mão Francesa)",
        "descricao" => "Suporte unidade externa 18k+",
        "custo" => 45.00,
        "venda" => 80.00,
        "estoque" => 10
    ],
    [
        "nome" => "Kit Coxim (Borracha)",
        "descricao" => "Amortecedor de vibração",
        "custo" => 10.00,
        "venda" => 25.00,
        "estoque" => 20
    ],

    // --- Gases (Kg ou Carga) ---
    [
        "nome" => "Gás R-410A (Kg)",
        "descricao" => "Fluido Inverter",
        "custo" => 90.00,
        "venda" => 180.00,
        "estoque" => 11
    ],
    [
        "nome" => "Gás R-22 (Kg)",
        "descricao" => "Fluido Convencional",
        "custo" => 100.00,
        "venda" => 200.00,
        "estoque" => 13
    ],
    [
        "nome" => "Gás R-32 (Kg)",
        "descricao" => "Fluido Novos Inverter",
        "custo" => 120.00,
        "venda" => 250.00,
        "estoque" => 13
    ]
];

try {
    $pdo->beginTransaction();
    
    // Limpa tabela atual para evitar duplicatas e garantir IDs novos
    $pdo->exec("DELETE FROM produtos");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='produtos'");
    
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE nome = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco_custo, preco_venda, estoque) VALUES (?, ?, ?, ?, ?)");

    foreach ($produtos as $p) {
        $stmtInsert->execute([$p['nome'], $p['descricao'], $p['custo'], $p['venda'], $p['estoque']]);
        echo "Cadastrado: {$p['nome']} (Venda: R$ {$p['venda']})<br>";
    }
    $pdo->commit();
    echo "<h3>Produtos cadastrados com sucesso!</h3>";
    echo "<p><a href='produtos'>Ver Lista de Produtos</a></p>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro: " . $e->getMessage();
}