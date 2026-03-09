<?php
$page_title = "Ordem de Serviço #" . $servico['id'];
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
        body { background-color: #eef2f6; color: #333; }
        .os-container {
            max-width: 850px; /* Largura A4 aproximada */
            margin: 20px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .brand-color { color: #0d6efd; }
        .table-os th { background-color: #f8f9fa; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        .status-badge { font-size: 0.9rem; padding: 5px 12px; border-radius: 20px; }
        
        /* Estilos de Impressão */
        @media print {
            body { background-color: #fff; }
            .os-container { box-shadow: none; margin: 0; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
            .btn, header, .sidebar { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Barra de Ações (Não sai na impressão) -->
<div class="container mt-3 mb-3 no-print d-flex justify-content-between align-items-center">
    <a href="<?php echo BASE_URL; ?>/" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    <div>
        <a href="<?php echo BASE_URL; ?>/servicos/editar/<?php echo $servico['id']; ?>" class="btn btn-outline-primary me-2"><i class="bi bi-pencil"></i> Editar</a>
        <button onclick="window.print()" class="btn btn-secondary me-2"><i class="bi bi-printer"></i> Imprimir</button>
        <button onclick="enviarWhatsApp()" class="btn btn-success"><i class="bi bi-whatsapp"></i> Enviar no WhatsApp</button>
    </div>
</div>

<div class="os-container">
    <!-- Cabeçalho -->
    <div class="row border-bottom pb-4 mb-4 align-items-center">
        <div class="col-2 text-center">
            <?php if (!empty($empresa['empresa_logo']) && file_exists(__DIR__ . '/../../public' . $empresa['empresa_logo'])): ?>
                <img src="<?php echo BASE_URL . $empresa['empresa_logo']; ?>" alt="Logo" style="max-width: 100%; max-height: 80px;">
            <?php else: ?>
                <i class="bi bi-snow2 brand-color" style="font-size: 3rem;"></i>
            <?php endif; ?>
        </div>
        <div class="col-6">
            <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($empresa['empresa_nome'] ?? 'Dubom Refrigeração'); ?></h3>
            <?php if (!empty($empresa['empresa_cnpj'])): ?><small class="text-muted d-block">CNPJ: <?php echo htmlspecialchars($empresa['empresa_cnpj']); ?></small><?php endif; ?>
            <?php if (!empty($empresa['empresa_slogan'])): ?><small class="text-muted d-block"><?php echo htmlspecialchars($empresa['empresa_slogan']); ?></small><?php endif; ?>
            <?php if (!empty($empresa['empresa_telefone'])): ?><small class="text-muted d-block">Contato: <?php echo htmlspecialchars($empresa['empresa_telefone']); ?> | <?php echo htmlspecialchars($empresa['empresa_email']); ?></small><?php endif; ?>
        </div>
        <div class="col-4 text-end">
            <h4 class="text-secondary fw-bold">OS Nº <?php echo str_pad($servico['id'], 4, '0', STR_PAD_LEFT); ?></h4>
            <small class="text-muted">Data: <?php echo date('d/m/Y', strtotime($servico['data_servico'])); ?></small>
        </div>
    </div>

    <!-- Dados do Cliente -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 bg-light rounded border">
                <h6 class="fw-bold text-uppercase text-secondary mb-2">Dados do Cliente</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Nome:</strong> <?php echo htmlspecialchars($servico['cliente']); ?><br>
                        <strong>Telefone:</strong> <?php echo htmlspecialchars($servico['cliente_telefone'] ?? 'Não informado'); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Endereço:</strong> <?php echo htmlspecialchars($servico['cliente_endereco'] ?? 'Não informado'); ?><br>
                        <strong>Status:</strong> <span class="badge bg-secondary"><?php echo $servico['status']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Itens do Serviço -->
    <h5 class="fw-bold mb-3 border-bottom pb-2">Descrição dos Serviços</h5>
    <table class="table table-os table-bordered mb-4">
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="text-center" width="100">Qtd</th>
                <th class="text-end" width="150">Valor Unit.</th>
                <th class="text-end" width="150">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach($servico['itens'] as $item): 
                $totalItem = $item['valor'] * $item['quantidade'];
                $subtotal += $totalItem;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                <td class="text-center"><?php echo $item['quantidade']; ?></td>
                <td class="text-end">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                <td class="text-end">R$ <?php echo number_format($totalItem, 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($servico['itens'])): ?>
            <tr><td colspan="4" class="text-center text-muted">Nenhum serviço registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Peças / Produtos -->
    <?php if(!empty($servico['produtos'])): ?>
    <h5 class="fw-bold mb-3 border-bottom pb-2">Peças e Materiais</h5>
    <table class="table table-os table-bordered mb-4">
        <thead>
            <tr>
                <th>Produto</th>
                <th class="text-center" width="100">Qtd</th>
                <th class="text-end" width="150">Valor Unit.</th>
                <th class="text-end" width="150">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($servico['produtos'] as $prod): 
                $totalProd = $prod['preco_venda'] * $prod['quantidade'];
                $subtotal += $totalProd;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($prod['nome']); ?></td>
                <td class="text-center"><?php echo $prod['quantidade']; ?></td>
                <td class="text-end">R$ <?php echo number_format($prod['preco_venda'], 2, ',', '.'); ?></td>
                <td class="text-end">R$ <?php echo number_format($totalProd, 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Totais -->
    <div class="row justify-content-end">
        <div class="col-md-5">
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-end">Subtotal:</td>
                    <td class="text-end fw-bold">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                </tr>
                <?php if($servico['desconto'] > 0): ?>
                <tr>
                    <td class="text-end text-danger">Desconto:</td>
                    <td class="text-end text-danger">- R$ <?php echo number_format($servico['desconto'], 2, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="border-top">
                    <td class="text-end fs-5 fw-bold text-primary">Total Final:</td>
                    <td class="text-end fs-5 fw-bold text-primary">R$ <?php echo number_format($servico['valor_total'], 2, ',', '.'); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Observações e Garantia -->
    <?php if(!empty($servico['laudo_tecnico'])): ?>
    <div class="p-3 bg-light rounded border mt-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-2">Laudo Técnico / Observações</h6>
        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($servico['laudo_tecnico'])); ?></p>
    </div>
    <?php endif; ?>

    <div class="mt-4 pt-3 border-top">
        <p class="mb-1 small"><strong>Garantia:</strong> <?php echo $servico['garantia']; ?> dias sobre o serviço executado.</p>
        <p class="mb-0 small text-muted">A garantia não cobre peças elétricas queimadas por oscilação de energia ou mau uso. Peças substituídas possuem garantia do fabricante.</p>
    </div>

    <div class="mt-5 pt-5 text-center text-muted" style="font-size: 0.8rem;">
        <p>_______________________________________________________<br>Assinatura do Cliente</p>
    </div>
</div>

<script>
function enviarWhatsApp() {
    // Dados para a mensagem
    let telefone = "<?php echo preg_replace('/[^0-9]/', '', $servico['cliente_telefone'] ?? ''); ?>";
    let nome = "<?php echo htmlspecialchars($servico['cliente']); ?>";
    let id = "<?php echo $servico['id']; ?>";
    let total = "<?php echo number_format($servico['valor_total'], 2, ',', '.'); ?>";
    
    // Monta a mensagem
    let texto = `Olá ${nome}, aqui é da *Dubom Refrigeração*.\n\nSegue o resumo da sua *Ordem de Serviço #${id}*:\n\nValor Total: *R$ ${total}*\nStatus: *<?php echo $servico['status']; ?>*\n\nQualquer dúvida estamos à disposição!`;
    
    // Cria o link (se não tiver telefone cadastrado, abre o whats sem número para a pessoa escolher)
    let url = telefone ? `https://wa.me/55${telefone}?text=${encodeURIComponent(texto)}` : `https://wa.me/?text=${encodeURIComponent(texto)}`;
    
    window.open(url, '_blank');
}
</script>

</body>
</html>