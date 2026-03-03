<?php

class CatalogoController
{
    public function index()
    {
        global $pdo;
        
        // Busca categorias para o formulário
        $categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
        
        // Busca itens do catálogo com dados da categoria
        $stmt = $pdo->query("
            SELECT c.*, cat.icone_emoji, cat.icone_bootstrap, cat.nome as nome_categoria 
            FROM catalogo c 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id 
            ORDER BY c.nome ASC
        ");
        $catalogo = $stmt->fetchAll();

        return view('catalogo', [
            'categorias' => $categorias,
            'catalogo' => $catalogo
        ]);
    }

    public function store()
    {
        global $pdo;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $categoria_id = $_POST['categoria_id'] ?? 'outros';
            $custo = str_replace(',', '.', $_POST['custo'] ?? 0);
            $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
            $garantia_dias = $_POST['garantia_dias'] ?? 0;
            $id = $_POST['id'] ?? null;

            if ($id) {
                // Atualizar existente
                $stmt = $pdo->prepare("UPDATE catalogo SET nome = ?, categoria_id = ?, custo = ?, valor = ?, garantia_dias = ? WHERE id = ?");
                $stmt->execute([$nome, $categoria_id, $custo, $valor, $garantia_dias, $id]);
            } else {
                // Criar novo
                $stmt = $pdo->prepare("INSERT INTO catalogo (nome, categoria_id, custo, valor, garantia_dias) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $categoria_id, $custo, $valor, $garantia_dias]);
            }
            
            header('Location: ' . BASE_URL . '/catalogo');
            exit;
        }
    }

    public function delete($id)
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM catalogo WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: ' . BASE_URL . '/catalogo');
        exit;
    }
}