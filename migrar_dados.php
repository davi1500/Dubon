<?php
require_once 'conexao.php';

echo "<h1>Iniciando Migração de JSON para Banco de Dados...</h1>";

// Verifica se já existem dados para evitar duplicação
$stmt = $pdo->query("SELECT COUNT(*) FROM servicos");
if ($stmt->fetchColumn() > 0) {
    die("<h3 style='color:red'>Atenção: O banco de dados já contém dados. A migração foi cancelada para evitar duplicidade.</h3>");
}

// --- 1. MIGRAR USUÁRIOS ---
$arquivo_usuarios = 'usuarios.json';
if (file_exists($arquivo_usuarios)) {
    $usuarios = json_decode(file_get_contents($arquivo_usuarios), true);
    if ($usuarios) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, nivel, nome) VALUES (?, ?, ?, ?)");
        foreach ($usuarios as $u) {
            // Verifica se usuário já existe antes de inserir
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $check->execute([$u['usuario']]);
            if (!$check->fetch()) {
                $stmt->execute([$u['usuario'], $u['senha'], $u['nivel'], $u['nome']]);
            }
        }
        $pdo->commit();
        echo "<p style='color:green'>✔ Usuários migrados com sucesso.</p>";
    }
}

// --- 2. MIGRAR SERVIÇOS ---
$arquivo_servicos = 'servicos.json';
if (file_exists($arquivo_servicos)) {
    $servicos = json_decode(file_get_contents($arquivo_servicos), true);
    
    if ($servicos) {
        $pdo->beginTransaction();
        try {
            foreach ($servicos as $s) {
                // Converte data de DD/MM/YYYY para YYYY-MM-DD (Padrão SQL)
                $dataSQL = null;
                if (!empty($s['data'])) {
                    $partes = explode('/', $s['data']);
                    if (count($partes) == 3) {
                        $dataSQL = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                // Insere o Serviço
                $stmt = $pdo->prepare("INSERT INTO servicos (cliente, data_servico, status, valor_total, valor_pago, garantia, obs) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $s['cliente'],
                    $dataSQL,
                    $s['status'],
                    $s['valor'] ?? 0,
                    $s['valor_pago'] ?? 0,
                    $s['garantia'] ?? 0,
                    $s['obs'] ?? ''
                ]);
                
                // Pega o ID gerado pelo banco para vincular os itens
                $servico_id = $pdo->lastInsertId();

                // Insere os Itens do Serviço
                if (isset($s['itens']) && is_array($s['itens'])) {
                    $stmtItem = $pdo->prepare("INSERT INTO servicos_itens (servico_id, descricao, valor, quantidade, categoria) VALUES (?, ?, ?, ?, ?)");
                    foreach ($s['itens'] as $item) {
                        $stmtItem->execute([
                            $servico_id,
                            $item['descricao'],
                            $item['valor'],
                            $item['quantidade'],
                            $item['categoria'] ?? 'outros'
                        ]);
                    }
                }
            }
            $pdo->commit();
            echo "<p style='color:green'>✔ " . count($servicos) . " serviços migrados com sucesso!</p>";
            echo "<p>Agora você pode acessar o sistema normalmente. Os arquivos .json antigos podem servir de backup.</p>";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color:red'>Erro na migração: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p>Arquivo servicos.json não encontrado.</p>";
}
?>
