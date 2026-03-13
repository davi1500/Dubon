<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro e Despesas - Dubom</title>
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
    <style>body { background-color: #eef2f6; }</style>
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
    
    <!-- Cabeçalho e Botão de Adicionar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-danger mb-0"><i class="bi bi-wallet2"></i> Despesas / Saídas</h3>
            <p class="text-muted mb-0">Controle de gastos com combustível, alimentação e loja</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-danger mb-0">R$ <?php echo number_format($totalMes, 2, ',', '.'); ?></h5>
            <small class="text-muted">Gasto neste mês</small>
        </div>
    </div>

    <div class="row">
        <!-- Formulário de Lançamento Rápido -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold text-danger">Lançar Nova Despesa</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="<?php echo BASE_URL; ?>/despesas/salvar" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descrição</label>
                            <input type="text" name="descricao" class="form-control form-control-lg bg-light" placeholder="Ex: Gasolina Fiorino" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Valor (R$)</label>
                                <input type="text" name="valor" class="form-control form-control-lg bg-light text-danger fw-bold" placeholder="0,00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Data</label>
                                <input type="date" name="data_despesa" class="form-control form-control-lg bg-light" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Categoria</label>
                            <select name="categoria" class="form-select form-select-lg bg-light">
                                <option value="Transporte">⛽ Transporte / Combustível</option>
                                <option value="Alimentacao">🍔 Alimentação</option>
                                <option value="Loja">🛠️ Loja / Material Consumo</option>
                                <option value="Fixa">📅 Conta Fixa (Luz/Net/Aluguel)</option>
                                <option value="Pessoal">👤 Pessoal / Retirada</option>
                                <option value="Investimento">📈 Investimento / Ferramenta</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observação (Opcional)</label>
                            <input type="text" name="obs" class="form-control bg-light" placeholder="Detalhes...">
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg w-100 rounded-pill shadow-sm">Lançar Saída</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Últimas Despesas -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th class="text-end pe-4">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($despesas)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma despesa lançada.</td></tr>
                                <?php else: ?>
                                    <?php foreach($despesas as $d): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?php echo date('d/m', strtotime($d['data_despesa'])); ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($d['descricao']); ?></div>
                                            <?php if($d['obs']): ?><small class="text-muted"><?php echo htmlspecialchars($d['obs']); ?></small><?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $d['categoria']; ?></span></td>
                                        <td class="fw-bold text-danger">- R$ <?php echo number_format($d['valor'], 2, ',', '.'); ?></td>
                                        <td class="text-end pe-4">
                                            <form action="<?php echo BASE_URL; ?>/despesas/excluir/<?php echo $d['id']; ?>" method="POST" onsubmit="return confirm('Excluir este lançamento?');">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary border-0"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- [NOVO] Seção de Despesas Fixas -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-month"></i> Despesas Fixas (Mensais)</h3>
            <p class="text-muted mb-0">Contas que se repetem todo mês</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModalRecorrente()">
            <i class="bi bi-plus-lg"></i> Nova Despesa Fixa
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Descrição</th>
                            <th>Valor Mensal</th>
                            <th>Dia do Vencimento</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($despesas_recorrentes)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Nenhuma despesa fixa cadastrada.</td></tr>
                        <?php else: ?>
                            <?php foreach($despesas_recorrentes as $dr): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($dr['descricao']); ?></td>
                                <td class="fw-bold text-primary">R$ <?php echo number_format($dr['valor'], 2, ',', '.'); ?></td>
                                <td>Dia <?php echo $dr['dia_vencimento']; ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $dr['categoria']; ?></span></td>
                                <td>
                                    <?php if($dr['ativa']): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-secondary border-0" onclick='editarRecorrente(<?php echo json_encode($dr); ?>)'><i class="bi bi-pencil"></i></button>
                                    <form action="<?php echo BASE_URL; ?>/despesas/recorrente/excluir/<?php echo $dr['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir esta despesa fixa?');">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary border-0"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Despesa Recorrente -->
<div class="modal fade" id="modalRecorrente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Despesa Fixa / Recorrente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/despesas/recorrente/salvar" method="POST" id="formRecorrente">
                    <input type="hidden" name="id_recorrente" id="id_recorrente">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição</label>
                        <input type="text" name="descricao_recorrente" id="descricao_recorrente" class="form-control" placeholder="Ex: Aluguel Oficina" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Valor Mensal (R$)</label>
                            <input type="text" name="valor_recorrente" id="valor_recorrente" class="form-control" placeholder="0,00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" id="dia_vencimento" class="form-control" min="1" max="31" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoria</label>
                        <select name="categoria_recorrente" id="categoria_recorrente" class="form-select">
                            <option value="Fixa">📅 Conta Fixa (Luz/Net/Aluguel)</option>
                            <option value="Transporte">⛽ Transporte / Combustível</option>
                            <option value="Alimentacao">🍔 Alimentação</option>
                            <option value="Loja">🛠️ Loja / Material Consumo</option>
                            <option value="Pessoal">👤 Pessoal / Retirada</option>
                            <option value="Investimento">📈 Investimento / Ferramenta</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativa" name="ativa" checked>
                        <label class="form-check-label" for="ativa">Despesa Ativa (contabilizar nos relatórios)</label>
                    </div>
                    <div class="text-end"><button type="submit" class="btn btn-primary rounded-3 px-4">Salvar</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalRecorrente = new bootstrap.Modal(document.getElementById('modalRecorrente'));
    function abrirModalRecorrente() { 
        document.getElementById('formRecorrente').reset(); 
        document.getElementById('id_recorrente').value = '';
        document.getElementById('ativa').checked = true;
        modalRecorrente.show(); 
    }
    function editarRecorrente(despesa) {
        document.getElementById('id_recorrente').value = despesa.id;
        document.getElementById('descricao_recorrente').value = despesa.descricao;
        document.getElementById('valor_recorrente').value = parseFloat(despesa.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('dia_vencimento').value = despesa.dia_vencimento;
        document.getElementById('categoria_recorrente').value = despesa.categoria;
        document.getElementById('ativa').checked = despesa.ativa == 1;
        modalRecorrente.show();
    }
</script>
</body>
</html>