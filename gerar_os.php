<?php
session_start();
if (!isset($_SESSION['usuario_nivel'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexao.php';

if (!isset($_GET['id'])) {
    die("ID do serviço não fornecido.");
}

$id = $_GET['id'];

// Busca os dados do serviço e do cliente
$stmt = $pdo->prepare("SELECT s.*, c.telefone, c.endereco 
                       FROM servicos s 
                       LEFT JOIN clientes c ON s.cliente_id = c.id 
                       WHERE s.id = ?");
$stmt->execute([$id]);
$servico = $stmt->fetch();

if (!$servico) {
    die("Serviço não encontrado.");
}

// Busca os itens
$stmtItens = $pdo->prepare("SELECT * FROM servicos_itens WHERE servico_id = ?");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll();

// Formatações
$dataServico = date('d/m/Y', strtotime($servico['data_servico']));
$valorTotal = number_format($servico['valor_total'], 2, ',', '.');
$valorPago = number_format($servico['valor_pago'], 2, ',', '.');
$restante = number_format($servico['valor_total'] - $servico['valor_pago'], 2, ',', '.');

// Define o título do documento baseado no status
$tituloDoc = ($servico['status'] == 'Pago') ? 'RECIBO DE PAGAMENTO' : 'ORDEM DE SERVIÇO';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS #<?php echo $id; ?> - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eee; font-family: 'Segoe UI', sans-serif; -webkit-print-color-adjust: exact; }
        .page { background: white; width: 21cm; min-height: 29.7cm; display: block; margin: 0 auto; padding: 2cm; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .logo-area { font-size: 3rem; line-height: 1; }
        .doc-title { font-weight: 800; letter-spacing: 2px; color: #444; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .box-info { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #eee; }
        .table-itens th { background-color: #f1f1f1 !important; }
        .assinatura-line { border-top: 1px solid #000; width: 80%; margin: 50px auto 10px auto; }
        @media print {
            body { background: white; }
            .page { box-shadow: none; margin: 0; width: 100%; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-4 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="bi bi-printer"></i> Imprimir / Salvar PDF</button>
    <button onclick="window.close()" class="btn btn-secondary btn-lg shadow ms-2">Fechar</button>
</div>

<div class="page">
    <!-- Cabeçalho -->
    <div class="row align-items-center mb-4">
        <div class="col-2 text-center logo-area">❄️</div>
        <div class="col-6">
            <h2 class="fw-bold mb-0">Dubom Refrigeração</h2>
            <small class="text-muted">CNPJ: 00.000.000/0001-00</small><br>
            <small class="text-muted">Instalação e Manutenção de Ar Condicionado e Refrigeração</small><br>
            <small class="text-muted">Contato: (XX) 99999-9999</small> <!-- Ajuste aqui o telefone da empresa -->
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-secondary">Nº <?php echo $id; ?></h5>
            <small>Data: <?php echo $dataServico; ?></small>
        </div>
    </div>

    <h4 class="text-center doc-title"><?php echo $tituloDoc; ?></h4>

    <!-- Dados do Cliente -->
    <div class="box-info">
        <div class="row">
            <div class="col-12 mb-2"><strong>Cliente:</strong> <?php echo $servico['cliente']; ?></div>
            <div class="col-6"><strong>Telefone:</strong> <?php echo $servico['telefone'] ?? 'Não informado'; ?></div>
            <div class="col-12 mt-2"><strong>Endereço:</strong> <?php echo $servico['endereco'] ?? 'Não informado'; ?></div>
        </div>
    </div>

    <!-- Tabela de Serviços -->
    <table class="table table-bordered table-itens mb-4">
        <thead>
            <tr>
                <th>Descrição do Serviço / Peça</th>
                <th class="text-center" width="100">Qtd</th>
                <th class="text-end" width="120">Valor Unit.</th>
                <th class="text-end" width="120">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($itens) > 0): ?>
                <?php foreach($itens as $item): ?>
                <tr>
                    <td><?php echo $item['descricao']; ?></td>
                    <td class="text-center"><?php echo $item['quantidade']; ?></td>
                    <td class="text-end">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                    <td class="text-end">R$ <?php echo number_format($item['valor'] * $item['quantidade'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback para serviços antigos sem itens detalhados -->
                <tr>
                    <td><?php echo $servico['obs'] ?: 'Serviço de Refrigeração'; ?></td>
                    <td class="text-center">1</td>
                    <td class="text-end">R$ <?php echo $valorTotal; ?></td>
                    <td class="text-end">R$ <?php echo $valorTotal; ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold">Total Geral</td>
                <td class="text-end fw-bold bg-light">R$ <?php echo $valorTotal; ?></td>
            </tr>
            <?php if($servico['valor_pago'] > 0): ?>
            <tr>
                <td colspan="3" class="text-end text-success">Valor Pago</td>
                <td class="text-end text-success">R$ <?php echo $valorPago; ?></td>
            </tr>
            <?php endif; ?>
            <?php if(($servico['valor_total'] - $servico['valor_pago']) > 0): ?>
            <tr>
                <td colspan="3" class="text-end text-danger">Restante a Pagar</td>
                <td class="text-end text-danger fw-bold">R$ <?php echo $restante; ?></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <!-- Observações / Laudo -->
    <div class="box-info mb-4">
        <h6 class="fw-bold border-bottom pb-2 mb-2">Observações / Laudo Técnico</h6>
        <p class="mb-0"><?php echo $servico['obs'] ?: 'Serviço realizado conforme solicitado.'; ?></p>
    </div>

    <!-- Termos de Garantia -->
    <div class="mt-4 pt-3 border-top">
        <small class="text-muted d-block">
            <strong>Termos de Garantia:</strong><br>
            1. Garantia de <?php echo $servico['garantia'] ?: '90'; ?> dias referente exclusivamente à mão de obra.<br>
            2. A garantia não cobre peças elétricas queimadas por oscilação de energia ou mau uso.<br>
            3. Peças substituídas possuem garantia do fabricante.
        </small>
    </div>
</div>

</body>
</html>