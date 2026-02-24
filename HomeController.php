<?php

class HomeController
{
    public function index()
    {
        // A variável $pdo está disponível globalmente pelo bootstrap.php
        global $pdo;

        // 1. Busca os serviços
        // Busca os serviços no banco (lógica que estava no index.php antigo)
        $stmt = $pdo->query("
            SELECT s.*, c.nome as nome_cliente 
            FROM servicos s 
            LEFT JOIN clientes c ON s.cliente_id = c.id 
            ORDER BY s.data_servico DESC, s.id DESC
        ");
        $servicos = $stmt->fetchAll();

        // 2. Busca dados para o Dashboard (apenas se for admin)
        $dashboard = [
            'faturamento' => 0,
            'pendente' => 0,
            'lucro' => 0 // Futuramente implementaremos o lucro real
        ];

        if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
            foreach ($servicos as $s) {
                $dashboard['faturamento'] += $s['valor_pago'];
                $dashboard['pendente'] += ($s['valor_total'] - $s['valor_pago']);
            }
        }

        // 3. Busca lista de clientes para o formulário de "Novo Serviço"
        $stmtClientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
        $clientes = $stmtClientes->fetchAll();

        // Chama a view e passa os dados
        return view('index', [
            'servicos' => $servicos,
            'dashboard' => $dashboard,
            'clientes' => $clientes
        ]);
    }
}