<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos e Estoque - Dubom</title>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-box-seam"></i> Produtos</h3>
            <p class="text-muted mb-0">Controle de estoque e peças</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
            <i class="bi bi-plus-lg"></i> Novo Produto
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Estoque</th>
                            <th>Custo</th>
                            <th>Venda</th>
                            <th>Fornecedor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produtos as $p): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($p['nome']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($p['descricao']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $p['estoque'] > 0 ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $p['estoque']; ?> un
                                </span>
                            </td>
                            <td class="text-muted">R$ <?php echo number_format($p['preco_custo'], 2, ',', '.'); ?></td>
                            <td class="fw-bold text-dark">R$ <?php echo number_format($p['preco_venda'], 2, ',', '.'); ?></td>
                            <td><small><?php echo htmlspecialchars($p['nome_fornecedor'] ?: '-'); ?></small></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editar(<?php echo json_encode($p); ?>)'><i class="bi bi-pencil"></i></button>
                                <form action="<?php echo BASE_URL; ?>/produtos/excluir/<?php echo $p['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir?');">
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

<div class="modal fade" id="modalProduto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/produtos/salvar" method="POST" id="formProduto">
                    <input type="hidden" name="id" id="pId">
                    <div class="mb-3">
                        <label class="form-label">Nome do Produto</label>
                        <input type="text" name="nome" id="pNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" id="pDesc" class="form-control">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4"><label class="form-label">Custo (R$)</label><input type="number" name="preco_custo" id="pCusto" class="form-control" step="0.01"></div>
                        <div class="col-4"><label class="form-label">Venda (R$)</label><input type="number" name="preco_venda" id="pVenda" class="form-control" step="0.01"></div>
                        <div class="col-4"><label class="form-label">Estoque</label><input type="number" name="estoque" id="pEstoque" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fornecedor</label>
                        <select name="fornecedor_id" id="pFornecedor" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach($fornecedores as $f): ?>
                                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end"><button type="submit" class="btn btn-primary rounded-3 px-4">Salvar</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>const modal = new bootstrap.Modal(document.getElementById('modalProduto')); function abrirModal() { document.getElementById('formProduto').reset(); document.getElementById('pId').value = ''; modal.show(); } function editar(p) { document.getElementById('pId').value = p.id; document.getElementById('pNome').value = p.nome; document.getElementById('pDesc').value = p.descricao; document.getElementById('pCusto').value = p.preco_custo; document.getElementById('pVenda').value = p.preco_venda; document.getElementById('pEstoque').value = p.estoque; document.getElementById('pFornecedor').value = p.fornecedor_id; modal.show(); }</script>
</body>
</html>