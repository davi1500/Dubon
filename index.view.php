<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dubom Refrigeração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Estilos específicos da página inicial */
        .card-dashboard { border: none; border-radius: 15px; transition: transform 0.2s; }
        .card-dashboard:hover { transform: translateY(-5px); }
        .status-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    
    <!-- Cabeçalho e Botão Novo -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-week"></i> Agenda de Serviços</h3>
            <p class="text-muted mb-0">Gerencie seus atendimentos</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoServico">
            <i class="bi bi-plus-lg"></i> Novo Serviço
        </button>
    </div>

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
                    <h6 class="card-title opacity-75"><i class="bi bi-clipboard-check"></i> Total de Serviços</h6>
                    <h3 class="fw-bold mb-0"><?php echo count($servicos); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros de Busca -->
    <div class="card border-0 shadow-sm p-3 mb-4 rounded-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscaInput" class="form-control border-start-0" placeholder="Buscar cliente, serviço...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroStatus" class="form-select">
                    <option value="">Todos os Status</option>
                    <option value="Agendado">Agendado</option>
                    <option value="Em Andamento">Em Andamento</option>
                    <option value="Concluido">Concluído</option>
                    <option value="Pago">Pago</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="filtroData" class="form-control">
            </div>
        </div>
    </div>

    <!-- Lista de Serviços -->
    <div class="row">
        <?php if (empty($servicos)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Nenhum serviço encontrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($servicos as $s): ?>
                <?php 
                    // Definição de cores por status
                    $statusClass = 'bg-secondary';
                    if ($s['status'] == 'Agendado') $statusClass = 'bg-info text-dark';
                    if ($s['status'] == 'Em Andamento') $statusClass = 'bg-warning text-dark';
                    if ($s['status'] == 'Concluido') $statusClass = 'bg-primary';
                    if ($s['status'] == 'Pago') $statusClass = 'bg-success';
                ?>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title fw-bold text-truncate"><?php echo htmlspecialchars($s['nome_cliente'] ?? $s['cliente']); ?></h5>
                                <span class="badge <?php echo $statusClass; ?> status-badge"><?php echo $s['status']; ?></span>
                            </div>
                            <p class="card-text text-muted">
                                <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($s['data_servico'])); ?>
                            </p>
                            <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                                <p class="fw-bold text-success mb-0">
                                    R$ <?php echo number_format($s['valor_total'], 2, ',', '.'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Serviço (Placeholder - Implementaremos a lógica no próximo passo) -->
<div class="modal fade" id="modalNovoServico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Formulário será implementado aqui.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>