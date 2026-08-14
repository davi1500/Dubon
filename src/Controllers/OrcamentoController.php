<?php

class OrcamentoController
{
    public function index()
    {
        global $pdo;
        
        // Verifica se usuário está logado
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        // Buscar catálogo de serviços ordenado por categoria
        $stmtCat = $pdo->query("SELECT * FROM catalogo ORDER BY categoria, nome");
        $catalogo = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // O orçamento rápido não precisa buscar produtos ou clientes, 
        // seu objetivo é apenas gerar orçamentos rápidos e anônimos.
        
        // Renderizar a view
        require __DIR__ . '/../Views/orcamento.view.php';
    }
}
