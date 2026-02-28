<?php
// Prepara os grupos para o Kanban
$grupos = [
    'Agendado'     => ['titulo' => 'Agendados', 'cor' => 'info', 'icone' => 'bi-calendar-event', 'lista' => []],
    'Em Andamento' => ['titulo' => 'Em Andamento', 'cor' => 'warning', 'icone' => 'bi-tools', 'lista' => []],
    'Concluido'    => ['titulo' => 'Concluídos', 'cor' => 'primary', 'icone' => 'bi-check-circle', 'lista' => []],
    'Pago'         => ['titulo' => 'Pagos', 'cor' => 'success', 'icone' => 'bi-cash-coin', 'lista' => []],
];

// Distribui os serviços nos grupos
foreach ($servicos as $s) {
    $st = $s['status'];
    if (isset($grupos[$st])) {
        $grupos[$st]['lista'][] = $s;
    } else {
        $grupos['Agendado']['lista'][] = $s; // Fallback para status desconhecido
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dubom Refrigeração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #eef2f6; }
        .card-dashboard { border: none; border-radius: 15px; transition: transform 0.2s; }
        .card-dashboard:hover { transform: translateY(-5px); }
        .kanban-col-header {
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .card-servico { transition: all 0.2s; border-radius: 8px; border: 1px solid #e9ecef; border-left-width: 4px; }
        .card-servico:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,.08)!important; border-color: #ccc; }
        .card-action {
            --bs-card-action-border-color:  #e9ecef;
            --bs-card-action-color:  #343a40;
            --bs-card-action-hover-color:  #343a40;

            border: 1px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.2s;
            color: #343a40;
        }
        .card-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: var(--bs-primary);
        }
        .fab {
            --fab-bg:  var(--bs-primary);
            --fab-color:  #fff;


            position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px;
            border-radius: 50%; background-color: var(--bs-primary); color: white;
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1050; text-decoration: none;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">

    <!-- Centro de Ações (Mobile First) -->
    <div class="text-center mb-5" >
        <h1 class="display-5 fw-bold text-primary">Painel de Controle</h1>
        <p class="lead text-muted">Gerencie suas Ordens de Serviço.</p>

        <div class="card border-0 shadow-sm p-3 my-4 rounded-4 mx-auto" style="max-width: 600px;">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="buscaInput" class="form-control border-start-0" placeholder="Buscar por cliente ou OS...">
            </div>
        </div>

        <div class="row g-3 justify-content-center">
            <div class="col-6 col-md-3">
                <a href="#col-agendado" class="card card-action text-decoration-none h-100" >
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-calendar-event display-6 text-info"></i>
                        <h6 class="mt-2 mb-0">Agendados</h6>
                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill mt-1"><?php echo count($grupos['Agendado']['lista']); ?></span>
                    </div>
                </a>
            </div>
            <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
            <div class="col-6 col-md-3">
                <a href="#col-concluido" class="card card-action text-decoration-none h-100" >
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-hourglass-split display-6 text-warning"></i>
                        <h6 class="mt-2 mb-0">A Receber</h6>
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-1"><?php echo count($grupos['Concluido']['lista']); ?></span>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <hr class="my-5">

    <!-- Dashboard (Apenas Admin) -->
    <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-dashboard bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title opacity-75"><i class="bi bi-cash-coin"></i> Faturamento (Recebido)</h6>
                    <h3 class="fw-bold mb-0">R$ <?php echo number_format($dashboard['faturamento'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard bg-warning text-dark shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title opacity-75"><i class="bi bi-hourglass-split"></i> A Receber (Pendente)</h6>
                    <h3 class="fw-bold mb-0">R$ <?php echo number_format($dashboard['pendente'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard bg-primary text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title opacity-75"><i class="bi bi-clipboard-check"></i> Total de OS</h6>
                    <h3 class="fw-bold mb-0"><?php echo count($servicos); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Título da Visão Geral -->
    <h4 class="text-center text-muted mb-4">Visão Geral das Ordens de Serviço</h4>

    <!-- Kanban de Serviços -->
    <div class="row g-4">
        <?php foreach ($grupos as $statusKey => $grupo): ?>
        <div class="col-md-6 col-xl-3" id="col-<?php echo strtolower($statusKey); ?>">
            <div class="kanban-col">
                <div class="kanban-col-header mb-3 d-flex align-items-center justify-content-between" >
                    <h6 class="fw-bold text-<?php echo $grupo['cor']; ?> mb-0">
                        <span><i class="bi <?php echo $grupo['icone']; ?> me-2"></i> <?php echo $grupo['titulo']; ?></span>
                    </h6>
                    <span class="badge bg-<?php echo $grupo['cor']; ?> rounded-pill"><?php echo count($grupo['lista']); ?></span>
                </div>
                
                <div class="d-flex flex-column gap-3 lista-servicos-grupo">
                    <?php if (empty($grupo['lista'])): ?>
                        <div class="text-center text-muted small py-4 border-2 rounded-3" style="border-style: dashed !important; background-color: rgba(0,0,0,0.02);">
                            Vazio
                        </div>
                    <?php else: ?>
                        <?php foreach ($grupo['lista'] as $s): ?>
                            <div class="card bg-white shadow-sm card-servico item-servico" 
                                 style="border-left-color: var(--bs-<?php echo $grupo['cor']; ?>);"
                                 data-texto="<?php echo strtolower(($s['nome_cliente'] ?? $s['cliente']) . ' ' . $s['obs']); ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted fw-bold">#<?php echo $s['id']; ?> - <?php echo date('d/m', strtotime($s['data_servico'])); ?></small>
                                        <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                                            <small class="fw-bold text-success">R$ <?php echo number_format($s['valor_total'], 2, ',', '.'); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <p class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($s['nome_cliente'] ?? $s['cliente']); ?>">
                                        <?php echo htmlspecialchars($s['nome_cliente'] ?? $s['cliente']); ?>
                                    </p>
                                    <p class="card-text small text-muted text-truncate mb-0">
                                        <?php echo htmlspecialchars($s['obs'] ?: 'Sem observações'); ?>
                                    </p>
                                    <div class="mt-3 pt-2 border-top d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/servicos/editar/<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="Editar Ordem de Serviço"><i class="bi bi-pencil"></i></a>
                                        <a href="<?php echo BASE_URL; ?>/gerar_os.php?id=<?php echo $s['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Imprimir OS"><i class="bi bi-printer"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Botão Flutuante para Novo Serviço -->
<a href="<?php echo BASE_URL; ?>/servicos/novo" class="fab" title="Nova Ordem de Serviço">
    <i class="bi bi-plus-lg"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    // Filtro simples por texto
    document.getElementById('buscaInput').addEventListener('input', function() {
        const termo = this.value.toLowerCase();
        document.querySelectorAll('.item-servico').forEach(card => {
            const texto = card.getAttribute('data-texto');
            if (texto.includes(termo)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>