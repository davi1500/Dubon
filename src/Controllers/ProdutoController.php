<?php

class ProdutoController
{
    public function index()
    {
        global $pdo;
        // Busca produtos com nome do fornecedor
        $produtos = $pdo->query("
            SELECT p.*, f.nome as nome_fornecedor 
            FROM produtos p 
            LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
            ORDER BY p.nome ASC
        ")->fetchAll();
        
        $fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC")->fetchAll();

        return view('produtos', ['produtos' => $produtos, 'fornecedores' => $fornecedores]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $preco_custo = str_replace(',', '.', $_POST['preco_custo'] ?? 0);
            $preco_venda = str_replace(',', '.', $_POST['preco_venda'] ?? 0);
            $estoque = $_POST['estoque'] ?? 0;
            $fornecedor_id = $_POST['fornecedor_id'] ?? null;

            if ($id) {
                $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, preco_custo=?, preco_venda=?, estoque=?, fornecedor_id=? WHERE id=?");
                $stmt->execute([$nome, $descricao, $preco_custo, $preco_venda, $estoque, $fornecedor_id, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco_custo, preco_venda, estoque, fornecedor_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, $preco_custo, $preco_venda, $estoque, $fornecedor_id]);
            }
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }
    }

    public function delete($id)
    {
        global $pdo;
        try {
            $pdo->prepare("DELETE FROM produtos WHERE id = ?")->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Produto excluído com sucesso!'];
        } catch (PDOException $e) {
            // Verifica se é erro de constraint (chave estrangeira)
            if (strpos($e->getMessage(), 'CONSTRAINT') !== false) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Não é possível excluir: Este produto está vinculado a uma ou mais Ordens de Serviço.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Erro ao excluir produto: ' . $e->getMessage()];
            }
        }
        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }
}