<?php

class PagamentoController
{
    public function store($servico_id)
    {
        global $pdo;

        // Verifica se é admin ou tem permissão de gerenciar preços
        if (!isset($_SESSION['usuario_nivel']) || ($_SESSION['usuario_nivel'] !== 'admin' && !($_SESSION['pode_editar_precos'] ?? 0))) {
            header('Location: ' . BASE_URL . '/servicos/visualizar/' . $servico_id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $valor_str = $_POST['valor_pagamento'] ?? '0';
            $valor = floatval(str_replace(',', '.', str_replace('.', '', $valor_str)));
            $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d H:i:s');

            if ($valor > 0) {
                // 1. Inserir no histórico de pagamentos
                $stmt = $pdo->prepare("INSERT INTO pagamentos (servico_id, valor, data_pagamento, forma_pagamento) VALUES (?, ?, ?, '')");
                $stmt->execute([$servico_id, $valor, $data_pagamento]);

                // 2. Recalcular o valor pago da OS
                self::recalcularValorPago($servico_id, $pdo);
            }

            header('Location: ' . BASE_URL . '/servicos/visualizar/' . $servico_id);
            exit;
        }
    }

    public function delete($pagamento_id)
    {
        global $pdo;

        // Apenas admin pode excluir pagamentos
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Descobre o servico_id antes de excluir
        $stmtGet = $pdo->prepare("SELECT servico_id FROM pagamentos WHERE id = ?");
        $stmtGet->execute([$pagamento_id]);
        $servico_id = $stmtGet->fetchColumn();

        if ($servico_id) {
            // Exclui o pagamento
            $pdo->prepare("DELETE FROM pagamentos WHERE id = ?")->execute([$pagamento_id]);
            
            // Recalcula o valor pago da OS
            self::recalcularValorPago($servico_id, $pdo);
            
            header('Location: ' . BASE_URL . '/servicos/visualizar/' . $servico_id);
            exit;
        }

        header('Location: ' . BASE_URL . '/');
        exit;
    }

    public static function recalcularValorPago($servico_id, $pdo)
    {
        // 1. Soma todos os pagamentos para esta OS
        $stmtSoma = $pdo->prepare("SELECT SUM(valor) FROM pagamentos WHERE servico_id = ?");
        $stmtSoma->execute([$servico_id]);
        $total_pago = $stmtSoma->fetchColumn() ?: 0;

        // 2. Busca o valor total da OS
        $stmtOs = $pdo->prepare("SELECT valor_total FROM servicos WHERE id = ?");
        $stmtOs->execute([$servico_id]);
        $valor_total = $stmtOs->fetchColumn() ?: 0;

        // 3. Atualiza o status automaticamente
        // Se pagou tudo ou mais (as vezes dá gorjeta ou arredonda), o status vira "Pago".
        // Caso contrário, se o status já fosse "Pago" por erro, volta para "Concluido"
        $status_update = "";
        $params = [$total_pago, $servico_id];

        if ($total_pago >= $valor_total && $valor_total > 0) {
            $status_update = ", status = 'Pago'";
        } else {
            // Se estava como pago e excluiu um pagamento
            $stmtStatusAtual = $pdo->prepare("SELECT status FROM servicos WHERE id = ?");
            $stmtStatusAtual->execute([$servico_id]);
            $status_atual = $stmtStatusAtual->fetchColumn();
            
            if ($status_atual === 'Pago') {
                $status_update = ", status = 'Concluido'";
            }
        }

        $pdo->prepare("UPDATE servicos SET valor_pago = ? {$status_update} WHERE id = ?")->execute($params);
    }
}
