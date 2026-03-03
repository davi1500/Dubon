<?php

class ClienteController
{
    public function index()
    {
        global $pdo;
        // Busca todos os clientes ordenados pelo nome
        $clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
        
        return view('clientes', ['clientes' => $clientes]);
    }

    public function store()
    {
        global $pdo;

        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $razao_social = $_POST['razao_social'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $cnpj = $_POST['cnpj'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $endereco = $_POST['endereco'] ?? '';

        if (empty($nome)) {
            // Nome é obrigatório
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }

        if (!empty($id)) {
            // Atualizar Existente
            $stmt = $pdo->prepare("UPDATE clientes SET nome=?, razao_social=?, cpf=?, cnpj=?, telefone=?, endereco=? WHERE id=?");
            $stmt->execute([$nome, $razao_social, $cpf, $cnpj, $telefone, $endereco, $id]);
        } else {
            // Criar Novo
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, razao_social, cpf, cnpj, telefone, endereco) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $razao_social, $cpf, $cnpj, $telefone, $endereco]);
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }

    public function delete($id)
    {
        global $pdo;
        // Exclui o cliente
        $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }
}