<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #eef2f6; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../../_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-people-fill"></i> Clientes</h3>
            <p class="text-muted mb-0">Gerencie sua base de clientes</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModal()">
            <i class="bi bi-plus-lg"></i> Novo Cliente
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            
            <!-- Barra de Busca -->
            <div class="input-group input-group-lg mb-4 border rounded-3 overflow-hidden">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="buscaCliente" class="form-control border-0 shadow-none" placeholder="Buscar por nome, CPF, CNPJ ou telefone...">
            </div>

            <!-- Lista de Clientes -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nome / Razão Social</th>
                            <th>Documento (CPF/CNPJ)</th>
                            <th>Contato</th>
                            <th>Endereço</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="listaClientes">
                        <?php foreach($clientes as $c): ?>
                        <tr class="item-cliente" data-texto="<?php echo strtolower($c['nome'] . ' ' . $c['razao_social'] . ' ' . $c['cpf'] . ' ' . $c['cnpj'] . ' ' . $c['telefone']); ?>">
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['nome']); ?></div>
                                <?php if(!empty($c['razao_social'])): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($c['razao_social']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($c['cpf'] ?: $c['cnpj'] ?: '-'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($c['telefone'] ?: '-'); ?></td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($c['endereco']); ?>">
                                <?php echo htmlspecialchars($c['endereco'] ?: '-'); ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editarCliente(<?php echo json_encode($c); ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="<?php echo BASE_URL; ?>/clientes/excluir/<?php echo $c['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
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

<!-- Modal de Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitulo">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo BASE_URL; ?>/clientes/salvar" method="POST" id="formCliente">
                    <input type="hidden" name="id" id="clienteId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="clienteNome" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Razão Social (Opcional)</label>
                            <input type="text" name="razao_social" id="clienteRazao" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">CPF</label>
                            <input type="text" name="cpf" id="clienteCpf" class="form-control" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">CNPJ</label>
                            <input type="text" name="cnpj" id="clienteCnpj" class="form-control" placeholder="00.000.000/0000-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Telefone / WhatsApp</label>
                            <input type="text" name="telefone" id="clienteTelefone" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Endereço Completo</label>
                            <input type="text" name="endereco" id="clienteEndereco" class="form-control" placeholder="Rua, Número, Bairro, Cidade">
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-secondary rounded-3 me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalEl = document.getElementById('modalCliente');
    const modal = new bootstrap.Modal(modalEl);

    function abrirModal() {
        document.getElementById('formCliente').reset();
        document.getElementById('clienteId').value = '';
        document.getElementById('modalTitulo').innerText = 'Novo Cliente';
        modal.show();
    }

    function editarCliente(c) {
        document.getElementById('clienteId').value = c.id;
        document.getElementById('clienteNome').value = c.nome;
        document.getElementById('clienteRazao').value = c.razao_social || '';
        document.getElementById('clienteCpf').value = c.cpf || '';
        document.getElementById('clienteCnpj').value = c.cnpj || '';
        document.getElementById('clienteTelefone').value = c.telefone || '';
        document.getElementById('clienteEndereco').value = c.endereco || '';
        
        document.getElementById('modalTitulo').innerText = 'Editar Cliente';
        modal.show();
    }

    // Filtro de Busca
    document.getElementById('buscaCliente').addEventListener('input', function() {
        const termo = this.value.toLowerCase();
        document.querySelectorAll('.item-cliente').forEach(tr => {
            const texto = tr.getAttribute('data-texto');
            tr.style.display = texto.includes(termo) ? '' : 'none';
        });
    });
</script>
</body>
</html>