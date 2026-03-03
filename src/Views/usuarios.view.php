<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Equipe - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>body { background-color: #eef2f6; }</style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-person-badge-fill"></i> Equipe & Usuários</h3>
            <p class="text-muted mb-0">Gerencie quem tem acesso ao sistema</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
            <i class="bi bi-plus-lg"></i> Novo Usuário
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Usuário (Login)</th>
                            <th>Nível de Acesso</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($u['nome']); ?></td>
                            <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                            <td>
                                <?php if($u['nivel'] === 'admin'): ?>
                                    <span class="badge bg-danger">Administrador</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Funcionário</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editar(<?php echo json_encode($u); ?>)'><i class="bi bi-pencil"></i></button>
                                <form action="<?php echo BASE_URL; ?>/usuarios/excluir/<?php echo $u['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Excluir este usuário?');">
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

<!-- Modal Usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitulo">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/usuarios/salvar" method="POST" id="formUsuario">
                    <input type="hidden" name="id" id="userId">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" id="userNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuário (Login)</label>
                        <input type="text" name="usuario" id="userLogin" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" id="userSenha" class="form-control" placeholder="Deixe em branco para manter a atual">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nível de Acesso</label>
                        <select name="nivel" id="userNivel" class="form-select">
                            <option value="funcionario">Funcionário (Acesso Básico)</option>
                            <option value="admin">Administrador (Acesso Total)</option>
                        </select>
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
    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    function abrirModal() { document.getElementById('formUsuario').reset(); document.getElementById('userId').value = ''; modal.show(); }
    function editar(u) { document.getElementById('userId').value = u.id; document.getElementById('userNome').value = u.nome; document.getElementById('userLogin').value = u.usuario; document.getElementById('userNivel').value = u.nivel; modal.show(); }
</script>
</body>
</html>