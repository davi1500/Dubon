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

    public function create()
    {
        global $pdo;
        
        // Busca lista de clientes para o select
        $stmtClientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
        $clientes = $stmtClientes->fetchAll();

        // Busca o catálogo de serviços do BANCO DE DADOS para o autocomplete
        // Traz também a categoria para pegarmos o ícone
        $catalogo = $pdo->query("
            SELECT c.*, cat.icone_emoji, cat.icone_bootstrap, cat.id as categoria 
            FROM catalogo c 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
        ")->fetchAll();

        return view('novo_servico', [
            'clientes' => $clientes,
            'catalogo' => $catalogo
        ]);
    }

    public function store()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Recebe os dados básicos
                $cliente_id = $_POST['cliente_id'] ?? null;
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $garantia = $_POST['garantia'] ?? 90;
                $desconto = $_POST['desconto'] ?? 0;
                
                // Arrays dos itens (descrição, valor, quantidade)
                $itens_desc = $_POST['item_descricao'] ?? [];
                $itens_valor = $_POST['item_valor'] ?? [];
                $itens_qtd = $_POST['item_qtd'] ?? [];

                // Se for um novo cliente (texto livre), precisamos criar ou buscar
                // Por enquanto, vamos simplificar assumindo que o ID vem do select
                // Futuramente podemos melhorar para aceitar texto livre
                
                // Busca o nome do cliente para salvar no campo legado 'cliente' (compatibilidade)
                $stmtCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
                $stmtCli->execute([$cliente_id]);
                $nome_cliente = $stmtCli->fetchColumn();

                // 2. Inicia Transação
                $pdo->beginTransaction();

                // 3. Insere o Serviço
                $stmt = $pdo->prepare("INSERT INTO servicos (cliente, cliente_id, data_servico, status, valor_total, desconto, valor_pago, garantia, obs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nome_cliente,
                    $cliente_id,
                    $data_servico,
                    $status,
                    0, // Valor total (atualizado abaixo)
                    $desconto,
                    0, // Valor pago (pode ser implementado depois)
                    $garantia,
                    $obs
                ]);
                
                $servico_id = $pdo->lastInsertId();
                $valor_total_servico = 0;

                // 4. Insere os Itens
                $stmtItem = $pdo->prepare("INSERT INTO servicos_itens (servico_id, descricao, valor, quantidade) VALUES (?, ?, ?, ?)");
                
                for ($i = 0; $i < count($itens_desc); $i++) {
                    if (!empty($itens_desc[$i])) {
                        $vlr = str_replace(',', '.', $itens_valor[$i]); // Corrige formato moeda
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $stmtItem->execute([$servico_id, $itens_desc[$i], $vlr, $qtd]);
                        $valor_total_servico += ($vlr * $qtd);
                    }
                }

                // 5. Atualiza o valor total do serviço
                // O valor total final é a soma dos itens MENOS o desconto
                $valor_final = $valor_total_servico - $desconto;
                $pdo->exec("UPDATE servicos SET valor_total = $valor_final WHERE id = $servico_id");

                $pdo->commit();
                header('Location: ' . BASE_URL . '/'); // Redireciona para a home
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                die("Erro ao salvar: " . $e->getMessage());
            }
        }
    }

    public function edit($id)
    {
        global $pdo;

        // 1. Busca o serviço principal
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = ?");
        $stmt->execute([$id]);
        $servico = $stmt->fetch();

        if (!$servico) {
            return view('404');
        }

        // 2. Busca os itens do serviço
        $stmtItens = $pdo->prepare("SELECT * FROM servicos_itens WHERE servico_id = ?");
        $stmtItens->execute([$id]);
        $servico['itens'] = $stmtItens->fetchAll();

        // 3. Busca dados auxiliares (clientes, catalogo)
        $stmtClientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
        $clientes = $stmtClientes->fetchAll();

        // Busca o catálogo do BANCO DE DADOS
        $catalogo = $pdo->query("
            SELECT c.*, cat.icone_emoji, cat.icone_bootstrap, cat.id as categoria 
            FROM catalogo c 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
        ")->fetchAll();

        // 4. Reutiliza a view de novo serviço, passando os dados do serviço a ser editado
        return view('novo_servico', [
            'servico' => $servico,
            'clientes' => $clientes,
            'catalogo' => $catalogo
        ]);
    }

    public function update($id)
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Recebe os dados básicos
                $cliente_id = $_POST['cliente_id'] ?? null;
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $garantia = $_POST['garantia'] ?? 90;
                $desconto = str_replace(',', '.', $_POST['desconto'] ?? 0);
                
                // Arrays dos itens
                $itens_desc = $_POST['item_descricao'] ?? [];
                $itens_valor = $_POST['item_valor'] ?? [];
                $itens_qtd = $_POST['item_qtd'] ?? [];

                $stmtCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
                $stmtCli->execute([$cliente_id]);
                $nome_cliente = $stmtCli->fetchColumn();

                $pdo->beginTransaction();

                // 2. Calcula o valor total dos itens
                $valor_total_itens = 0;
                for ($i = 0; $i < count($itens_desc); $i++) {
                    if (!empty($itens_desc[$i])) {
                        $vlr = str_replace(',', '.', $itens_valor[$i]);
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $valor_total_itens += ($vlr * $qtd);
                    }
                }
                $valor_final = $valor_total_itens - $desconto;

                // 3. Atualiza o serviço principal
                $stmt = $pdo->prepare("UPDATE servicos SET cliente = ?, cliente_id = ?, data_servico = ?, status = ?, valor_total = ?, desconto = ?, garantia = ?, obs = ? WHERE id = ?");
                $stmt->execute([$nome_cliente, $cliente_id, $data_servico, $status, $valor_final, $desconto, $garantia, $obs, $id]);

                // 4. Deleta os itens antigos para reinserir os novos
                $pdo->prepare("DELETE FROM servicos_itens WHERE servico_id = ?")->execute([$id]);

                // 5. Insere os novos itens
                $stmtItem = $pdo->prepare("INSERT INTO servicos_itens (servico_id, descricao, valor, quantidade) VALUES (?, ?, ?, ?)");
                for ($i = 0; $i < count($itens_desc); $i++) {
                    if (!empty($itens_desc[$i])) {
                        $vlr = str_replace(',', '.', $itens_valor[$i]);
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $stmtItem->execute([$id, $itens_desc[$i], $vlr, $qtd]);
                    }
                }

                $pdo->commit();
                header('Location: ' . BASE_URL . '/');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                die("Erro ao atualizar: " . $e->getMessage());
            }
        }
    }
}