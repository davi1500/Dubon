<?php
$is_edit = isset($servico);
$page_title = $is_edit ? "Editar OS #{$servico['id']}" : "Nova Ordem de Serviço";
$form_action = $is_edit ? BASE_URL . "/servicos/atualizar/{$servico['id']}" : BASE_URL . "/servicos/salvar";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?php
        // Favicon Dinâmico
        $favicon = "data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>❄️</text></svg>";
        if (isset($pdo)) {
            $stmtFav = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'empresa_logo'");
            $logoPath = $stmtFav->fetchColumn();
            if ($logoPath && file_exists(__DIR__ . '/../../public' . $logoPath)) {
                $favicon = BASE_URL . $logoPath;
            }
        }
    ?>
    <link rel="icon" href="<?php echo $favicon; ?>">
    <style>
        /* Estilo para a lista de sugestões */
        .sugestoes-lista { position: absolute; z-index: 1000; width: 95%; max-height: 200px; overflow-y: auto; display: none; }
        .sugestoes-lista .list-group-item { cursor: pointer; }
        .sugestoes-lista .list-group-item:hover { background-color: #f8f9fa; }
        .sugestoes-lista .list-group-item i { width: 25px; text-align: center; }
        .item-linha { position: relative; } /* Para posicionar a lista absoluta */
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0"><i class="bi <?php echo $is_edit ? 'bi-pencil-square' : 'bi-plus-circle'; ?>"></i> <?php echo $page_title; ?></h3>
        <div>
            <?php if($is_edit): ?>
                <a href="<?php echo BASE_URL; ?>/servicos/visualizar/<?php echo $servico['id']; ?>" class="btn btn-outline-primary rounded-pill px-4 me-2"><i class="bi bi-printer"></i> Visualizar / Imprimir</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
        </div>
    </div>

    <!-- [NOVO] Seletor de Pacotes Rápidos -->
    <?php if(!$is_edit): ?>
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <label class="fw-bold mb-2"><i class="bi bi-lightning-charge-fill text-warning"></i> Agilizar Preenchimento (Pacotes Prontos)</label>
        <select id="selectPacote" class="form-select form-select-lg" onchange="aplicarPacote(this)">
            <option value="">Selecione um pacote para preencher automaticamente...</option>
        </select>
    </div>
    <?php endif; ?>

    <form action="<?php echo $form_action; ?>" method="POST" class="card border-0 shadow-sm p-4 rounded-4">
        <div class="row g-3">
            
            <!-- Seção 1: Dados do Cliente e Data -->
            <div class="col-md-8 item-linha">
                <label class="form-label fw-bold">Cliente</label>
                <input type="text" name="cliente_nome" id="inputCliente" class="form-control form-control-lg bg-light border-0" placeholder="Digite o nome ou selecione..." value="<?php echo $is_edit ? htmlspecialchars($servico['cliente']) : ''; ?>" required autocomplete="off" oninput="mostrarSugestoesCliente(this)">
                <ul class="list-group sugestoes-lista shadow" id="sugestoesCliente"></ul>
                
                <!-- [CORREÇÃO] Campos para Novo Cliente (Aparecem apenas se o nome não existir) -->
                <div id="novos_campos_cliente" class="row g-2 mt-2" style="display:none;">
                    <div class="col-12"><small class="text-primary fw-bold"><i class="bi bi-person-plus"></i> Cliente novo detectado. Preencha para cadastrar:</small></div>
                    <div class="col-md-6"><input type="text" name="cliente_telefone" id="inputTelefone" class="form-control form-control-sm" placeholder="Telefone / WhatsApp"></div>
                    <div class="col-md-6"><input type="text" name="cliente_endereco" id="inputEndereco" class="form-control form-control-sm" placeholder="Endereço Completo"></div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Data do Serviço</label>
                <input type="date" name="data_servico" class="form-control form-control-lg bg-light border-0" value="<?php echo $is_edit ? ($servico['data_servico'] ?? date('Y-m-d')) : date('Y-m-d'); ?>" required>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <!-- Seção 2: Itens e Produtos (Abas) -->
            <div class="col-12">
                <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="servicos-tab" data-bs-toggle="tab" data-bs-target="#servicos-pane" type="button" role="tab"><i class="bi bi-tools"></i> Serviços</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="produtos-tab" data-bs-toggle="tab" data-bs-target="#produtos-pane" type="button" role="tab"><i class="bi bi-box-seam"></i> Peças / Produtos</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="myTabContent">
                    <!-- ABA SERVIÇOS -->
                    <div class="tab-pane fade show active" id="servicos-pane" role="tabpanel">
                        <div id="listaItens">
                            <?php if ($is_edit && !empty($servico['itens'])): ?>
                                <?php foreach($servico['itens'] as $item): ?>
                                    <div class="row g-2 mb-2 item-linha">
                                        <div class="col-12 col-md-6 position-relative item-input-wrapper">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 icon-view"><i class="bi bi-tools text-muted"></i></span>
                                                <input type="text" name="item_descricao[]" class="form-control border-start-0 ps-2 item-desc" placeholder="Descrição (ex: Limpeza Split)" oninput="mostrarSugestoes(this)" required autocomplete="off" value="<?php echo htmlspecialchars($item['descricao']); ?>">
                                            </div>
                                            <ul class="list-group sugestoes-lista shadow"></ul>
                                        </div>
                                        <div class="col-4 col-md-2">
                                            <input type="number" name="item_qtd[]" class="form-control" placeholder="Qtd" value="<?php echo $item['quantidade'] ?? 1; ?>" min="1" oninput="recalculateAll()">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text">R$</span>
                                                <input type="text" name="item_valor[]" class="form-control" placeholder="0,00" value="<?php echo number_format($item['valor'] ?? 0, 2, ',', '.'); ?>" oninput="recalculateAll()">
                                            </div>
                                        </div>
                                        <div class="col-2 col-md-1">
                                            <button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Item Padrão -->
                                <div class="row g-2 mb-2 item-linha">
                                    <div class="col-12 col-md-6 position-relative item-input-wrapper">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 icon-view"><i class="bi bi-tools text-muted"></i></span>
                                            <input type="text" name="item_descricao[]" class="form-control border-start-0 ps-2 item-desc" placeholder="Descrição (ex: Limpeza Split)" oninput="mostrarSugestoes(this)" required autocomplete="off">
                                        </div>
                                        <ul class="list-group sugestoes-lista shadow"></ul>
                                    </div>
                                    <div class="col-4 col-md-2"><input type="number" name="item_qtd[]" class="form-control" placeholder="Qtd" value="1" min="1" oninput="recalculateAll()"></div>
                                    <div class="col-6 col-md-3">
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" name="item_valor[]" class="form-control" placeholder="0,00" oninput="recalculateAll()">
                                        </div>
                                    </div>
                                    <div class="col-2 col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="adicionarLinha()">
                            <i class="bi bi-plus-lg"></i> Adicionar Outro Serviço
                        </button>
                    </div>

                    <!-- ABA PRODUTOS -->
                    <div class="tab-pane fade" id="produtos-pane" role="tabpanel">
                        <div id="listaProdutos">
                            <?php if ($is_edit && !empty($servico['produtos'])): ?>
                                <?php foreach($servico['produtos'] as $prod): ?>
                                    <div class="row g-2 mb-2 prod-linha">
                                        <div class="col-12 col-md-6">
                                            <select name="produto_id[]" class="form-select prod-select" onchange="atualizarPrecoProduto(this)">
                                                <option value="">Selecione um produto...</option>
                                                <?php foreach($produtos as $p): ?>
                                                    <option value="<?php echo $p['id']; ?>" data-preco="<?php echo $p['preco_venda']; ?>" <?php echo ($p['id'] == $prod['produto_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($p['nome']); ?> (Estoque: <?php echo $p['estoque']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4 col-md-2">
                                            <input type="number" name="produto_qtd[]" class="form-control" placeholder="Qtd" value="<?php echo $prod['quantidade']; ?>" min="1" oninput="recalculateAll()">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text">R$</span>
                                                <input type="text" name="produto_valor[]" class="form-control prod-valor" value="<?php echo number_format($prod['preco_venda'], 2, ',', '.'); ?>" oninput="recalculateAll()">
                                            </div>
                                        </div>
                                        <div class="col-2 col-md-1">
                                            <button type="button" class="btn btn-outline-danger w-100" onclick="removerLinhaProd(this)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="adicionarLinhaProduto()">
                            <i class="bi bi-plus-lg"></i> Adicionar Peça/Produto
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <!-- Seção 3: Detalhes Finais -->
            <div class="col-md-4">
                <label class="form-label fw-bold">Status Inicial</label>
                <select name="status" id="statusSelect" class="form-select fw-bold border-0 bg-info bg-opacity-10 text-info" onchange="atualizarCorStatus(this)">
                    <option value="Agendado" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Agendado') ? 'selected' : ''; ?>>📅 Agendado</option>
                    <option value="Em Andamento" class="text-dark" <?php echo (!$is_edit || ($is_edit && $servico['status'] == 'Em Andamento')) ? 'selected' : ''; ?>>🔧 Em Andamento</option>
                    <option value="Concluido" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Concluido') ? 'selected' : ''; ?>>⚠️ Concluído (Aguardando Pagamento)</option>
                    <option value="Pago" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Pago') ? 'selected' : ''; ?>>💲 Pago</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Garantia (Dias)</label>
                <input type="number" name="garantia" class="form-control bg-light border-0" value="<?php echo $is_edit ? ($servico['garantia'] ?? 90) : '90'; ?>">
            </div>
            <div class="col-md-4">
                <div class="card bg-light border-0 p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Soma dos Itens:</span>
                        <strong id="subtotalDisplay">R$ 0,00</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Desconto:</span>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <span class="input-group-text">R$</span>
                            <input type="text" name="desconto" id="inputDesconto" class="form-control text-end" value="<?php echo number_format($servico['desconto'] ?? 0, 2, ',', '.'); ?>" oninput="recalculateAll()">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                        <span class="fw-bold fs-5">Valor Final:</span>
                        <div class="input-group" style="width: 140px;">
                            <span class="input-group-text bg-primary text-white border-primary">R$</span>
                            <input type="text" id="inputTotalFinal" class="form-control fw-bold text-primary text-end" value="<?php echo number_format($servico['valor_total'] ?? 0, 2, ',', '.'); ?>" oninput="recalculateDiscountFromTotal()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Laudo Técnico (Visível para o cliente)</label>
                <textarea name="laudo_tecnico" class="form-control" rows="3" placeholder="Descreva o defeito constatado e a solução aplicada. Isso aparecerá na OS."><?php echo $is_edit ? htmlspecialchars($servico['laudo_tecnico'] ?? '') : ''; ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Observações Internas (Não aparece na OS)</label>
                <textarea name="obs" class="form-control bg-light border-0" rows="2" placeholder="Detalhes técnicos, endereço específico, etc..."><?php echo $is_edit ? htmlspecialchars($servico['obs']) : ''; ?></textarea>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow"><?php echo $is_edit ? 'Salvar Alterações na OS' : 'Criar Ordem de Serviço'; ?></button>
            </div>
        </div>
    </form>
</div>

<script>
    const catalogo = <?php echo json_encode($catalogo); ?>;
    const todosClientes = <?php echo json_encode($clientes); ?>;
    const listaProdutos = <?php echo json_encode($produtos); ?>;
    
    // --- DEFINIÇÃO DOS PACOTES (KIT INSTALAÇÃO) ---
    // Ajuste aqui para bater com a realidade do seu pai
    const pacotesDefinidos = {
        'inst_9000': {
            titulo: 'Instalação Split 9.000/12.000 BTUs (Padrão 3m)',
            servico: { desc: 'Mão de Obra de Instalação (Split)', valor: 350.00 },
            pecas: [
                { keyword: 'Cobre 1/4', qtd: 3 }, // 3 metros
                { keyword: 'Cobre 3/8', qtd: 3 }, // 3 metros
                { keyword: 'Isolante Térmico 1/4', qtd: 3 },
                { keyword: 'Isolante Térmico 3/8', qtd: 3 },
                { keyword: 'Suporte 400mm', qtd: 1 }, // 1 par
                { keyword: 'Cabo PP', qtd: 4 }, // 4 metros
                { keyword: 'Fita PVC', qtd: 1 } // 1 rolo
            ]
        },
        'inst_18000': {
            titulo: 'Instalação Split 18.000 BTUs (Padrão 3m)',
            servico: { desc: 'Mão de Obra de Instalação (18k)', valor: 450.00 },
            pecas: [
                { keyword: 'Cobre 1/4', qtd: 3 },
                { keyword: 'Cobre 1/2', qtd: 3 },
                { keyword: 'Isolante Térmico 1/4', qtd: 3 },
                { keyword: 'Isolante Térmico 1/2', qtd: 3 },
                { keyword: 'Suporte 500mm', qtd: 1 },
                { keyword: 'Cabo PP', qtd: 4 },
                { keyword: 'Fita PVC', qtd: 1 }
            ]
        },
        'carga_gas': {
            titulo: 'Carga de Gás Completa',
            servico: { desc: 'Mão de Obra e Vácuo', valor: 200.00 },
            pecas: [
                { keyword: 'Gás', qtd: 1 } // Vai puxar o primeiro gás que achar, usuário ajusta qual é
            ]
        }
    };

    // Carrega os pacotes no select ao iniciar
    document.addEventListener('DOMContentLoaded', () => {
        const selectPac = document.getElementById('selectPacote');
        if(selectPac) {
            for (const [key, pacote] of Object.entries(pacotesDefinidos)) {
                const opt = document.createElement('option');
                opt.value = key;
                opt.text = pacote.titulo;
                selectPac.appendChild(opt);
            }
        }
    });

    function aplicarPacote(select) {
        const key = select.value;
        if (!key || !pacotesDefinidos[key]) return;

        const pct = pacotesDefinidos[key];

        // 1. Limpa as listas atuais (opcional, aqui vou limpar para facilitar)
        document.getElementById('listaItens').innerHTML = '';
        document.getElementById('listaProdutos').innerHTML = '';

        // 2. Adiciona o Serviço (Mão de Obra)
        adicionarLinha(); // Cria a linha vazia
        const rowServ = document.querySelector('#listaItens .item-linha:last-child');
        rowServ.querySelector('.item-desc').value = pct.servico.desc;
        rowServ.querySelector('input[name="item_valor[]"]').value = formatCurrencyForInput(pct.servico.valor);

        // 3. Adiciona as Peças Automaticamente
        pct.pecas.forEach(pecaReq => {
            // Tenta encontrar o produto no array 'listaProdutos' (vindo do PHP) usando a palavra-chave
            const produtoEncontrado = listaProdutos.find(p => p.nome.toLowerCase().includes(pecaReq.keyword.toLowerCase()));
            
            if (produtoEncontrado) {
                adicionarLinhaProduto(); // Cria linha vazia
                const rowProd = document.querySelector('#listaProdutos .prod-linha:last-child');
                
                // Seleciona o produto no dropdown
                const selectProd = rowProd.querySelector('.prod-select');
                selectProd.value = produtoEncontrado.id;
                
                // Define quantidade e atualiza preço
                rowProd.querySelector('input[name="produto_qtd[]"]').value = pecaReq.qtd;
                atualizarPrecoProduto(selectProd);
            }
        });

        recalculateAll();
        
        // Reseta o select para não ficar marcado
        select.value = "";
    }

    // Mapa de Ícones e Cores por Categoria
    const categoriasIcons = {
        'ar_condicionado': { icon: 'bi-fan', color: 'text-info' },     // Ar Condicionado
        'maquina_lavar': { icon: 'bi-droplet-fill', color: 'text-primary' }, // Lavadora
        'refrigeracao': { icon: 'bi-snow2', color: 'text-info' },      // Geladeira
        'balcao': { icon: 'bi-shop', color: 'text-warning' },          // Balcão
        'camara_fria': { icon: 'bi-building', color: 'text-secondary' }, // Câmara Fria
        'outros': { icon: 'bi-tools', color: 'text-muted' }            // Geral
    };

    function adicionarLinha() {
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 item-linha';
        div.innerHTML = `
            <div class="col-12 col-md-6 position-relative item-input-wrapper">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 icon-view"><i class="bi bi-tools text-muted"></i></span>
                    <input type="text" name="item_descricao[]" class="form-control border-start-0 ps-2 item-desc" placeholder="Descrição" oninput="mostrarSugestoes(this)" required autocomplete="off">
                </div>
                <ul class="list-group sugestoes-lista shadow"></ul>
            </div>
            <div class="col-4 col-md-2"><input type="number" name="item_qtd[]" class="form-control" value="1" min="1" oninput="recalculateAll()"></div>
            <div class="col-6 col-md-3"><div class="input-group"><span class="input-group-text">R$</span><input type="text" name="item_valor[]" class="form-control" placeholder="0,00" oninput="recalculateAll()"></div></div>
            <div class="col-2 col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button></div>
        `;
        document.getElementById('listaItens').appendChild(div);
    }

    function adicionarLinhaProduto() {
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 prod-linha';
        
        let options = '<option value="">Selecione um produto...</option>';
        listaProdutos.forEach(p => {
            options += `<option value="${p.id}" data-preco="${p.preco_venda}">${p.nome} (Estoque: ${p.estoque})</option>`;
        });

        div.innerHTML = `
            <div class="col-12 col-md-6">
                <select name="produto_id[]" class="form-select prod-select" onchange="atualizarPrecoProduto(this)">
                    ${options}
                </select>
            </div>
            <div class="col-4 col-md-2"><input type="number" name="produto_qtd[]" class="form-control" value="1" min="1" oninput="recalculateAll()"></div>
            <div class="col-6 col-md-3">
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" name="produto_valor[]" class="form-control prod-valor" placeholder="0,00" oninput="recalculateAll()">
                </div>
            </div>
            <div class="col-2 col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removerLinhaProd(this)"><i class="bi bi-trash"></i></button></div>
        `;
        document.getElementById('listaProdutos').appendChild(div);
    }

    function removerLinha(btn) {
        if (document.querySelectorAll('.item-linha').length > 1) {
            btn.closest('.item-linha').remove();
            recalculateAll();
        }
    }

    function removerLinhaProd(btn) {
        btn.closest('.prod-linha').remove();
        recalculateAll();
    }

    function atualizarPrecoProduto(select) {
        const preco = select.options[select.selectedIndex].getAttribute('data-preco') || 0;
        const row = select.closest('.prod-linha');
        row.querySelector('.prod-valor').value = formatCurrencyForInput(parseFloat(preco));
        recalculateAll();
    }

    // --- LÓGICA DE CÁLCULO DINÂMICO ---
 /**
     * Converte strings de moeda (ex: "1.500,50", "1500.50", "1.200") 
     * em um Float válido para cálculos.
     */
    function parseCurrency(value) {
         if (!value) return 0;
         if (typeof value === 'number') return value;
 
         let cleanValue = String(value).replace(/[^\d,.-]/g, '');
 
         const lastComma = cleanValue.lastIndexOf(',');
         const lastDot = cleanValue.lastIndexOf('.');
 
         // Formato brasileiro (1.234,56)
         if (lastComma > lastDot) {
             // Remove todos os pontos (milhar) e troca a última vírgula por ponto (decimal)
             cleanValue = cleanValue.replace(/\./g, '').replace(',', '.');
         }
         // Formato americano (1,234.56) ou com apenas pontos
         else if (lastDot > lastComma) {
             // Remove todas as vírgulas (milhar)
             cleanValue = cleanValue.replace(/,/g, '');
         }
         // Apenas vírgula como decimal (1234,56)
         else if (lastComma !== -1) {
             cleanValue = cleanValue.replace(',', '.');
         }
 
         const result = parseFloat(cleanValue);
         return isNaN(result) ? 0 : result;
    }

    /**
     * Formata um número Float para o padrão de moeda brasileiro (1.500,50)
     */
    function formatCurrency(value) {
        return value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Formata um número para ser usado em um input (ex: 1500,50)
     */
    function formatCurrencyForInput(value) {
        if (isNaN(value)) return "0,00";
        return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Recalcula o Subtotal e o Valor Final com base nos itens e desconto.
     */
    function recalculateAll() {
        let subtotal = 0;

        const qtds = document.querySelectorAll('input[name="item_qtd[]"]');
        const valores = document.querySelectorAll('input[name="item_valor[]"]');

        qtds.forEach((inputQtd, index) => {
            const qtd = parseFloat(inputQtd.value) || 0;
            const valor = parseCurrency(valores[index].value);
            subtotal += (qtd * valor);
        });

        // Soma Produtos
        const qtdsProd = document.querySelectorAll('input[name="produto_qtd[]"]');
        const valoresProd = document.querySelectorAll('input[name="produto_valor[]"]');
        
        qtdsProd.forEach((inputQtd, index) => {
            const qtd = parseFloat(qtdsProd[index].value) || 0;
            const preco = parseCurrency(valoresProd[index].value);
            subtotal += (qtd * preco);
        });

        const desconto = parseCurrency(document.getElementById('inputDesconto').value);
        const totalFinal = subtotal - desconto;

        // Atualiza Exibições
        document.getElementById('subtotalDisplay').innerText = `R$ ${formatCurrency(subtotal)}`;
        document.getElementById('inputTotalFinal').value = formatCurrency(totalFinal);
    }

    /**
     * Lógica Reversa: Calcula o Desconto com base no Valor Final digitado.
     */
    function recalculateDiscountFromTotal() {
        let subtotal = 0;
        const qtds = document.querySelectorAll('input[name="item_qtd[]"]');
        const valores = document.querySelectorAll('input[name="item_valor[]"]');

        qtds.forEach((inputQtd, index) => {
            const qtd = parseFloat(inputQtd.value) || 0;
            const valor = parseCurrency(valores[index].value);
            subtotal += (qtd * valor);
        });

        // Soma Produtos
        const qtdsProd = document.querySelectorAll('input[name="produto_qtd[]"]');
        const valoresProd = document.querySelectorAll('input[name="produto_valor[]"]');

        qtdsProd.forEach((inputQtd, index) => {
            const qtd = parseFloat(qtdsProd[index].value) || 0;
            const preco = parseCurrency(valoresProd[index].value);
            subtotal += (qtd * preco);
        });

        const totalFinalDigitado = parseCurrency(document.getElementById('inputTotalFinal').value);
        
        // Desconto = Subtotal - Total Final
        const novoDesconto = subtotal - totalFinalDigitado;

        // Atualiza o campo de desconto e o subtotal (para garantir sincronia)
        document.getElementById('inputDesconto').value = formatCurrency(novoDesconto);
        document.getElementById('subtotalDisplay').innerText = `R$ ${formatCurrency(subtotal)}`;
    }


    // --- Lógica de Autocomplete Customizado ---

    // [CORREÇÃO] Verifica se o cliente digitado existe para mostrar/ocultar campos extras
    function verificarClienteNovo(nome) {
        const container = document.getElementById('novos_campos_cliente');
        const clienteExistente = todosClientes.find(c => c.nome.toLowerCase() === nome.toLowerCase());
        
        if (clienteExistente) {
            // Se o cliente já existe, esconde os campos extras
            container.style.display = 'none';
        } else {
            // Se não existe (e tem mais de 2 letras), mostra para cadastrar telefone/endereço
            if(nome.length > 2) container.style.display = 'flex';
        }
    }

    function mostrarSugestoesCliente(input) {
        const termo = input.value.toLowerCase();
        const lista = document.getElementById('sugestoesCliente');
        lista.innerHTML = '';
        lista.style.display = 'none';

        if (termo.length < 1) return;
        
        verificarClienteNovo(termo);

        const sugestoes = todosClientes.filter(cliente => cliente.nome.toLowerCase().includes(termo));

        if (sugestoes.length > 0) {
            sugestoes.forEach(cliente => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action d-flex align-items-center';
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person fs-5 me-2 text-muted"></i>
                        <span>${cliente.nome}</span>
                    </div>
                `;
                li.onclick = () => selecionarSugestaoCliente(input, cliente);
                lista.appendChild(li);
            });
            lista.style.display = 'block';
        }
    }

    function mostrarSugestoes(input) {
        const termo = input.value.toLowerCase();
        const lista = input.closest('.item-input-wrapper').querySelector('.sugestoes-lista');
        lista.innerHTML = '';
        lista.style.display = 'none';

        if (termo.length < 1) return;

        const sugestoes = catalogo.filter(item => item.nome.toLowerCase().includes(termo));

        if (sugestoes.length > 0) {
            sugestoes.forEach(item => {
                // Define o ícone baseado na categoria do item
                const catData = categoriasIcons[item.categoria] || categoriasIcons['outros'];
                
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi ${catData.icon} ${catData.color} fs-5 me-2"></i>
                        <span>${item.nome}</span>
                    </div>
                    <span class="badge bg-light text-dark border">R$ ${parseFloat(item.valor).toFixed(2)}</span>
                `;
                li.onclick = () => selecionarSugestao(input, item);
                lista.appendChild(li);
            });
            lista.style.display = 'block';
        }
    }

    function selecionarSugestaoCliente(input, cliente) {
        input.value = cliente.nome;
        // Esconde a lista
        document.getElementById('sugestoesCliente').style.display = 'none';
        // Esconde campos de novo cliente pois selecionou um existente
        document.getElementById('novos_campos_cliente').style.display = 'none';
    }

    function selecionarSugestao(input, item) {
        input.value = item.nome;
        const row = input.closest('.item-linha');
        const inputValor = row.querySelector('[name="item_valor[]"]');

        let valorFormatado = "0,00";
        if (item.valor && !isNaN(parseFloat(item.valor))) {
            valorFormatado = parseFloat(item.valor).toFixed(2).replace('.', ',');
        }
        inputValor.value = valorFormatado; 

        // Atualiza o ícone visualmente ao lado do input
        const iconContainer = row.querySelector('.icon-view i');
        const catData = categoriasIcons[item.categoria] || categoriasIcons['outros'];
        if (iconContainer) {
            iconContainer.className = `bi ${catData.icon} ${catData.color}`;
        }

        // Atualiza o campo de garantia da OS com o valor do item do catálogo
        const inputGarantia = document.querySelector('input[name="garantia"]');
        if (inputGarantia) {
            inputGarantia.value = item.garantia_dias || 0;
        }
        
        // Esconde a lista
        input.closest('.item-input-wrapper').querySelector('.sugestoes-lista').style.display = 'none';

        // Dispara o recálculo
        recalculateAll();
    }

    // Fecha as sugestões se clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('item-desc') && e.target.id !== 'inputCliente') {
            document.querySelectorAll('.sugestoes-lista').forEach(el => el.style.display = 'none');
        }
    });

    // --- Lógica Visual do Status ---
    function atualizarCorStatus(select) {
        // Remove todas as classes de cor atuais
        select.className = 'form-select fw-bold border-0';
        
        const val = select.value;
        if (val === 'Agendado') {
            select.classList.add('bg-info', 'bg-opacity-10', 'text-info');
        } else if (val === 'Em Andamento') {
            select.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
        } else if (val === 'Concluido') {
            select.classList.add('bg-warning', 'bg-opacity-10', 'text-warning');
        } else if (val === 'Pago') {
            select.classList.add('bg-success', 'bg-opacity-10', 'text-success');
        }
    }
    
    // Inicializa a cor do status ao carregar
    document.addEventListener('DOMContentLoaded', () => {
        atualizarCorStatus(document.getElementById('statusSelect'));

        // Calcula os totais ao carregar a página (importante para o modo de edição)
        recalculateAll();

        // Tenta recuperar os ícones corretos ao carregar a página (para edição)
        document.querySelectorAll('.item-desc').forEach(input => {
            const termo = input.value.toLowerCase();
            if (termo) {
                const item = catalogo.find(i => i.nome.toLowerCase() === termo);
                if (item) {
                    const row = input.closest('.item-linha');
                    const iconContainer = row.querySelector('.icon-view i');
                    const catData = categoriasIcons[item.categoria] || categoriasIcons['outros'];
                    if (iconContainer) iconContainer.className = `bi ${catData.icon} ${catData.color}`;
                }
            }
        });
    });

    // Feedback visual ao salvar
    document.querySelector('form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Salvando...';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>