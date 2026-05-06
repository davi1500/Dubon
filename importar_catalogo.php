<?php
require_once __DIR__ . '/conexao.php';

echo "<h1>Iniciando importação do novo Catálogo...</h1>";

try {
    $pdo->beginTransaction();

    // 1. Limpa o catálogo atual para evitar duplicatas (Não afeta as OS antigas)
    $pdo->exec("DELETE FROM catalogo");
    echo "<p>Catálogo antigo limpo com sucesso.</p>";

    // 2. Super lista de serviços categorizados
    $servicos = [
        // --- AR CONDICIONADO ---
        ['nome' => 'Instalação Split (Até 12.000 BTUs)', 'categoria_id' => 'ar_condicionado', 'custo' => 50, 'valor' => 400, 'garantia' => 90],
        ['nome' => 'Instalação Split (18.000 a 30.000 BTUs)', 'categoria_id' => 'ar_condicionado', 'custo' => 80, 'valor' => 650, 'garantia' => 90],
        ['nome' => 'Instalação Piso Teto / Cassete', 'categoria_id' => 'ar_condicionado', 'custo' => 150, 'valor' => 1200, 'garantia' => 90],
        ['nome' => 'Desinstalação', 'categoria_id' => 'ar_condicionado', 'custo' => 20, 'valor' => 150, 'garantia' => 0],
        ['nome' => 'Limpeza Completa (Até 12.000 BTUs)', 'categoria_id' => 'ar_condicionado', 'custo' => 30, 'valor' => 180, 'garantia' => 90],
        ['nome' => 'Limpeza Completa (18.000 a 30.000 BTUs)', 'categoria_id' => 'ar_condicionado', 'custo' => 40, 'valor' => 250, 'garantia' => 90],
        ['nome' => 'Reparo Elétrico / Eletrônico', 'categoria_id' => 'ar_condicionado', 'custo' => 20, 'valor' => 150, 'garantia' => 90],
        ['nome' => 'Reparo de Vazamento e Carga de Gás', 'categoria_id' => 'ar_condicionado', 'custo' => 60, 'valor' => 350, 'garantia' => 90],
        ['nome' => 'Troca de Compressor', 'categoria_id' => 'ar_condicionado', 'custo' => 100, 'valor' => 600, 'garantia' => 90],
        ['nome' => 'Carga de Gás Adicional', 'categoria_id' => 'ar_condicionado', 'custo' => 50, 'valor' => 200, 'garantia' => 90],
        ['nome' => 'Pré-Instalação / Infraestrutura (Por Metro)', 'categoria_id' => 'ar_condicionado', 'custo' => 15, 'valor' => 100, 'garantia' => 90],
        ['nome' => 'Desentupimento de Dreno', 'categoria_id' => 'ar_condicionado', 'custo' => 10, 'valor' => 120, 'garantia' => 30],

        // --- MÁQUINA DE LAVAR E SECA ---
        ['nome' => 'Limpeza Completa', 'categoria_id' => 'maquina_lavar', 'custo' => 30, 'valor' => 200, 'garantia' => 90],
        ['nome' => 'Manutenção Mecânica (Câmbio / Rolamentos)', 'categoria_id' => 'maquina_lavar', 'custo' => 40, 'valor' => 350, 'garantia' => 90],
        ['nome' => 'Manutenção Elétrica (Chicote / Sensores)', 'categoria_id' => 'maquina_lavar', 'custo' => 15, 'valor' => 120, 'garantia' => 90],
        ['nome' => 'Troca de Placa (Interface / Potência)', 'categoria_id' => 'maquina_lavar', 'custo' => 20, 'valor' => 150, 'garantia' => 90],
        ['nome' => 'Troca de Bomba de Drenagem', 'categoria_id' => 'maquina_lavar', 'custo' => 15, 'valor' => 100, 'garantia' => 90],
        ['nome' => 'Troca de Válvula de Entrada', 'categoria_id' => 'maquina_lavar', 'custo' => 15, 'valor' => 90, 'garantia' => 90],
        ['nome' => 'Revisão Geral', 'categoria_id' => 'maquina_lavar', 'custo' => 20, 'valor' => 150, 'garantia' => 90],

        // --- GELADEIRA E FREEZER ---
        ['nome' => 'Reparo de Vazamento e Carga de Gás', 'categoria_id' => 'refrigeracao', 'custo' => 80, 'valor' => 380, 'garantia' => 90],
        ['nome' => 'Troca de Compressor', 'categoria_id' => 'refrigeracao', 'custo' => 90, 'valor' => 450, 'garantia' => 90],
        ['nome' => 'Manutenção Elétrica (Sensores / Placa)', 'categoria_id' => 'refrigeracao', 'custo' => 20, 'valor' => 150, 'garantia' => 90],
        ['nome' => 'Desobstrução de Dreno / Calha', 'categoria_id' => 'refrigeracao', 'custo' => 10, 'valor' => 100, 'garantia' => 30],
        ['nome' => 'Troca de Borracha (Gaxeta)', 'categoria_id' => 'refrigeracao', 'custo' => 10, 'valor' => 80, 'garantia' => 90],

        // --- REFRIGERAÇÃO COMERCIAL ---
        ['nome' => 'Limpeza de Condensadora', 'categoria_id' => 'balcao', 'custo' => 40, 'valor' => 250, 'garantia' => 90],
        ['nome' => 'Manutenção Corretiva Comercial (Mão de Obra)', 'categoria_id' => 'balcao', 'custo' => 50, 'valor' => 350, 'garantia' => 90],
        ['nome' => 'Reparo de Vazamento e Carga de Gás', 'categoria_id' => 'balcao', 'custo' => 120, 'valor' => 550, 'garantia' => 90],
        ['nome' => 'Troca de Compressor', 'categoria_id' => 'balcao', 'custo' => 150, 'valor' => 800, 'garantia' => 90],
        ['nome' => 'Troca de Controlador Digital', 'categoria_id' => 'camara_fria', 'custo' => 30, 'valor' => 180, 'garantia' => 90],

        // --- MICRO-ONDAS, BEBEDOUROS E ELETROPORTÁTEIS ---
        ['nome' => 'Manutenção Corretiva', 'categoria_id' => 'outros', 'custo' => 10, 'valor' => 80, 'garantia' => 90],
        ['nome' => 'Troca de Magnetron', 'categoria_id' => 'outros', 'custo' => 20, 'valor' => 120, 'garantia' => 90],
        ['nome' => 'Higienização e Troca de Filtro', 'categoria_id' => 'outros', 'custo' => 10, 'valor' => 70, 'garantia' => 90],

        // --- GERAIS E TAXAS ---
        ['nome' => 'Visita Técnica / Orçamento', 'categoria_id' => 'outros', 'custo' => 20, 'valor' => 50, 'garantia' => 0],
        ['nome' => 'Serviço Adicional / Hora Técnica', 'categoria_id' => 'outros', 'custo' => 0, 'valor' => 0, 'garantia' => 0],
    ];

    // 3. Inserção
    $stmt = $pdo->prepare("INSERT INTO catalogo (nome, categoria_id, custo, valor, garantia_dias) VALUES (?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($servicos as $s) {
        $stmt->execute([$s['nome'], $s['categoria_id'], $s['custo'], $s['valor'], $s['garantia']]);
        $count++;
    }

    $pdo->commit();
    
    echo "<p style='color: green;'><strong>Sucesso!</strong> Foram cadastrados {$count} serviços oficiais no sistema.</p>";
    echo "<a href='" . BASE_URL . "/catalogo'>Clique aqui para ver seu novo Catálogo</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>Erro ao importar: " . $e->getMessage() . "</p>";
}
?>