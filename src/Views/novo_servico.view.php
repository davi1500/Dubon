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
        <a href="<?php echo BASE_URL; ?>/" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
    </div>

    <form action="<?php echo $form_action; ?>" method="POST" class="card border-0 shadow-sm p-4 rounded-4">
        <div class="row g-3">
            
            <!-- Seção 1: Dados do Cliente e Data -->
            <div class="col-md-8">
                <label class="form-label fw-bold">Cliente</label>
                <select name="cliente_id" class="form-select form-select-lg bg-light border-0" required>
                    <option value="">Selecione o cliente...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($is_edit && $servico['cliente_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Data do Serviço</label>
                <input type="date" name="data_servico" class="form-control form-control-lg bg-light border-0" value="<?php echo $is_edit ? $servico['data_servico'] : date('Y-m-d'); ?>" required>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <!-- Seção 2: Itens do Serviço -->
            <div class="col-12">
                <label class="form-label fw-bold mb-3">Itens da OS (Peças e Mão de Obra)</label>
                <div id="listaItens">
                    <?php if ($is_edit && !empty($servico['itens'])): ?>
                        <?php foreach($servico['itens'] as $item): ?>
                            <div class="row g-2 mb-2 item-linha">
                                <div class="col-md-6">
                                    <input type="text" name="item_descricao[]" class="form-control item-desc" placeholder="Descrição (ex: Limpeza Split)" oninput="mostrarSugestoes(this)" required autocomplete="off" value="<?php echo htmlspecialchars($item['descricao']); ?>">
                                    <ul class="list-group sugestoes-lista shadow"></ul>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="item_qtd[]" class="form-control" placeholder="Qtd" value="<?php echo $item['quantidade']; ?>" min="1" onchange="calcularTotal()">
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" name="item_valor[]" class="form-control" placeholder="0,00" step="0.01" onchange="calcularTotal()" value="<?php echo $item['valor']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Item Padrão -->
                        <div class="row g-2 mb-2 item-linha">
                            <div class="col-md-6">
                                <input type="text" name="item_descricao[]" class="form-control item-desc" placeholder="Descrição (ex: Limpeza Split)" oninput="mostrarSugestoes(this)" required autocomplete="off">
                                <ul class="list-group sugestoes-lista shadow"></ul>
                            </div>
                            <div class="col-md-2"><input type="number" name="item_qtd[]" class="form-control" placeholder="Qtd" value="1" min="1" onchange="calcularTotal()"></div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" name="item_valor[]" class="form-control" placeholder="0,00" step="0.01" onchange="calcularTotal()">
                                </div>
                            </div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="adicionarLinha()">
                    <i class="bi bi-plus-lg"></i> Adicionar Outro Item
                </button>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <!-- Seção 3: Detalhes Finais -->
            <div class="col-md-4">
                <label class="form-label fw-bold">Status Inicial</label>
                <select name="status" id="statusSelect" class="form-select fw-bold border-0 bg-info bg-opacity-10 text-info" onchange="atualizarCorStatus(this)">
                    <option value="Agendado" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Agendado') ? 'selected' : ''; ?>>📅 Agendado</option>
                    <option value="Em Andamento" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Em Andamento') ? 'selected' : ''; ?>>🔧 Em Andamento</option>
                    <option value="Concluido" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Concluido') ? 'selected' : ''; ?>>✅ Concluído (Aguardando Pagamento)</option>
                    <option value="Pago" class="text-dark" <?php echo ($is_edit && $servico['status'] == 'Pago') ? 'selected' : ''; ?>>💲 Pago</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Garantia (Dias)</label>
                <input type="number" name="garantia" class="form-control bg-light border-0" value="<?php echo $is_edit ? $servico['garantia'] : '90'; ?>">
            </div>
            <div class="col-md-4">
                <div class="card bg-light border-0 p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong id="subtotalDisplay">R$ 0,00</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Desconto:</span>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="desconto" id="inputDesconto" class="form-control text-end" value="<?php echo $is_edit ? $servico['desconto'] : '0'; ?>" step="0.01" oninput="calcularTotalFinal()">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                        <span class="fw-bold fs-5">Total:</span>
                        <div class="input-group" style="width: 140px;">
                            <span class="input-group-text bg-primary text-white border-primary">R$</span>
                            <input type="number" id="inputTotalFinal" class="form-control fw-bold text-primary text-end" value="<?php echo $is_edit ? $servico['valor_total'] : '0'; ?>" step="0.01" oninput="calcularDescontoReverso()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Observações Internas</label>
                <textarea name="obs" class="form-control bg-light border-0" rows="3" placeholder="Detalhes técnicos, endereço específico, etc..."><?php echo $is_edit ? htmlspecialchars($servico['obs']) : ''; ?></textarea>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow"><?php echo $is_edit ? 'Salvar Alterações na OS' : 'Criar Ordem de Serviço'; ?></button>
            </div>
        </div>
    </form>
</div>

<script>
    const catalogo = <?php echo json_encode($catalogo); ?>;

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
            <div class="col-md-6">
                <input type="text" name="item_descricao[]" class="form-control item-desc" placeholder="Descrição" oninput="mostrarSugestoes(this)" required autocomplete="off">
                <ul class="list-group sugestoes-lista shadow"></ul>
            </div>
            <div class="col-md-2"><input type="number" name="item_qtd[]" class="form-control" value="1" min="1" onchange="calcularTotal()"></div>
            <div class="col-md-3"><div class="input-group"><span class="input-group-text">R$</span><input type="number" name="item_valor[]" class="form-control" placeholder="0,00" step="0.01" onchange="calcularTotal()"></div></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removerLinha(this)"><i class="bi bi-trash"></i></button></div>
        `;
        document.getElementById('listaItens').appendChild(div);
    }

    function removerLinha(btn) {
        if (document.querySelectorAll('.item-linha').length > 1) {
            btn.closest('.item-linha').remove();
            calcularTotal();
        }
    }

    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.item-linha').forEach(row => {
            const qtd = parseFloat(row.querySelector('[name="item_qtd[]"]').value) || 0;
            const valor = parseFloat(row.querySelector('[name="item_valor[]"]').value) || 0;
            total += (qtd * valor);
        });
        
        // Atualiza o Subtotal na tela
        document.getElementById('subtotalDisplay').innerText = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        // Recalcula o total final mantendo o desconto atual
        calcularTotalFinal(total);
    }

    function calcularTotalFinal(subtotalOverride = null) {
        // Se não passou o subtotal, calcula de novo
        let subtotal = subtotalOverride;
        if (subtotal === null) {
            subtotal = 0;
            document.querySelectorAll('.item-linha').forEach(row => {
                const qtd = parseFloat(row.querySelector('[name="item_qtd[]"]').value) || 0;
                const valor = parseFloat(row.querySelector('[name="item_valor[]"]').value) || 0;
                subtotal += (qtd * valor);
            });
        }

        const desconto = parseFloat(document.getElementById('inputDesconto').value) || 0;
        const totalFinal = subtotal - desconto;

        document.getElementById('inputTotalFinal').value = totalFinal.toFixed(2);
    }

    function calcularDescontoReverso() {
        let subtotal = 0;
        document.querySelectorAll('.item-linha').forEach(row => {
            const qtd = parseFloat(row.querySelector('[name="item_qtd[]"]').value) || 0;
            const valor = parseFloat(row.querySelector('[name="item_valor[]"]').value) || 0;
            subtotal += (qtd * valor);
        });

        const totalDesejado = parseFloat(document.getElementById('inputTotalFinal').value) || 0;
        
        // Se o total desejado for maior que o subtotal, o desconto seria negativo (acréscimo), 
        // mas por enquanto vamos permitir isso ou zerar.
        const novoDesconto = subtotal - totalDesejado;

        document.getElementById('inputDesconto').value = novoDesconto.toFixed(2);
    }

    // --- Lógica de Autocomplete Customizado ---

    function mostrarSugestoes(input) {
        const termo = input.value.toLowerCase();
        const lista = input.nextElementSibling; // A <ul> logo após o input
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

    function selecionarSugestao(input, item) {
        input.value = item.nome;
        const row = input.closest('.item-linha');
        const inputValor = row.querySelector('[name="item_valor[]"]');
        inputValor.value = item.valor;
        
        // Esconde a lista
        input.nextElementSibling.style.display = 'none';
        
        calcularTotal();
    }

    // Fecha as sugestões se clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('item-desc')) {
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
            select.classList.add('bg-warning', 'bg-opacity-10', 'text-warning');
        } else if (val === 'Concluido') {
            select.classList.add('bg-danger', 'bg-opacity-10', 'text-danger');
        } else if (val === 'Pago') {
            select.classList.add('bg-success', 'bg-opacity-10', 'text-success');
        }
    }
    
    // Inicializa a cor do status ao carregar
    document.addEventListener('DOMContentLoaded', () => {
        atualizarCorStatus(document.getElementById('statusSelect'));
        calcularTotal(); // Calcula os totais ao carregar a página (importante para o modo de edição)
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>