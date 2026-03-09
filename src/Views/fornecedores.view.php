<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Dubom</title>
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
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-truck"></i> Fornecedores</h3>
            <p class="text-muted mb-0">Gerencie seus parceiros e fornecedores de peças</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
            <i class="bi bi-plus-lg"></i> Novo Fornecedor
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Empresa</th>
                            <th>Contato</th>
                            <th>Telefone/Email</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fornecedores as $f): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($f['nome']); ?></td>
                            <td><?php echo htmlspecialchars($f['contato']); ?></td>
                            <td>
                                <div><i class="bi bi-whatsapp text-success"></i> <?php echo htmlspecialchars($f['telefone']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($f['email']); ?></small>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editar(<?php echo json_encode($f); ?>)'><i class="bi bi-pencil"></i></button>
                                <form action="<?php echo BASE_URL; ?>/fornecedores/excluir/<?php echo $f['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir?');">
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

<div class="modal fade" id="modalFornecedor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/fornecedores/salvar" method="POST" id="formFornecedor">
                    <input type="hidden" name="id" id="fId">
                    <div class="mb-3">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="nome" id="fNome" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Nome Contato</label><input type="text" name="contato" id="fContato" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Telefone</label><input type="text" name="telefone" id="fTelefone" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="fEmail" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" id="fEndereco" class="form-control">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('modalFornecedor'));
    function abrirModal() { document.getElementById('formFornecedor').reset(); document.getElementById('fId').value = ''; modal.show(); }
    function editar(f) { document.getElementById('fId').value = f.id; document.getElementById('fNome').value = f.nome; document.getElementById('fContato').value = f.contato; document.getElementById('fTelefone').value = f.telefone; document.getElementById('fEmail').value = f.email; document.getElementById('fEndereco').value = f.endereco; modal.show(); }
</script>
</body>
</html>