<?php

class DespesaController
{
    public function index()
    {
        global $pdo;
        // Apenas admin
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // Busca despesas do mês atual por padrão, ou as últimas 50 para simplificar
        // Ordena por data (mais recente primeiro)
        $despesas = $pdo->query("SELECT * FROM despesas ORDER BY data_despesa DESC, id DESC LIMIT 100")->fetchAll();

        // Busca despesas recorrentes (fixas)
        $despesas_recorrentes = $pdo->query("SELECT * FROM despesas_recorrentes ORDER BY dia_vencimento ASC")->fetchAll();

        // Calcula total do mês atual para exibir na tela
        $mesAtual = date('Y-m');
        $totalVariaveis = $pdo->query("SELECT SUM(valor) FROM despesas WHERE strftime('%Y-%m', data_despesa) = '$mesAtual'")->fetchColumn();
        $totalFixas = $pdo->query("SELECT SUM(valor) FROM despesas_recorrentes WHERE ativa = 1")->fetchColumn();
        $totalMes = ($totalVariaveis ?: 0) + ($totalFixas ?: 0);

        return view('despesas', [
            'despesas' => $despesas, 
            'despesas_recorrentes' => $despesas_recorrentes,
            'totalMes' => $totalMes
        ]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $descricao = trim($_POST['descricao']);
            $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor'])));
            $data_despesa = $_POST['data_despesa'] ?? date('Y-m-d');
            $categoria = $_POST['categoria'];
            $obs = $_POST['obs'] ?? '';

            if (!empty($descricao) && $valor > 0) {
                $stmt = $pdo->prepare("INSERT INTO despesas (descricao, valor, data_despesa, categoria, obs) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$descricao, $valor, $data_despesa, $categoria, $obs]);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Despesa lançada com sucesso!'];
            }

            header('Location: ' . BASE_URL . '/despesas');
            exit;
        }
    }

    public function delete($id)
    {
        global $pdo;
        $pdo->prepare("DELETE FROM despesas WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/despesas');
        exit;
    }

    public function storeRecorrente()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_recorrente'] ?? null;
            $descricao = trim($_POST['descricao_recorrente']);
            $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor_recorrente'])));
            $dia_vencimento = (int) $_POST['dia_vencimento'];
            $categoria = $_POST['categoria_recorrente'];
            $ativa = isset($_POST['ativa']) ? 1 : 0;

            if ($id) {
                // Editar
                $stmt = $pdo->prepare("UPDATE despesas_recorrentes SET descricao=?, valor=?, dia_vencimento=?, categoria=?, ativa=? WHERE id=?");
                $stmt->execute([$descricao, $valor, $dia_vencimento, $categoria, $ativa, $id]);
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO despesas_recorrentes (descricao, valor, dia_vencimento, categoria, ativa) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$descricao, $valor, $dia_vencimento, $categoria, $ativa]);
            }

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Despesa fixa salva com sucesso!'];
            header('Location: ' . BASE_URL . '/despesas');
            exit;
        }
    }

    public function deleteRecorrente($id)
    {
        global $pdo;
        $pdo->prepare("DELETE FROM despesas_recorrentes WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/despesas');
        exit;
    }
}