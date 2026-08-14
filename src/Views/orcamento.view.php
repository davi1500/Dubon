<?php
$page_title = "Orçamento Rápido";
$isAdmin = (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin');

// Busca configurações da empresa (Logo, nome, telefone) para colocar no cabeçalho do PDF
global $pdo;
$stmtEmp = $pdo->query("SELECT chave, valor FROM configuracoes");
$configData = $stmtEmp->fetchAll(PDO::FETCH_KEY_PAIR);
$empresaNome = $configData['empresa_nome'] ?? 'Dubom Refrigeração';
$empresaTel = $configData['empresa_telefone'] ?? '';
$empresaLogo = $configData['empresa_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento Rápido - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #eef2f6; }
        .main-content { transition: margin-left 0.3s ease; padding: 2rem; width: 100%; }
        
        .ticket {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 20px;
        }
        .ticket-header { border-bottom: 2px solid #f8f9fa; margin-bottom: 20px; padding-bottom: 20px; }
        .ticket-footer { border-top: 2px solid #f8f9fa; margin-top: 20px; padding-top: 20px; }
        .sugestoes-lista {
            display: none; position: absolute; z-index: 1000; width: 100%;
            background: white; border: 1px solid #ddd; border-top: none;
            max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .sugestoes-lista .list-group-item { cursor: pointer; }
        .sugestoes-lista .list-group-item:hover { background-color: #f8f9fa; }

        /* Estilos de Impressão */
        @media print {
            body { background-color: #fff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .sidebar { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .ticket { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <?php require_once __DIR__ . '/../../_partials/menu.php'; ?>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h4 class="fw-bold mb-0 text-secondary"><i class="bi bi-calculator text-primary"></i> Orçamento Rápido</h4>
        <div>
            <button class="btn btn-success me-2" onclick="copiarWhatsApp()"><i class="bi bi-whatsapp"></i> Copiar para WhatsApp</button>
            <button class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Gerar PDF</button>
        </div>
    </div>

    <div class="row">
        <!-- Editor do Orçamento (Esquerda) -->
        <div class="col-lg-4 no-print">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">Adicionar Serviços</h6>
                    
                    <div id="itensContainer">
                        <!-- O primeiro item base -->
                        <div class="item-linha border rounded p-3 mb-3 bg-light position-relative">
                            <div class="row g-2">
                                <div class="col-12 item-input-wrapper position-relative">
                                    <label class="form-label small text-muted mb-1">Serviço</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control border-start-0 item-desc" placeholder="Ex: Instalação 9000 BTUs..." onkeyup="mostrarSugestoes(this)">
                                    </div>
                                    <input type="text" class="form-control form-control-sm item-detalhe" placeholder="Detalhe opcional (Ex: sem material)" onkeyup="atualizarPreview()">
                                    <ul class="list-group sugestoes-lista"></ul>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Qtd</label>
                                    <input type="number" class="form-control form-control-sm item-qtd" value="1" min="1" onchange="atualizarPreview()">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Valor Unit (R$)</label>
                                    <input type="text" class="form-control form-control-sm item-valor" value="0,00" onkeyup="mascaraMoeda(this); atualizarPreview()">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 border-0" onclick="removerItem(this)" title="Remover"><i class="bi bi-x"></i></button>
                        </div>
                    </div>

                    <button class="btn btn-outline-primary btn-sm w-100 fw-bold mb-4" onclick="adicionarNovaLinha()"><i class="bi bi-plus-lg"></i> Adicionar Mais Serviços</button>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Desconto Global (R$)</label>
                        <input type="text" id="inputDesconto" class="form-control form-control-sm" value="0,00" onkeyup="mascaraMoeda(this); atualizarPreview()">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Observações Gerais</label>
                        <textarea id="inputObs" class="form-control form-control-sm" rows="3" placeholder="Garantia, prazos, formas de pagamento..." onkeyup="atualizarPreview()"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview / Ticket (Direita) -->
        <div class="col-lg-8">
            <div class="ticket">
                <div class="ticket-header d-flex justify-content-between align-items-center">
                    <div>
                        <?php if($empresaLogo && file_exists(__DIR__ . '/../../public' . $empresaLogo)): ?>
                            <img src="<?php echo BASE_URL . $empresaLogo; ?>" alt="Logo" style="max-height: 60px;" class="mb-2">
                        <?php else: ?>
                            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-snow2"></i> <?php echo htmlspecialchars($empresaNome); ?></h3>
                        <?php endif; ?>
                        <p class="mb-0 text-muted small"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($empresaTel); ?></p>
                    </div>
                    <div class="text-end">
                        <h2 class="fw-bold mb-1" style="color: #444;">ORÇAMENTO</h2>
                        <span class="badge bg-light text-dark border">Válido por 7 dias</span><br>
                        <small class="text-muted">Data: <?php echo date('d/m/Y'); ?></small>
                    </div>
                </div>

                <div class="ticket-body">
                    <table class="table table-borderless table-striped">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th>Descrição do Serviço</th>
                                <th class="text-center" width="10%">Qtd</th>
                                <th class="text-end" width="20%">V. Unit.</th>
                                <th class="text-end" width="20%">Total</th>
                            </tr>
                        </thead>
                        <tbody id="ticketItens">
                            <!-- Injetado via JS -->
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum serviço adicionado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ticket-footer">
                    <div class="row justify-content-between">
                        <div class="col-sm-12 col-md-6 mb-3 mb-md-0">
                            <div id="ticketObsContainer" style="display: none;">
                                <h6 class="fw-bold text-secondary mb-1">Observações:</h6>
                                <p class="small text-muted mb-0" id="ticketObs" style="white-space: pre-wrap;"></p>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-bold" id="ticketSubtotal">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Desconto:</span>
                                <span class="fw-bold" id="ticketDesconto">- R$ 0,00</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-5 fw-bold text-dark">Total Geral:</span>
                                <span class="fs-4 fw-bold text-success" id="ticketTotal">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center text-muted small d-print-block">
                    <p class="mb-0">Este é um orçamento preliminar e não serve como documento fiscal.</p>
                    <p>Valores sujeitos a alteração mediante vistoria técnica presencial.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const catalogo = <?php echo json_encode($catalogo); ?>;

    function mascaraMoeda(input) {
        let v = input.value.replace(/\D/g, '');
        v = (v / 100).toFixed(2) + '';
        v = v.replace(".", ",");
        v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
        input.value = v;
    }

    function parseDinheiro(valStr) {
        if(!valStr) return 0;
        let p = valStr.replace(/\./g, '').replace(',', '.');
        let f = parseFloat(p);
        return isNaN(f) ? 0 : f;
    }

    function formatDinheiro(num) {
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function adicionarNovaLinha() {
        const container = document.getElementById('itensContainer');
        const novaLinha = document.createElement('div');
        novaLinha.className = 'item-linha border rounded p-3 mb-3 bg-light position-relative';
        novaLinha.innerHTML = `
            <div class="row g-2">
                <div class="col-12 item-input-wrapper position-relative">
                    <label class="form-label small text-muted mb-1">Serviço</label>
                    <div class="input-group input-group-sm mb-1">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 item-desc" placeholder="Ex: Instalação..." onkeyup="mostrarSugestoes(this)">
                    </div>
                    <input type="text" class="form-control form-control-sm item-detalhe" placeholder="Detalhe opcional" onkeyup="atualizarPreview()">
                    <ul class="list-group sugestoes-lista"></ul>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-1">Qtd</label>
                    <input type="number" class="form-control form-control-sm item-qtd" value="1" min="1" onchange="atualizarPreview()">
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-1">Valor Unit (R$)</label>
                    <input type="text" class="form-control form-control-sm item-valor" value="0,00" onkeyup="mascaraMoeda(this); atualizarPreview()">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 border-0" onclick="removerItem(this)" title="Remover"><i class="bi bi-x"></i></button>
        `;
        container.appendChild(novaLinha);
    }

    function removerItem(btn) {
        btn.closest('.item-linha').remove();
        atualizarPreview();
    }

    function mostrarSugestoes(input) {
        const termo = input.value.toLowerCase();
        const lista = input.closest('.item-input-wrapper').querySelector('.sugestoes-lista');
        lista.innerHTML = '';
        lista.style.display = 'none';

        if (termo.length < 1) {
            atualizarPreview();
            return;
        }

        const sugestoes = catalogo.filter(item => item.nome.toLowerCase().includes(termo));

        if (sugestoes.length > 0) {
            sugestoes.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2 small';
                li.innerHTML = `<span>${item.nome}</span> <strong class="text-success">R$ ${parseFloat(item.valor).toFixed(2).replace('.', ',')}</strong>`;
                li.onclick = () => selecionarSugestao(input, item);
                lista.appendChild(li);
            });
            lista.style.display = 'block';
        }
        atualizarPreview(); // Se ele digitar algo customizado
    }

    function selecionarSugestao(input, item) {
        input.value = item.nome;
        const row = input.closest('.item-linha');
        const inputValor = row.querySelector('.item-valor');
        
        let valorFormatado = "0,00";
        if (item.valor && !isNaN(parseFloat(item.valor))) {
            valorFormatado = parseFloat(item.valor).toFixed(2).replace('.', ',');
        }
        inputValor.value = valorFormatado; 
        
        input.closest('.item-input-wrapper').querySelector('.sugestoes-lista').style.display = 'none';
        atualizarPreview();
    }

    // Fecha sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('item-desc')) {
            document.querySelectorAll('.sugestoes-lista').forEach(el => el.style.display = 'none');
        }
    });

    function atualizarPreview() {
        const linhas = document.querySelectorAll('#itensContainer .item-linha');
        const ticketItens = document.getElementById('ticketItens');
        
        ticketItens.innerHTML = '';
        let subtotal = 0;
        let itensAdd = 0;

        linhas.forEach(row => {
            const desc = row.querySelector('.item-desc').value.trim();
            const detalhe = row.querySelector('.item-detalhe').value.trim();
            const qtd = parseInt(row.querySelector('.item-qtd').value) || 1;
            const valor = parseDinheiro(row.querySelector('.item-valor').value);
            
            if(desc && valor > 0) {
                const totalItem = qtd * valor;
                subtotal += totalItem;
                itensAdd++;

                let detalheHTML = detalhe ? `<br><small class="text-muted">\${detalhe}</small>` : '';

                ticketItens.innerHTML += `
                    <tr>
                        <td><span class="fw-bold">\${desc}</span>\${detalheHTML}</td>
                        <td class="text-center">\${qtd}</td>
                        <td class="text-end">R$ \${formatDinheiro(valor)}</td>
                        <td class="text-end fw-bold">R$ \${formatDinheiro(totalItem)}</td>
                    </tr>
                `;
            }
        });

        if(itensAdd === 0) {
            ticketItens.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Nenhum serviço adicionado.</td></tr>`;
        }

        const descontoStr = document.getElementById('inputDesconto').value;
        const desconto = parseDinheiro(descontoStr);
        const total = Math.max(0, subtotal - desconto);

        document.getElementById('ticketSubtotal').innerText = 'R$ ' + formatDinheiro(subtotal);
        
        if(desconto > 0) {
            document.getElementById('ticketDesconto').innerText = '- R$ ' + formatDinheiro(desconto);
        } else {
            document.getElementById('ticketDesconto').innerText = 'R$ 0,00';
        }

        document.getElementById('ticketTotal').innerText = 'R$ ' + formatDinheiro(total);
        
        // Trata observações globais
        const obs = document.getElementById('inputObs').value.trim();
        const obsContainer = document.getElementById('ticketObsContainer');
        const ticketObs = document.getElementById('ticketObs');
        if(obs) {
            ticketObs.innerText = obs;
            obsContainer.style.display = 'block';
        } else {
            ticketObs.innerText = '';
            obsContainer.style.display = 'none';
        }
    }

    function copiarWhatsApp() {
        const linhas = document.querySelectorAll('#itensContainer .item-linha');
        let texto = "*ORÇAMENTO PRELIMINAR*\n\n";
        
        let subtotal = 0;
        let itensAdd = 0;

        linhas.forEach(row => {
            const desc = row.querySelector('.item-desc').value.trim();
            const detalhe = row.querySelector('.item-detalhe').value.trim();
            const qtd = parseInt(row.querySelector('.item-qtd').value) || 1;
            const valor = parseDinheiro(row.querySelector('.item-valor').value);
            
            if(desc && valor > 0) {
                const totalItem = qtd * valor;
                subtotal += totalItem;
                itensAdd++;
                texto += `✅ ${qtd}x ${desc} - R$ ${formatDinheiro(totalItem)}\n`;
                if(detalhe) {
                    texto += `   _↳ ${detalhe}_\n`;
                }
            }
        });

        if(itensAdd === 0) {
            alert('Adicione pelo menos um serviço antes de copiar!');
            return;
        }

        const desconto = parseDinheiro(document.getElementById('inputDesconto').value);
        const total = Math.max(0, subtotal - desconto);

        texto += `\n`;
        if(desconto > 0) {
            texto += `Subtotal: R$ ${formatDinheiro(subtotal)}\n`;
            texto += `Desconto: - R$ ${formatDinheiro(desconto)}\n`;
        }
        texto += `*TOTAL GERAL: R$ ${formatDinheiro(total)}*\n\n`;
        
        const obs = document.getElementById('inputObs').value.trim();
        if(obs) {
            texto += `*Observações:*\n${obs}\n\n`;
        }
        
        texto += `_Orçamento válido por 7 dias. Valores sujeitos a confirmação técnica._\n`;
        texto += `Fico à disposição para qualquer dúvida!`;

        // Tentar copiar para o clipboard
        navigator.clipboard.writeText(texto).then(() => {
            alert('Copiado para a área de transferência!\nVocê já pode colar no WhatsApp do cliente.');
        }).catch(err => {
            console.error('Falha ao copiar: ', err);
            // Fallback manual para mobile antigo
            const textArea = document.createElement("textarea");
            textArea.value = texto;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            alert('Copiado para a área de transferência!\nVocê já pode colar no WhatsApp do cliente.');
        });
    }

</script>
</body>
</html>
