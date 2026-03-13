<?php
// setup_materiais.php
require_once 'conexao.php';

echo "<h1>Atualização da Lista de Materiais</h1>";

$materiais = [
    // --- Fitas e Adesivos ---
    "Fita PVC",
    "Fita Silver Tape",
    "Fita Isolante",
    "Fita Elastomérica",
    "Fita Adesiva",
    "Fita de Contato",
    "Cola de Cano",
    "Massa Plástica",
    "Silicone",
    
    // --- Tubulação e Conexões ---
    "Cano PVC 20mm",
    "Cano PVC 25mm",
    "Joelho PVC 20mm",
    "Joelho PVC 25mm",
    "Curva 45 Graus PVC",
    "Mangueira 5/8\"",
    "Mangueira 1/2\"",
    "Conduíte (Caduide)",
    "Caixa de Passagem",
    
    // --- Isolamento Térmico ---
    "Isolante Esponjoso 1/4\"",
    "Isolante Esponjoso 3/8\"",
    "Isolante Esponjoso 1/2\"",
    "Isolante Esponjoso 5/8\"",
    "Isolante Esponjoso 3/4\"",
    "Isolante Esponjoso 7/8\"",
    
    // --- Solda ---
    "Solda de Alumínio",
    "Solda Zincaflux",
    "Solda Foscoper",
    "Solda Prata",
    "Solda Eletrodo",
    "Estanho",
    
    // --- Elétrica e Fixação ---
    "Fio 2.5mm Azul",
    "Fio 2.5mm Preto",
    "Fio 2.5mm Verde",
    "Abraçadeira de Nylon (Enforca Gato)",
    "Abraçadeira de Ferro",
    "Passador de Fio (Guia)",
    "Terminal Garfo 1.5",
    "Terminal Pino 1.5",
    "Terminal Bola 1.5",
    "Terminal Ilhós Tubular 1.5",
    "Terminal Garfo 2.5",
    "Terminal Pino 2.5",
    "Terminal Bola 2.5",
    "Terminal Ilhós Tubular 2.5",
    
    // --- Parafusos e Buchas ---
    "Parafuso 06",
    "Parafuso 08",
    "Parafuso 10",
    "Parafuso 12",
    "Parafuso 4.2",
    "Parafuso Brocante",
    "Bucha 06",
    "Bucha 08",
    "Bucha 10",
    "Bucha 12",
    "Bucha de Buraco Ruim (Aba)",
    "Arruela Pequena",
    "Arruela Grande",
    
    // --- Químicos e Limpeza ---
    "Detergente",
    "Esponja",
    "Thinner",
    "Água Raz",
    "Água Sanitária",
    "Sabão em Pó",
    "Ácido de Limpeza",
    "Jato Plus / BR Plus",
    "WD-40 / Desengripante",
    "Tinta Preta",
    "Graxa Branca",
    "Graxa Azul",
    "Anti-ferrugem",
    "Gesso",
    
    // --- Óleos ---
    "Óleo de Bomba de Vácuo",
    "Óleo 160P",
    "Óleo ISO 5",
    "Óleo 32",
    "Óleo R134a",
    "Óleo Capela",
    
    // --- Peças Diversas ---
    "O-ring (Anel de Vedação)",
    "Válvula Schrader",
    "Lixa",
    "Disco de Corte",
    "Disco de Desbaste",
    "Espuma Expansiva"
];

try {
    $pdo->beginTransaction();
    
    // [LIMPEZA] Remove todos os itens anteriores para garantir que os Produtos saiam da lista
    $pdo->exec("DELETE FROM materiais");
    // Reinicia o contador de ID (opcional, funciona no SQLite)
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='materiais'");

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM materiais WHERE nome = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO materiais (nome, status, obs) VALUES (?, 'ok', 'Importado automaticamente')");

    $adicionados = 0;
    foreach ($materiais as $item) {
        // Verifica se já existe para não duplicar
        $stmtCheck->execute([$item]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$item]);
            $adicionados++;
        }
    }

    $pdo->commit();
    echo "<p style='color: green; font-size: 1.2em;'>✔ Sucesso! <strong>{$adicionados}</strong> novos materiais foram adicionados à sua lista de compras.</p>";
    echo "<p><a href='" . BASE_URL . "/materiais'>Ir para Lista de Materiais</a></p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>Erro ao importar: " . $e->getMessage() . "</p>";
}
?>