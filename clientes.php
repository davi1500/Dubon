<?php
session_start();
if (!isset($_SESSION['usuario_nivel'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexao.php';

// API para buscar histórico de um cliente específico
if (isset($_GET['acao']) && $_GET['acao'] === 'historico' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM servicos WHERE cliente_id = ? ORDER BY data_servico DESC");
    $stmt->execute([$_GET['id']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// Busca todos os clientes com contagem de serviços e total gasto
$sql = "SELECT c.*, 
        COUNT(s.id) as total_servicos, 
        SUM(s.valor_total) as total_gasto,
        MAX(s.data_servico) as ultima_visita
        FROM clientes c 
        LEFT JOIN servicos s ON c.id = s.cliente_id 
        GROUP BY c.id 
        ORDER BY c.nome ASC";
$stmt = $pdo->query($sql);
$clientes = $stmt->fetchAll();
?>
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
        .card-cliente { cursor: pointer; transition: transform 0.2s; border: none; border-radius: 12px; }
        .card-cliente:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .avatar-circle { width: 45px; height: 45px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #555; }
    </style>
</head>
<body>

<?php require_once '_partials/menu.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0"><i class="bi bi-people-fill"></i> Meus Clientes</h3>
    </div>

    <!-- Busca -->
    <div class="input-group mb-4 shadow-sm">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
        <input type="text" id="buscaCliente" class="form-control border-start-0" placeholder="Buscar por nome ou telefone...">
    </div>

    <!-- Lista de Clientes -->
    <div class="row g-3" id="listaClientes">
        <?php foreach($clientes as $c): ?>
            <div class="col-md-6 col-lg-4 item-cliente" data-nome="<?php echo strtolower($c['nome']); ?>" data-tel="<?php echo $c['telefone']; ?>">
                <div class="card card-cliente p-3 bg-white h-100 shadow-sm" onclick="verHistorico(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nome']); ?>')">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3 fs-5">
                            <?php echo strtoupper(substr($c['nome'], 0, 1)); ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="fw-bold mb-0 text-truncate"><?php echo $c['nome']; ?></h6>
                            <small class="text-muted d-block">
                                <i class="bi bi-telephone"></i> <?php echo $c['telefone'] ? $c['telefone'] : 'Sem telefone'; ?>
                            </small>
                            <small class="text-muted d-block text-truncate">
                                <i class="bi bi-geo-alt"></i> <?php echo $c['endereco'] ? $c['endereco'] : 'Sem endereço'; ?>
                            </small>
                        </div>
                        <div class="text-end ms-2">
                            <span class="badge bg-primary rounded-pill"><?php echo $c['total_servicos']; ?> serviços</span>
                            <?php if($_SESSION['usuario_nivel'] === 'admin'): ?>
                                <div class="text-success fw-bold small mt-1" style="font-size: 0.75rem;">R$ <?php echo number_format($c['total_gasto'], 2, ',', '.'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($c['ultima_visita']): ?>
                        <div class="mt-2 pt-2 border-top text-muted small">
                            Última visita: <?php echo date('d/m/Y', strtotime($c['ultima_visita'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Histórico -->
<div class="modal fade" id="modalHistorico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTitulo">Histórico</h5>
                    <p class="text-muted small mb-0">Lista de atendimentos realizados</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="modalCorpo">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filtro de Busca
    document.getElementById('buscaCliente').addEventListener('input', function() {
        const termo = this.value.toLowerCase();
        document.querySelectorAll('.item-cliente').forEach(el => {
            const nome = el.getAttribute('data-nome');
            const tel = el.getAttribute('data-tel') || '';
            if (nome.includes(termo) || tel.includes(termo)) {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        });
    });

    // Ver Histórico
    async function verHistorico(id, nome) {
        const modal = new bootstrap.Modal(document.getElementById('modalHistorico'));
        document.getElementById('modalTitulo').innerText = nome;
        document.getElementById('modalCorpo').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modal.show();

        try {
            const res = await fetch(`?acao=historico&id=${id}`);
            const servicos = await res.json();
            
            let html = '';
            if (servicos.length === 0) {
                html = '<div class="alert alert-info text-center">Nenhum serviço encontrado.</div>';
            } else {
                servicos.forEach(s => {
                    // Formata data
                    const dataPartes = s.data_servico ? s.data_servico.split('-') : [];
                    const dataFmt = dataPartes.length === 3 ? `${dataPartes[2]}/${dataPartes[1]}/${dataPartes[0]}` : 'Data inválida';
                    
                    // Cor do status
                    let badgeClass = 'bg-secondary';
                    if (s.status === 'Concluido') badgeClass = 'bg-primary';
                    if (s.status === 'Pago') badgeClass = 'bg-success';
                    if (s.status === 'Em Andamento') badgeClass = 'bg-warning text-dark';

                    html += `
                        <div class="card mb-2 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge ${badgeClass} mb-1">${s.status}</span>
                                        <h6 class="fw-bold mb-1 text-secondary">${dataFmt}</h6>
                                    </div>
                                    <div class="fw-bold text-dark">R$ ${parseFloat(s.valor_total).toFixed(2)}</div>
                                </div>
                                <p class="mb-0 mt-2 text-muted small">${s.obs || 'Sem observações'}</p>
                            </div>
                        </div>
                    `;
                });
            }
            document.getElementById('modalCorpo').innerHTML = html;

        } catch (err) {
            console.error(err);
            document.getElementById('modalCorpo').innerHTML = '<div class="alert alert-danger">Erro ao carregar histórico.</div>';
        }
    }
</script>
</body>
</html>