<?php

class FornecedorController
{
    public function index()
    {
        global $pdo;
        $fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();
        return view('fornecedores', ['fornecedores' => $fornecedores]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $contato = $_POST['contato'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $email = $_POST['email'] ?? '';
            $endereco = $_POST['endereco'] ?? '';

            if ($id) {
                $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, contato=?, telefone=?, email=?, endereco=? WHERE id=?");
                $stmt->execute([$nome, $contato, $telefone, $email, $endereco, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, contato, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $contato, $telefone, $email, $endereco]);
            }
            header('Location: ' . BASE_URL . '/fornecedores');
            exit;
        }
    }

    public function delete($id) {
        global $pdo; $pdo->prepare("DELETE FROM fornecedores WHERE id = ?")->execute([$id]); header('Location: ' . BASE_URL . '/fornecedores'); exit;
    }
}