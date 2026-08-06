<?php

class ContratoController
{
    public function index()
    {
        global $pdo;
        // Apenas admin pode acessar os contratos
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Busca contratos com os nomes dos clientes
        $stmt = $pdo->query("
            SELECT co.*, c.nome as cliente_nome, c.telefone as cliente_telefone 
            FROM contratos co 
            JOIN clientes c ON co.cliente_id = c.id 
            ORDER BY co.dia_vencimento ASC
        ");
        $contratos = $stmt->fetchAll();

        // Busca clientes para o modal de cadastro
        $clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();

        return view('contratos', ['contratos' => $contratos, 'clientes' => $clientes]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $cliente_id = $_POST['cliente_id'] ?? '';
            $valor_mensal = str_replace(',', '.', $_POST['valor_mensal'] ?? '0');
            $dia_vencimento = (int)($_POST['dia_vencimento'] ?? 1);
            $maquinas_cobertas = $_POST['maquinas_cobertas'] ?? '';
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if (empty($cliente_id) || empty($valor_mensal) || empty($dia_vencimento)) {
                die('Cliente, Valor e Dia de Vencimento são obrigatórios.');
            }

            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE contratos SET cliente_id=?, valor_mensal=?, dia_vencimento=?, maquinas_cobertas=?, ativo=? WHERE id=?");
                $stmt->execute([$cliente_id, $valor_mensal, $dia_vencimento, $maquinas_cobertas, $ativo, $id]);
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO contratos (cliente_id, valor_mensal, dia_vencimento, maquinas_cobertas, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cliente_id, $valor_mensal, $dia_vencimento, $maquinas_cobertas, $ativo]);
            }

            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }
    }

    public function delete($id)
    {
        global $pdo;
        $pdo->prepare("DELETE FROM contratos WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/contratos');
        exit;
    }

    public static function checkPMOC($pdo)
    {
        // Verifica todos os contratos ativos
        $mesAtual = date('Y-m');
        $diaAtual = (int)date('d');

        $stmt = $pdo->query("SELECT * FROM contratos WHERE ativo = 1");
        $contratos = $stmt->fetchAll();

        foreach ($contratos as $c) {
            // Se o mês atual ainda não foi gerado e já chegou/passou do dia de vencimento
            if ($c['ultimo_mes_gerado'] !== $mesAtual && $diaAtual >= $c['dia_vencimento']) {
                // 1. Gera a OS Preventiva
                $stmtOS = $pdo->prepare("INSERT INTO servicos (cliente_id, valor_total, valor_pago, status) VALUES (?, 0, 0, 'pendente')");
                $stmtOS->execute([$c['cliente_id']]);
                $osId = $pdo->lastInsertId();

                // 2. Insere os Itens da OS (Mão de Obra do Contrato)
                $desc = "Manutenção Preventiva PMOC (Mensal). Equipamentos: " . $c['maquinas_cobertas'];
                $stmtItem = $pdo->prepare("INSERT INTO servicos_itens (servico_id, descricao, valor) VALUES (?, ?, 0)");
                $stmtItem->execute([$osId, $desc]);

                // 3. Atualiza o Contrato para não gerar novamente este mês
                $pdo->prepare("UPDATE contratos SET ultimo_mes_gerado = ? WHERE id = ?")->execute([$mesAtual, $c['id']]);
                
                // NOTA: A expectativa financeira poderia ser lançada aqui na tabela de receitas
                // Mas, como a Dubom foca muito na OS para guiar o recebimento, deixaremos o controle da parcela PMOC a combinar no futuro se houver um módulo Conta Corrente.
            }
        }
    }
}
