<?php

class MaterialController
{
    public function index()
    {
        global $pdo;
        // Busca materiais ordenando primeiro pelo status (quem precisa comprar aparece antes) e depois pelo nome
        $materiais = $pdo->query("SELECT * FROM materiais ORDER BY status ASC, nome ASC")->fetchAll();
        return view('materiais', ['materiais' => $materiais]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome'] ?? '');
            $obs = trim($_POST['obs'] ?? '');

            if (!empty($nome)) {
                $stmt = $pdo->prepare("INSERT INTO materiais (nome, obs, status) VALUES (?, ?, 'ok')");
                $stmt->execute([$nome, $obs]);
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Material adicionado à lista!'];
            }
            
            header('Location: ' . BASE_URL . '/materiais');
            exit;
        }
    }

    public function toggle($id)
    {
        global $pdo;
        // Busca o status atual para inverter
        $stmt = $pdo->prepare("SELECT status FROM materiais WHERE id = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetchColumn();

        $novo = ($atual === 'comprar') ? 'ok' : 'comprar';
        
        $pdo->prepare("UPDATE materiais SET status = ? WHERE id = ?")->execute([$novo, $id]);
        
        header('Location: ' . BASE_URL . '/materiais');
        exit;
    }

    public function delete($id)
    {
        global $pdo;
        $pdo->prepare("DELETE FROM materiais WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/materiais');
        exit;
    }
}