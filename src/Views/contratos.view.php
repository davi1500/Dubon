<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratos PMOC - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <?php
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
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-file-earmark-check"></i> Contratos Recorrentes (PMOC)</h3>
            <p class="text-muted mb-0">Gerencie as manutenções preventivas mensais</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
            <i class="bi bi-plus-lg"></i> Novo Contrato
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Máquinas Cobertas</th>
                            <th>Vencimento</th>
                            <th>Mensalidade</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contratos)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum contrato cadastrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach($contratos as $c): ?>
                        <tr>
                            <td class="fw-bold">
                                <?php echo htmlspecialchars($c['cliente_nome']); ?>
                                <br><small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($c['cliente_telefone']); ?></small>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($c['maquinas_cobertas'])); ?></td>
                            <td><span class="badge bg-secondary">Todo dia <?php echo str_pad($c['dia_vencimento'], 2, '0', STR_PAD_LEFT); ?></span></td>
                            <td class="text-success fw-bold">R$ <?php echo number_format($c['valor_mensal'], 2, ',', '.'); ?></td>
                            <td>
                                <?php if($c['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editar(<?php echo json_encode($c); ?>)'><i class="bi bi-pencil"></i></button>
                                <form action="<?php echo BASE_URL; ?>/contratos/excluir/<?php echo $c['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir este contrato permanentemente?');">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Contrato -->
<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitulo">Novo Contrato PMOC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/contratos/salvar" method="POST" id="formContrato">
                    <input type="hidden" name="id" id="contratoId">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Cliente Contratante</label>
                            <select name="cliente_id" id="contratoCliente" class="form-select" required>
                                <option value="">Selecione um cliente...</option>
                                <?php foreach($clientes as $cli): ?>
                                    <option value="<?php echo $cli['id']; ?>"><?php echo htmlspecialchars($cli['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mensalidade (R$)</label>
                            <input type="number" step="0.01" name="valor_mensal" id="contratoValor" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dia de Vencimento Mensal</label>
                            <input type="number" min="1" max="31" name="dia_vencimento" id="contratoDia" class="form-control" required placeholder="Ex: 5">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Máquinas Cobertas no Contrato (Descrição)</label>
                        <textarea name="maquinas_cobertas" id="contratoMaquinas" class="form-control" rows="3" placeholder="Ex: 4x Splits 9k btus, 1x Piso Teto 36k..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" name="ativo" id="contratoAtivo" value="1" checked>
                        <label class="form-check-label fw-bold" for="contratoAtivo">Contrato Ativo</label>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-light rounded-3 px-4 me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Salvar Contrato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('modalContrato'));
    function abrirModal() { 
        document.getElementById('formContrato').reset(); 
        document.getElementById('contratoId').value = ''; 
        document.getElementById('contratoAtivo').checked = true;
        document.getElementById('modalTitulo').innerText = 'Novo Contrato PMOC';
        modal.show(); 
    }
    function editar(c) { 
        document.getElementById('contratoId').value = c.id; 
        document.getElementById('contratoCliente').value = c.cliente_id; 
        document.getElementById('contratoValor').value = c.valor_mensal; 
        document.getElementById('contratoDia').value = c.dia_vencimento; 
        document.getElementById('contratoMaquinas').value = c.maquinas_cobertas; 
        document.getElementById('contratoAtivo').checked = (c.ativo == 1);
        document.getElementById('modalTitulo').innerText = 'Editar Contrato';
        modal.show(); 
    }
</script>
</body>
</html>
