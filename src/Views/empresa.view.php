<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados da Empresa - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>body { background-color: #eef2f6; }</style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-building-gear"></i> Dados da Empresa</h3>
            <p class="text-muted mb-0">Edite as informações que aparecem na Ordem de Serviço</p>
        </div>
    </div>

    <form action="<?php echo BASE_URL; ?>/empresa/salvar" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm p-4 rounded-4">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Nome da Empresa</label>
                        <input type="text" name="empresa_nome" class="form-control form-control-lg" value="<?php echo htmlspecialchars($config['empresa_nome']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Slogan / Descrição Curta</label>
                        <input type="text" name="empresa_slogan" class="form-control" value="<?php echo htmlspecialchars($config['empresa_slogan']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">CNPJ</label>
                        <input type="text" name="empresa_cnpj" class="form-control" value="<?php echo htmlspecialchars($config['empresa_cnpj']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Telefone Principal</label>
                        <input type="text" name="empresa_telefone" class="form-control" value="<?php echo htmlspecialchars($config['empresa_telefone']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Email de Contato</label>
                        <input type="email" name="empresa_email" class="form-control" value="<?php echo htmlspecialchars($config['empresa_email']); ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Logo da Empresa</label>
                <div class="card text-center bg-light">
                    <div class="card-body">
                        <?php if (!empty($config['empresa_logo']) && file_exists(__DIR__ . '/../../public' . $config['empresa_logo'])): ?>
                            <img src="<?php echo BASE_URL . $config['empresa_logo']; ?>" alt="Logo Atual" class="img-fluid rounded mb-3" style="max-height: 150px;">
                        <?php else: ?>
                            <div class="py-4">
                                <i class="bi bi-image-alt fs-1 text-muted"></i>
                                <p class="text-muted small">Nenhum logo enviado</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="empresa_logo" class="form-control" accept="image/png, image/jpeg">
                        <small class="form-text text-muted">Envie uma imagem (PNG, JPG). A imagem antiga será substituída.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end mt-4">
            <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow">Salvar Informações</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>