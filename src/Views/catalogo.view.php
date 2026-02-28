<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Serviços - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #eef2f6; font-family: 'Segoe UI', sans-serif; }
        .table-custom { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-tags-fill"></i> Meus Serviços</h3>
            <p class="text-muted mb-0">Gerencie preços e custos dos seus serviços</p>
        </div>
    </div>

    <div class="row">
        <!-- Coluna da Esquerda: Formulário e Lista -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body px-4 pt-4">
                    
                    <!-- Formulário de Adição Rápida -->
                    <form action="<?php echo BASE_URL; ?>/catalogo/salvar" method="POST" class="row g-2 mb-4 align-items-end p-3 bg-light rounded-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Nome do Serviço</label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: Limpeza Split" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Categoria (Ícone)</label>
                            <select name="categoria_id" class="form-select">
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo $cat['icone_emoji'] . ' ' . $cat['nome']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Custo (R$)</label>
                            <input type="number" name="custo" class="form-control" placeholder="0,00" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Venda (R$)</label>
                            <input type="number" name="valor" class="form-control" placeholder="0,00" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>

                    <!-- Lista -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">Ícone</th>
                                    <th>Serviço</th>
                                    <th>Custo</th>
                                    <th>Venda</th>
                                    <th>Lucro Estimado</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($catalogo)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhum serviço cadastrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach($catalogo as $item): 
                                        $lucro = $item['valor'] - $item['custo'];
                                    ?>
                                    <tr>
                                        <td class="text-center fs-5"><?php echo $item['icone_emoji']; ?></td>
                                        <td class="fw-bold text-secondary"><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td class="text-danger">R$ <?php echo number_format($item['custo'], 2, ',', '.'); ?></td>
                                        <td class="text-dark">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                                        <td class="text-success fw-bold">R$ <?php echo number_format($lucro, 2, ',', '.'); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick='editarItem(<?php echo json_encode($item); ?>)'><i class="bi bi-pencil"></i></button>
                                            <form action="<?php echo BASE_URL; ?>/catalogo/excluir/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza?');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditarItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Editar Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo BASE_URL; ?>/catalogo/salvar" method="POST" id="formEditar">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Nome do Serviço</label>
                            <input type="text" name="nome" id="editNome" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="categoria_id" id="editCategoria" class="form-select bg-light">
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo $cat['icone_emoji'] . ' ' . $cat['nome']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Custo</label><input type="number" name="custo" id="editCusto" class="form-control" step="0.01"></div>
                            <div class="col-6"><label class="form-label">Venda</label><input type="number" name="valor" id="editValor" class="form-control" step="0.01"></div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary rounded-3 me-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary rounded-3 px-4">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editarItem(item) {
        document.getElementById('editId').value = item.id;
        document.getElementById('editNome').value = item.nome;
        document.getElementById('editCategoria').value = item.categoria_id;
        document.getElementById('editCusto').value = item.custo;
        document.getElementById('editValor').value = item.valor;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarItem'));
        modal.show();
    }
</script>
</body>
</html>