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
            SELECT s.*, c.nome as nome_cliente, GROUP_CONCAT(i.descricao, '|||') as resumo_itens 
            FROM servicos s 
            LEFT JOIN clientes c ON s.cliente_id = c.id 
            LEFT JOIN servicos_itens i ON s.id = i.servico_id
            GROUP BY s.id
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

        // [NOVO] Busca despesas do MÊS ATUAL para o dashboard
        $mesAtual = date('Y-m');
        $stmtDespesas = $pdo->prepare("SELECT SUM(valor) FROM despesas WHERE strftime('%Y-%m', data_despesa) = ?");
        $stmtDespesas->execute([$mesAtual]);
        $total_despesas_variaveis = $stmtDespesas->fetchColumn() ?: 0;

        $total_despesas_fixas = $pdo->query("SELECT SUM(valor) FROM despesas_recorrentes WHERE ativa = 1")->fetchColumn() ?: 0;

        // Array auxiliar para custos de peças por serviço
        $custos_servicos = [];
        if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
            // Busca o custo total das peças utilizadas em cada serviço
            $stmtCustos = $pdo->query("SELECT servico_id, SUM(sp.quantidade * p.preco_custo) as custo_total 
                                       FROM servicos_produtos sp 
                                       JOIN produtos p ON sp.produto_id = p.id 
                                       GROUP BY servico_id");
            $custos_servicos = $stmtCustos->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin') {
            // Filtra os serviços para pegar apenas os do MÊS ATUAL para os cálculos do dashboard
            $servicos_mes_atual = array_filter($servicos, function($s) use ($mesAtual) {
                return substr($s['data_servico'], 0, 7) === $mesAtual;
            });

            // 1. Faturamento e Lucro são calculados sobre o MÊS ATUAL (Regime de Competência)
            foreach ($servicos_mes_atual as $s) {
                // Calcula quanto já foi pago nessa OS (para Faturamento Mensal)
                $val_pago = ($s['status'] === 'Pago') ? $s['valor_total'] : ($s['valor_pago'] ?? 0);
                $dashboard['faturamento'] += $val_pago;

                // Lucro Bruto do Mês = Faturamento do Mês - Custo das Peças do Mês
                $custo_pecas = $custos_servicos[$s['id']] ?? 0;
                $dashboard['lucro'] += ($s['valor_total'] - $custo_pecas);
            }
            // Subtrai despesas (que já são filtradas pelo mês atual no início do método)
            $dashboard['lucro'] -= ($total_despesas_variaveis + $total_despesas_fixas);

            // 2. 'A Receber' considera TODOS os serviços pendentes (Global / Acumulado)
            foreach ($servicos as $s) {
                // Calcula quanto já foi pago nessa OS
                $val_pago = ($s['status'] === 'Pago') ? $s['valor_total'] : ($s['valor_pago'] ?? 0);
                if ($s['status'] !== 'Pago') {
                    $dashboard['pendente'] += ($s['valor_total'] - $val_pago);
                }
            }

            // Contagem de "Em Andamento" vem de todos os serviços, não só do mês
            $dashboard['em_andamento'] = count(array_filter($servicos, fn($s) => $s['status'] === 'Em Andamento'));
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
                $cliente_telefone = $_POST['cliente_telefone'] ?? '';
                $cliente_endereco = $_POST['cliente_endereco'] ?? '';
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $laudo_tecnico = $_POST['laudo_tecnico'] ?? '';
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
                $prods_valor = $_POST['produto_valor'] ?? []; // Novo campo editável

                // 2. Inicia Transação
                $pdo->beginTransaction();

                // Lógica de Cliente: Busca por nome ou cria novo
                if (empty($cliente_nome)) {
                    throw new Exception("O nome do cliente é obrigatório.");
                }

                $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
                $stmtCheck->execute([$cliente_nome]);
                $cliente_id = $stmtCheck->fetchColumn();

                if (!$cliente_id) {
                    $stmtIns = $pdo->prepare("INSERT INTO clientes (nome, telefone, endereco) VALUES (?, ?, ?)");
                    $stmtIns->execute([$cliente_nome, $cliente_telefone, $cliente_endereco]);
                    $cliente_id = $pdo->lastInsertId();
                }

                // 3. Insere o Serviço
                $stmt = $pdo->prepare("INSERT INTO servicos (cliente, cliente_id, data_servico, status, valor_total, desconto, valor_pago, garantia, obs, laudo_tecnico) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $cliente_nome,
                    $cliente_id,
                    $data_servico,
                    $status,
                    0, // Valor total (atualizado abaixo)
                    $desconto,
                    0, // Valor pago (pode ser implementado depois)
                    $garantia,
                    $obs,
                    $laudo_tecnico
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
                        // Prioriza o valor enviado pelo formulário (editável), senão busca do banco
                        if (isset($prods_valor[$i]) && $prods_valor[$i] !== '') {
                            $preco = floatval(str_replace(',', '.', str_replace('.', '', $prods_valor[$i])));
                        } else {
                            $stmtGetPreco->execute([$prods_id[$i]]);
                            $preco = $stmtGetPreco->fetchColumn();
                        }

                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];

                        $stmtProd->execute([$servico_id, $prods_id[$i], $qtd, $preco]);
                        $stmtBaixa->execute([$qtd, $prods_id[$i]]);
                        
                        $valor_total_servico += ($preco * $qtd);
                    }
                }

                // 6. Atualiza o valor total e pago do serviço
                $valor_final = $valor_total_servico - $desconto;
                
                $valor_pago_final = 0;
                if ($status === 'Pago') {
                    $valor_pago_final = $valor_final;
                }

                // Atualiza o valor total e o valor pago na OS recém-criada
                $stmtUpdateTotal = $pdo->prepare("UPDATE servicos SET valor_total = ?, valor_pago = ? WHERE id = ?");
                $stmtUpdateTotal->execute([$valor_final, $valor_pago_final, $servico_id]);

                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Ordem de Serviço criada com sucesso!'];
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

    public function show($id)
    {
        global $pdo;

        // 1. Busca o serviço principal
        $stmt = $pdo->prepare("SELECT s.*, c.telefone as cliente_telefone, c.endereco as cliente_endereco, c.email as cliente_email FROM servicos s LEFT JOIN clientes c ON s.cliente_id = c.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $servico = $stmt->fetch();

        if (!$servico) {
            return view('404');
        }

        // 2. Busca os itens do serviço
        $stmtItens = $pdo->prepare("SELECT * FROM servicos_itens WHERE servico_id = ?");
        $stmtItens->execute([$id]);
        $servico['itens'] = $stmtItens->fetchAll();

        // 3. Busca os produtos vinculados
        $stmtProds = $pdo->prepare("
            SELECT sp.*, p.nome 
            FROM servicos_produtos sp 
            JOIN produtos p ON sp.produto_id = p.id 
            WHERE sp.servico_id = ?");
        $stmtProds->execute([$id]);
        $servico['produtos'] = $stmtProds->fetchAll();

        // 4. Busca dados da empresa para o cabeçalho
        $empresa = [];
        $conf = $pdo->query("SELECT * FROM configuracoes")->fetchAll();
        foreach($conf as $c) $empresa[$c['chave']] = $c['valor'];

        return view('ver_servico', ['servico' => $servico, 'empresa' => $empresa]);
    }

    public function storeGarantia($id)
    {
        global $pdo;

        // Busca a OS original (Pai)
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = ?");
        $stmt->execute([$id]);
        $pai = $stmt->fetch();

        if (!$pai) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Serviço original não encontrado.'];
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Cria a nova OS de Garantia
            // Status: Agendado, Valor: 0, Obs: Referência à OS original
            $obsGarantia = "RETORNO DE GARANTIA referente à OS #{$pai['id']}.\nMotivo: ";
            
            $stmtIns = $pdo->prepare("INSERT INTO servicos (cliente, cliente_id, data_servico, status, valor_total, desconto, valor_pago, garantia, obs, servico_pai_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                $pai['cliente'],
                $pai['cliente_id'],
                date('Y-m-d'), // Data de hoje
                'Agendado',
                0, // Valor começa zerado (é garantia)
                0,
                0,
                0, // Garantia da garantia? Geralmente é o restante da original, ou 0.
                $obsGarantia,
                $pai['id'] // VÍNCULO IMPORTANTE
            ]);
            
            $novaId = $pdo->lastInsertId();
            $pdo->commit();

            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'OS de Garantia aberta! Preencha os detalhes do retorno.'];
            // Redireciona direto para a edição da nova OS
            header('Location: ' . BASE_URL . "/servicos/editar/{$novaId}");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao gerar garantia: " . $e->getMessage());
        }
    }

    public function update($id)
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 1. Recebe os dados básicos
                $cliente_nome = trim($_POST['cliente_nome'] ?? '');
                $cliente_telefone = $_POST['cliente_telefone'] ?? '';
                $cliente_endereco = $_POST['cliente_endereco'] ?? '';
                $data_servico = $_POST['data_servico'] ?? date('Y-m-d');
                $status = $_POST['status'] ?? 'Agendado';
                $obs = $_POST['obs'] ?? '';
                $laudo_tecnico = $_POST['laudo_tecnico'] ?? '';
                $garantia = $_POST['garantia'] ?? 90;
                $desconto = floatval(str_replace(',', '.', str_replace('.', '', $_POST['desconto'] ?? '0')));
                
                // Arrays dos itens
                $itens_desc = $_POST['item_descricao'] ?? [];
                $itens_valor = $_POST['item_valor'] ?? [];
                $itens_qtd = $_POST['item_qtd'] ?? [];

                // Arrays dos produtos
                $prods_id = $_POST['produto_id'] ?? [];
                $prods_qtd = $_POST['produto_qtd'] ?? [];
                $prods_valor = $_POST['produto_valor'] ?? [];

                $pdo->beginTransaction();

                // Lógica de Cliente: Atualiza o nome ou reassocia o cliente.
                if (empty($cliente_nome)) {
                    throw new Exception("O nome do cliente é obrigatório.");
                }

                // Busca o cliente original da OS para saber quem estamos editando
                $stmtOrig = $pdo->prepare("SELECT cliente_id FROM servicos WHERE id = ?");
                $stmtOrig->execute([$id]);
                $original_cliente_id = $stmtOrig->fetchColumn();

                // Verifica se o novo nome já pertence a outro cliente
                $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
                $stmtCheck->execute([$cliente_nome]);
                $target_cliente_id = $stmtCheck->fetchColumn();

                if ($target_cliente_id) {
                    // Um cliente com o nome digitado já existe. Vamos usar o ID dele.
                    $cliente_id = $target_cliente_id;
                } else if ($original_cliente_id) {
                    // Nenhum cliente com o nome digitado existe, e a OS tinha um cliente vinculado.
                    // Isso significa que o usuário quer RENOMEAR o cliente original.
                    // ATUALIZAÇÃO: Também atualiza telefone e endereço para corrigir o cadastro completo.
                    $stmtUpdateCliente = $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ?, endereco = ? WHERE id = ?");
                    $stmtUpdateCliente->execute([$cliente_nome, $cliente_telefone, $cliente_endereco, $original_cliente_id]);
                    $cliente_id = $original_cliente_id;
                } else {
                    // Nenhum cliente com o nome digitado existe, e a OS não tinha cliente vinculado (dado antigo).
                    // Nesse caso, criamos um novo cliente.
                    // SEGURANÇA: Se o nome digitado não existe, CRIAMOS um novo cliente.
                    // Não renomeamos o antigo para evitar que a edição de uma OS altere o cadastro do cliente para todo o sistema.
                    $stmtIns = $pdo->prepare("INSERT INTO clientes (nome, telefone, endereco) VALUES (?, ?, ?)");
                    $stmtIns->execute([$cliente_nome, $cliente_telefone, $cliente_endereco]);
                    $cliente_id = $pdo->lastInsertId();
                }

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
                        if (isset($prods_valor[$i]) && $prods_valor[$i] !== '') {
                            $preco = floatval(str_replace(',', '.', str_replace('.', '', $prods_valor[$i])));
                        } else {
                            $stmtGetPreco->execute([$prods_id[$i]]);
                            $preco = $stmtGetPreco->fetchColumn();
                        }
                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];
                        $valor_total_itens += ($preco * $qtd);
                    }
                }
                $valor_final = $valor_total_itens - $desconto;

                // [CORREÇÃO] Define o valor pago com base no status
                $valor_pago_final = 0;
                if ($status === 'Pago') {
                    $valor_pago_final = $valor_final;
                }

                // 3. Atualiza o serviço principal
                $stmt = $pdo->prepare("UPDATE servicos SET cliente = ?, cliente_id = ?, data_servico = ?, status = ?, valor_total = ?, desconto = ?, valor_pago = ?, garantia = ?, obs = ?, laudo_tecnico = ? WHERE id = ?");
                $stmt->execute([$cliente_nome, $cliente_id, $data_servico, $status, $valor_final, $desconto, $valor_pago_final, $garantia, $obs, $laudo_tecnico, $id]);

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
                        if (isset($prods_valor[$i]) && $prods_valor[$i] !== '') {
                            $preco = floatval(str_replace(',', '.', str_replace('.', '', $prods_valor[$i])));
                        } else {
                            $stmtGetPreco->execute([$prods_id[$i]]);
                            $preco = $stmtGetPreco->fetchColumn();
                        }
                        $qtd = $prods_qtd[$i] < 1 ? 1 : $prods_qtd[$i];
                        
                        $stmtProd->execute([$id, $prods_id[$i], $qtd, $preco]);
                        $stmtBaixa->execute([$qtd, $prods_id[$i]]);
                    }
                }

                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Ordem de Serviço atualizada com sucesso!'];
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
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Ordem de Serviço excluída com sucesso!'];
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao excluir serviço: " . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/');
        exit;
    }
}