<?php
// Prepara os grupos para o Kanban
$grupos = [
    'Agendado'     => ['titulo' => 'Agendados', 'cor' => 'info', 'icone' => 'bi-calendar-event', 'lista' => []],
    'Em Andamento' => ['titulo' => 'Em Andamento', 'cor' => 'primary', 'icone' => 'bi-tools', 'lista' => []],
    'Concluido'    => ['titulo' => 'Concluídos (A Receber)', 'cor' => 'warning', 'icone' => 'bi-check-circle', 'lista' => []],
    'Pago'         => ['titulo' => 'Pagos (Total)', 'cor' => 'success', 'icone' => 'bi-cash-coin', 'lista' => []],
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

// Helper para traduzir o dia da semana
$dias_semana_map = [
    'Sunday'    => 'Dom',
    'Monday'    => 'Seg',
    'Tuesday'   => 'Ter',
    'Wednesday' => 'Qua',
    'Thursday'  => 'Qui',
    'Friday'    => 'Sex',
    'Saturday'  => 'Sáb'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dubom Refrigeração</title>
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
        body { background-color: #eef2f6; }
        .card-dashboard { border: none; border-radius: 15px; transition: transform 0.2s; }
        .card-dashboard:hover { transform: translateY(-5px); }
        .kanban-col-header {
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 15px;
        }
        .kanban-col {
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 15px;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.03);
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
    <div class="mb-4">
        <div class="row g-3 align-items-stretch">
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-1 d-flex align-items-center">
                        <div class="input-group input-group-lg border-0 w-100">
                            <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="buscaInput" class="form-control border-0 shadow-none" placeholder="Buscar cliente, OS...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <a href="#col-agendado" class="card card-action text-decoration-none h-100" >
                    <div class="card-body d-flex align-items-center justify-content-between p-2">
                        <div>
                            <h6 class="mb-0 fw-bold text-secondary">Agendados</h6>
                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill mt-1"><?php echo count($grupos['Agendado']['lista']); ?></span>
                        </div>
                        <i class="bi bi-calendar-event fs-3 text-info"></i>
                    </div>
                </a>
            </div>
            <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
            <div class="col-6 col-md-3">
                <a href="#col-concluido" class="card card-action text-decoration-none h-100" >
                    <div class="card-body d-flex align-items-center justify-content-between p-2">
                        <div>
                            <h6 class="mb-0 fw-bold text-secondary">A Receber</h6>
                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-1"><?php echo count($grupos['Concluido']['lista']); ?></span>
                        </div>
                        <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <hr class="my-4">

    <!-- Dashboard (Apenas Admin) -->
    <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-dashboard bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title opacity-75"><i class="bi bi-calendar-check"></i> Faturamento (Este Mês)</h6>
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
                    <h6 class="card-title opacity-75"><i class="bi bi-tools"></i> Em Andamento</h6>
                    <h3 class="fw-bold mb-0"><?php echo $dashboard['em_andamento']; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kanban de Serviços -->
    <div class="row g-4">
        <?php foreach ($grupos as $statusKey => $grupo): ?>
        <div class="col-md-6 col-xl-3" id="col-<?php echo strtolower($statusKey); ?>">
            <div class="kanban-col">
                <div class="kanban-col-header d-flex align-items-center justify-content-between" >
                    <h6 class="fw-bold text-<?php echo $grupo['cor']; ?> mb-0">
                        <span><i class="bi <?php echo $grupo['icone']; ?> me-2"></i> <?php echo $grupo['titulo']; ?></span>
                    </h6>
                    <span class="badge bg-<?php echo $grupo['cor']; ?> rounded-pill"><?php echo count($grupo['lista']); ?></span>
                </div>
                
                <div class="d-flex flex-column gap-3 lista-servicos-grupo">
                    <?php if (empty($grupo['lista'])): ?>
                        <div class="text-center text-muted small py-5 border-2 rounded-3 opacity-50" style="border: 2px dashed #dee2e6;">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhum serviço
                        </div>
                    <?php else: ?>
                        <?php foreach ($grupo['lista'] as $index => $s): ?>
                            <?php $isHidden = $index >= 3; ?>
                            <div class="card bg-white shadow-sm card-servico item-servico <?php echo $isHidden ? 'item-extra' : ''; ?>" 
                                 style="border-left-color: var(--bs-<?php echo $grupo['cor']; ?>); <?php echo $isHidden ? 'display: none;' : ''; ?>"
                                 data-texto="<?php echo strtolower(($s['nome_cliente'] ?? $s['cliente']) . ' ' . $s['obs']); ?>">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <?php
                                            $dia_semana_en = date('l', strtotime($s['data_servico']));
                                            $dia_semana_pt = $dias_semana_map[$dia_semana_en] ?? '';
                                        ?>
                                        <small class="text-muted">
                                            <?php if(!empty($s['servico_pai_id'])): ?>
                                                <span class="badge bg-warning text-dark me-1" title="Retorno de Garantia da OS #<?php echo $s['servico_pai_id']; ?>">
                                                    <i class="bi bi-arrow-repeat"></i> R
                                                </span>
                                            <?php endif; ?>
                                            <span style="font-size: 0.7em; opacity: 0.6;">#<?php echo $s['id']; ?></span>
                                            <span class="fw-bold ms-1"><?php echo $dia_semana_pt; ?>, <?php echo date('d/m', strtotime($s['data_servico'])); ?></span>
                                        </small>
                                        <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                                            <div class="text-end">
                                                <small class="fw-bold text-success">R$ <?php echo number_format($s['valor_total'], 2, ',', '.'); ?></small>
                                                <?php if($s['valor_pago'] > 0 && $s['valor_pago'] < $s['valor_total'] && $s['status'] !== 'Pago'): ?>
                                                    <br><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle" style="font-size: 0.65rem;" title="Valor já adiantado">
                                                        Pago: R$ <?php echo number_format($s['valor_pago'], 2, ',', '.'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($s['nome_cliente'] ?? $s['cliente']); ?>">
                                        <?php echo htmlspecialchars($s['nome_cliente'] ?? $s['cliente']); ?>
                                    </p>
                                    
                                    <!-- [NOVO] Resumo dos Itens (Pequeno) -->
                                    <?php if (!empty($s['resumo_itens'])): ?>
                                        <div class="mb-2" style="font-size: 0.75rem; line-height: 1.3;">
                                            <?php 
                                                $itens_preview = explode('|||', $s['resumo_itens']);
                                                $qtd_preview = count($itens_preview);
                                                $mostrar = array_slice($itens_preview, 0, 2); // Mostra apenas os 2 primeiros
                                            ?>
                                            <?php foreach($mostrar as $item_desc): ?>
                                                <div class="text-truncate text-secondary">
                                                    <i class="bi bi-check2-circle me-1 text-primary" style="opacity: 0.7;"></i><?php echo htmlspecialchars($item_desc); ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if($qtd_preview > 2): ?>
                                                <div class="text-muted fst-italic ps-3">+<?php echo $qtd_preview - 2; ?> item(s)...</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <p class="card-text small text-muted text-truncate mb-2">
                                        <?php echo htmlspecialchars($s['obs'] ?: 'Sem observações'); ?>
                                    </p>
                                    <div class="mt-2 pt-1 border-top d-flex justify-content-end align-items-center gap-1">
                                        <a href="<?php echo BASE_URL; ?>/servicos/editar/<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="Editar Ordem de Serviço"><i class="bi bi-pencil"></i></a>
                                        <a href="<?php echo BASE_URL; ?>/gerar_os.php?id=<?php echo $s['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Imprimir OS"><i class="bi bi-printer"></i></a>
                                        <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                                            <form action="<?php echo BASE_URL; ?>/servicos/excluir/<?php echo $s['id']; ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta OS? A ação não pode ser desfeita.');" class="d-inline">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Excluir OS"><i class="bi bi-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($grupo['lista']) > 3): ?>
                            <button class="btn btn-sm btn-light w-100 text-muted mt-2 btn-ver-mais" onclick="mostrarMais(this)">
                                <i class="bi bi-chevron-down"></i> Ver mais (<?php echo count($grupo['lista']) - 3; ?>)
                            </button>
                        <?php endif; ?>
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

    // Função para mostrar itens ocultos
    function mostrarMais(btn) {
        const parent = btn.parentElement;
        const extras = parent.querySelectorAll('.item-extra');
        extras.forEach(el => el.style.display = 'block');
        btn.style.display = 'none';
    }

    // Filtro por texto (com reset do estado "Ver mais")
    document.getElementById('buscaInput').addEventListener('input', function() {
        const termo = this.value.toLowerCase();
        const isSearching = termo.length > 0;

        // Se estiver buscando, esconde os botões "Ver mais" para não confundir
        document.querySelectorAll('.btn-ver-mais').forEach(btn => {
            btn.style.display = isSearching ? 'none' : 'block';
        });

        if (isSearching) {
            // Modo Busca: Mostra tudo que corresponde, ignorando o limite
            document.querySelectorAll('.item-servico').forEach(card => {
                const texto = card.getAttribute('data-texto');
                if (texto.includes(termo)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        } else {
            // Limpou a busca: Restaura o estado inicial (oculta > 3)
            document.querySelectorAll('.lista-servicos-grupo').forEach(col => {
                const items = col.querySelectorAll('.item-servico');
                items.forEach((item, index) => {
                    if (index >= 3) {
                        item.style.display = 'none';
                    } else {
                        item.style.display = 'block';
                    }
                });
            });
        }
    });
</script>
</body>
</html>