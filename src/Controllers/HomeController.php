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
            'em_andamento' => 0,
            'lucro' => 0 // Futuramente implementaremos o lucro real
        ];

        if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
            foreach ($servicos as $s) {
                $dashboard['faturamento'] += $s['valor_pago'];
                $dashboard['pendente'] += ($s['valor_total'] - $s['valor_pago']);
                if ($s['status'] === 'Em Andamento') {
                    $dashboard['em_andamento']++;
                }
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

        // Busca produtos com estoque > 0 para adicionar na OS
        $produtos = $pdo->query("SELECT * FROM produtos WHERE estoque > 0 ORDER BY nome ASC")->fetchAll();

        return view('novo_servico', [
            'clientes' => $clientes,
            'catalogo' => $catalogo,
            'produtos' => $produtos
        ]);
    }

    public function store()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Recebe os dados básicos
                $cliente_nome = trim($_POST['cliente_nome'] ?? '');
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $garantia = $_POST['garantia'] ?? 90;
                // Converte o valor de moeda BRL para um float válido
                $desconto = floatval(str_replace(',', '.', str_replace('.', '', $_POST['desconto'] ?? '0')));
                
                // Arrays dos itens (descrição, valor, quantidade)
                $itens_desc = $_POST['item_descricao'] ?? [];
                $itens_valor = $_POST['item_valor'] ?? [];
                $itens_qtd = $_POST['item_qtd'] ?? [];

                // Arrays dos produtos (peças)
                $prods_id = $_POST['produto_id'] ?? [];
                $prods_qtd = $_POST['produto_qtd'] ?? [];

                // Lógica de Cliente: Busca por nome ou cria novo
                if (empty($cliente_nome)) {
                    throw new Exception("O nome do cliente é obrigatório.");
                }

                $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
                $stmtCheck->execute([$cliente_nome]);
                $cliente_id = $stmtCheck->fetchColumn();

                if (!$cliente_id) {
                    $stmtIns = $pdo->prepare("INSERT INTO clientes (nome) VALUES (?)");
                    $stmtIns->execute([$cliente_nome]);
                    $cliente_id = $pdo->lastInsertId();
                }

                // 2. Inicia Transação
                $pdo->beginTransaction();

                // 3. Insere o Serviço
                $stmt = $pdo->prepare("INSERT INTO servicos (cliente, cliente_id, data_servico, status, valor_total, desconto, valor_pago, garantia, obs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $cliente_nome,
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
                        $vlr = floatval(str_replace(',', '.', str_replace('.', '', $itens_valor[$i])));
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $stmtItem->execute([$servico_id, $itens_desc[$i], $vlr, $qtd]);
                        $valor_total_servico += ($vlr * $qtd);
                    }
                }

                // 5. Insere os Produtos e Baixa Estoque
                $stmtProd = $pdo->prepare("INSERT INTO servicos_produtos (servico_id, produto_id, quantidade, preco_venda) VALUES (?, ?, ?, ?)");
                $stmtBaixa = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
                $stmtGetPreco = $pdo->prepare("SELECT preco_venda FROM produtos WHERE id = ?");

                for ($i = 0; $i < count($prods_id); $i++) {
                    if (!empty($prods_id[$i])) {
                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];
                        $stmtGetPreco->execute([$prods_id[$i]]);
                        $preco = $stmtGetPreco->fetchColumn();
                        
                        $stmtProd->execute([$servico_id, $prods_id[$i], $qtd, $preco]);
                        $stmtBaixa->execute([$qtd, $prods_id[$i]]);
                        
                        $valor_total_servico += ($preco * $qtd);
                    }
                }

                // 6. Atualiza o valor total do serviço
                // O valor total final é a soma dos itens MENOS o desconto
                $valor_final = $valor_total_servico - $desconto; // Agora ambos são floats
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

        // 2.1 Busca os produtos vinculados
        $stmtProds = $pdo->prepare("
            SELECT sp.*, p.nome 
            FROM servicos_produtos sp 
            JOIN produtos p ON sp.produto_id = p.id 
            WHERE sp.servico_id = ?");
        $stmtProds->execute([$id]);
        $servico['produtos'] = $stmtProds->fetchAll();

        // 3. Busca dados auxiliares (clientes, catalogo)
        $stmtClientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
        $clientes = $stmtClientes->fetchAll();

        // Busca o catálogo do BANCO DE DADOS
        $catalogo = $pdo->query("
            SELECT c.*, cat.icone_emoji, cat.icone_bootstrap, cat.id as categoria 
            FROM catalogo c 
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
        ")->fetchAll();

        // Busca todos os produtos (mesmo sem estoque, pois pode estar editando algo antigo)
        $produtos = $pdo->query("SELECT * FROM produtos ORDER BY nome ASC")->fetchAll();

        // 4. Reutiliza a view de novo serviço, passando os dados do serviço a ser editado
        return view('novo_servico', [
            'servico' => $servico,
            'clientes' => $clientes,
            'catalogo' => $catalogo,
            'produtos' => $produtos
        ]);
    }

    public function update($id)
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Recebe os dados básicos
                $cliente_nome = trim($_POST['cliente_nome'] ?? '');
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $garantia = $_POST['garantia'] ?? 90;
                $desconto = floatval(str_replace(',', '.', str_replace('.', '', $_POST['desconto'] ?? '0')));
                
                // Arrays dos itens
                $itens_desc = $_POST['item_descricao'] ?? [];
                $itens_valor = $_POST['item_valor'] ?? [];
                $itens_qtd = $_POST['item_qtd'] ?? [];

                // Arrays dos produtos
                $prods_id = $_POST['produto_id'] ?? [];
                $prods_qtd = $_POST['produto_qtd'] ?? [];

                // Lógica de Cliente: Busca por nome ou cria novo
                if (empty($cliente_nome)) {
                    throw new Exception("O nome do cliente é obrigatório.");
                }

                $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
                $stmtCheck->execute([$cliente_nome]);
                $cliente_id = $stmtCheck->fetchColumn();

                if (!$cliente_id) {
                    $stmtIns = $pdo->prepare("INSERT INTO clientes (nome) VALUES (?)");
                    $stmtIns->execute([$cliente_nome]);
                    $cliente_id = $pdo->lastInsertId();
                }

                $pdo->beginTransaction();

                // 2. Calcula o valor total dos itens
                $valor_total_itens = 0;
                for ($i = 0; $i < count($itens_desc); $i++) {
                    if (!empty($itens_desc[$i])) {
                        $vlr = floatval(str_replace(',', '.', str_replace('.', '', $itens_valor[$i])));
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $valor_total_itens += ($vlr * $qtd);
                    }
                }

                // Calcula total dos produtos
                $stmtGetPreco = $pdo->prepare("SELECT preco_venda FROM produtos WHERE id = ?");
                for ($i = 0; $i < count($prods_id); $i++) {
                    if (!empty($prods_id[$i])) {
                        $stmtGetPreco->execute([$prods_id[$i]]);
                        $preco = $stmtGetPreco->fetchColumn();
                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];
                        $valor_total_itens += ($preco * $qtd);
                    }
                }
                $valor_final = $valor_total_itens - $desconto;

                // 3. Atualiza o serviço principal
                $stmt = $pdo->prepare("UPDATE servicos SET cliente = ?, cliente_id = ?, data_servico = ?, status = ?, valor_total = ?, desconto = ?, garantia = ?, obs = ? WHERE id = ?");
                $stmt->execute([$cliente_nome, $cliente_id, $data_servico, $status, $valor_final, $desconto, $garantia, $obs, $id]);

                // 4. Deleta os itens antigos para reinserir os novos
                $pdo->prepare("DELETE FROM servicos_itens WHERE servico_id = ?")->execute([$id]);

                // 5. Insere os novos itens
                $stmtItem = $pdo->prepare("INSERT INTO servicos_itens (servico_id, descricao, valor, quantidade) VALUES (?, ?, ?, ?)");
                for ($i = 0; $i < count($itens_desc); $i++) {
                    if (!empty($itens_desc[$i])) {
                        $vlr = floatval(str_replace(',', '.', str_replace('.', '', $itens_valor[$i])));
                        $qtd = $itens_qtd[$i] < 1 ? 1 : $itens_qtd[$i];
                        $stmtItem->execute([$id, $itens_desc[$i], $vlr, $qtd]);
                    }
                }

                // 6. Gerencia Produtos (Devolve estoque antigo -> Remove -> Insere novo -> Baixa estoque)
                // Primeiro, devolve o estoque dos produtos que estavam na OS
                $oldProds = $pdo->prepare("SELECT produto_id, quantidade FROM servicos_produtos WHERE servico_id = ?");
                $oldProds->execute([$id]);
                $devolver = $oldProds->fetchAll();
                $stmtDevolve = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
                foreach ($devolver as $dp) {
                    $stmtDevolve->execute([$dp['quantidade'], $dp['produto_id']]);
                }

                // Remove vínculos antigos
                $pdo->prepare("DELETE FROM servicos_produtos WHERE servico_id = ?")->execute([$id]);

                // Insere novos e baixa estoque
                $stmtProd = $pdo->prepare("INSERT INTO servicos_produtos (servico_id, produto_id, quantidade, preco_venda) VALUES (?, ?, ?, ?)");
                $stmtBaixa = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
                
                for ($i = 0; $i < count($prods_id); $i++) {
                    if (!empty($prods_id[$i])) {
                        $stmtGetPreco->execute([$prods_id[$i]]);
                        $preco = $stmtGetPreco->fetchColumn();
                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];
                        
                        $stmtProd->execute([$id, $prods_id[$i], $qtd, $preco]);
                        $stmtBaixa->execute([$qtd, $prods_id[$i]]);
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

    public function delete($id)
    {
        global $pdo;

        // Apenas admin pode excluir
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Devolve o estoque dos produtos que estavam na OS antes de deletar
            $oldProds = $pdo->prepare("SELECT produto_id, quantidade FROM servicos_produtos WHERE servico_id = ?");
            $oldProds->execute([$id]);
            $devolver = $oldProds->fetchAll();
            $stmtDevolve = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
            foreach ($devolver as $dp) {
                $stmtDevolve->execute([$dp['quantidade'], $dp['produto_id']]);
            }

            // Deleta o serviço. O ON DELETE CASCADE cuidará das tabelas servicos_itens e servicos_produtos
            $pdo->prepare("DELETE FROM servicos WHERE id = ?")->execute([$id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao excluir serviço: " . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/');
        exit;
    }
}