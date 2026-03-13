<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiais e Compras - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?php
        $favicon = "data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>❄️</text></svg>";
        if (isset($pdo)) {
            $stmtFav = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'empresa_logo'");
            $logoPath = $stmtFav->fetchColumn();
            if ($logoPath && file_exists(__DIR__ . '/../../public' . $logoPath)) { $favicon = BASE_URL . $logoPath; }
        }
    ?>
    <link rel="icon" href="<?php echo $favicon; ?>">
    <style>
        body { background-color: #eef2f6; font-family: 'Segoe UI', sans-serif; }
        .card-material { transition: transform 0.2s; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card-material:hover { transform: translateY(-2px); }
        .status-ok { border-left: 5px solid #198754; } /* Verde */
        .status-comprar { border-left: 5px solid #dc3545; background-color: #fff5f5; } /* Vermelho */
        
        /* Estilos de Impressão (Lista de Compras) */
        @media print {
            body { background-color: white; }
            .no-print, header, .sidebar, .btn, .alert { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .container-impressao { display: block !important; }
            .card-material { box-shadow: none; border: 1px solid #ddd; margin-bottom: 10px; break-inside: avoid; }
            .status-ok { display: none !important; } /* Esconde o que já tem */
            .status-comprar { border: 1px solid #000; background: none; }
            
            /* Título da Impressão */
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <?php
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-cart-check-fill"></i> Materiais de Consumo</h3>
            <p class="text-muted mb-0">Controle o que precisa ser reposto na mala ou oficina</p>
        </div>
        <div>
            <button class="btn btn-outline-dark me-2" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir Lista de Compras
            </button>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
                <i class="bi bi-plus-lg"></i> Adicionar Material
            </button>
        </div>
    </div>

    <!-- Área de Impressão (Cabeçalho) -->
    <div class="print-header">
        <h2>🛒 Lista de Compras - Dubom Refrigeração</h2>
        <p>Gerado em: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="row g-2">
        <?php if(empty($materiais)): ?>
            <div class="col-12 text-center py-5 no-print">
                <i class="bi bi-basket fs-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2">Nenhum material cadastrado.</p>
            </div>
        <?php endif; ?>

        <?php foreach($materiais as $m): ?>
            <?php 
                $isComprar = $m['status'] === 'comprar'; 
                $cardClass = $isComprar ? 'status-comprar' : 'status-ok';
                $btnClass = $isComprar ? 'btn-danger' : 'btn-success';
                $iconClass = $isComprar ? 'bi-cart-x' : 'bi-check-lg';
                $textoStatus = $isComprar ? 'PRECISA COMPRAR' : 'EM ESTOQUE';
            ?>
            <div class="col-12">
                <div class="card card-material p-2 <?php echo $cardClass; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div class="ms-2">
                                <h6 class="fw-bold mb-0 text-truncate <?php echo $isComprar ? 'text-danger' : 'text-dark'; ?>">
                                    <?php echo htmlspecialchars($m['nome']); ?>
                                </h6>
                                <?php if(!empty($m['obs'])): ?>
                                    <small class="text-muted text-truncate d-block"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($m['obs']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 no-print ms-3">
                            <?php if($isComprar): ?>
                                <span class="badge bg-danger me-2 d-none d-md-inline-block">COMPRAR</span>
                            <?php endif; ?>
                            
                            <!-- Botão de Alternar Status -->
                            <form action="<?php echo BASE_URL; ?>/materiais/alternar/<?php echo $m['id']; ?>" method="POST">
                                <button type="submit" class="btn <?php echo $btnClass; ?> btn-sm rounded-pill px-3" title="Mudar Status">
                                    <i class="bi <?php echo $iconClass; ?>"></i>
                                </button>
                            </form>
                            
                            <!-- Botão de Excluir -->
                            <form action="<?php echo BASE_URL; ?>/materiais/excluir/<?php echo $m['id']; ?>" method="POST" onsubmit="return confirm('Remover este material da lista definitivamente?');">
                                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill border-0" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Novo Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Novo Material de Consumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/materiais/salvar" method="POST" id="formMaterial">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Material</label>
                        <input type="text" name="nome" class="form-control form-control-lg bg-light" placeholder="Ex: Fita Isolante 3M" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observação (Opcional)</label>
                        <input type="text" name="obs" class="form-control" placeholder="Ex: Comprar apenas pacote fechado">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Adicionar à Lista</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>const modal = new bootstrap.Modal(document.getElementById('modalMaterial')); function abrirModal() { document.getElementById('formMaterial').reset(); modal.show(); setTimeout(() => document.querySelector('input[name="nome"]').focus(), 500); }</script>
</body>
</html>