<?php
session_start();
if (!isset($_SESSION['usuario_nivel'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/conexao.php';

if (!isset($_GET['id'])) {
    die("ID do serviço não fornecido.");
}

$id = (int)$_GET['id']; // Cast to integer to prevent SQL injection and other attacks.
$safe_id = htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); // Sanitize for HTML output

// Busca os dados do serviço e do cliente
$stmt = $pdo->prepare("SELECT s.*, c.nome as nome_cliente_cadastrado, c.telefone, c.endereco, c.cpf, c.cnpj, c.razao_social
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

// Busca as configurações da empresa
$stmtConfig = $pdo->query("SELECT * FROM configuracoes");
$config_raw = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);
$config = array_merge([
    'empresa_nome' => 'Dubom Refrigeração',
    'empresa_cnpj' => '00.000.000/0001-00',
    'empresa_telefone' => '(00) 90000-0000',
    'empresa_email' => 'contato@suaempresa.com',
    'empresa_slogan' => 'Slogan da sua empresa',
    'empresa_logo' => ''
], $config_raw);

// Define o título do documento baseado no status
$tituloDoc = ($servico['status'] == 'Pago') ? 'RECIBO DE PAGAMENTO' : 'ORDEM DE SERVIÇO';

// --- Lógica para o WhatsApp ---
$telefoneLimpo = '';
if (!empty($servico['telefone'])) {
    // Remove tudo que não for número
    $telefoneLimpo = preg_replace('/[^0-9]/', '', $servico['telefone']);
    // Adiciona o código do país (Brasil) se não tiver
    if (strlen($telefoneLimpo) >= 10 && substr($telefoneLimpo, 0, 2) !== '55') {
        $telefoneLimpo = '55' . $telefoneLimpo;
    }
}

// Monta a mensagem para o WhatsApp
$nomeCliente = htmlspecialchars($servico['nome_cliente_cadastrado'] ?? $servico['cliente']);
$mensagemWpp = "Olá, *{$nomeCliente}*! 👋\n\n";
$mensagemWpp .= "Segue o resumo da sua *{$tituloDoc} Nº {$id}* da Dubom Refrigeração:\n\n";

foreach($itens as $item) {
    $mensagemWpp .= "✅ " . $item['descricao'] . " (Qtd: " . $item['quantidade'] . ")\n";
}
if (empty($itens)) { // Fallback para OSs antigas
    $mensagemWpp .= "✅ " . ($servico['obs'] ?: 'Serviço de Refrigeração') . "\n";
}

$mensagemWpp .= "\n*Valor Total: R$ {$valorTotal}*\n";
$mensagemWpp .= "Garantia: " . ($servico['garantia'] ?: '0') . " dias\n\n";
$mensagemWpp .= "Qualquer dúvida, estamos à disposição!";
$linkWpp = "https://wa.me/{$telefoneLimpo}?text=" . urlencode($mensagemWpp);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS #<?php echo $safe_id; ?> - Dubom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php
        $favicon = "data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>❄️</text></svg>";
        if (!empty($config['empresa_logo']) && file_exists(__DIR__ . '/public' . $config['empresa_logo'])) {
            $favicon = BASE_URL . $config['empresa_logo'];
        }
    ?>
    <link rel="icon" href="<?php echo $favicon; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

<div class="container py-4 no-print">
    <div class="d-flex flex-wrap justify-content-center gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="bi bi-printer"></i> Imprimir / PDF</button>
        <button onclick="sharePDF(this)" class="btn btn-danger btn-lg shadow"><i class="bi bi-share-fill"></i> Compartilhar PDF</button>
        <?php if (!empty($telefoneLimpo)): ?>
            <a href="<?php echo $linkWpp; ?>" target="_blank" class="btn btn-success btn-lg shadow">
                <i class="bi bi-whatsapp"></i> Enviar via WhatsApp
            </a>
        <?php endif; ?>
        <button onclick="window.close()" class="btn btn-secondary btn-lg shadow">Fechar</button>
    </div>
</div>

<div class="page">
    <!-- Cabeçalho -->
    <div class="row align-items-center mb-4">
        <div class="col-2 text-center logo-area">
            <?php if (!empty($config['empresa_logo']) && file_exists(__DIR__ . '/public' . $config['empresa_logo'])): ?>
                <img src="<?php echo BASE_URL . $config['empresa_logo']; ?>" alt="Logo" style="max-width: 100%; max-height: 80px;">
            <?php else: ?>
                ❄️
            <?php endif; ?>
        </div>
        <div class="col-6">
            <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($config['empresa_nome']); ?></h3>
            <?php if (!empty($config['empresa_cnpj'])): ?><small class="text-muted d-block">CNPJ: <?php echo htmlspecialchars($config['empresa_cnpj']); ?></small><?php endif; ?>
            <?php if (!empty($config['empresa_slogan'])): ?><small class="text-muted d-block"><?php echo htmlspecialchars($config['empresa_slogan']); ?></small><?php endif; ?>
            <?php if (!empty($config['empresa_telefone'])): ?><small class="text-muted d-block">Contato: <?php echo htmlspecialchars($config['empresa_telefone']); ?> | <?php echo htmlspecialchars($config['empresa_email']); ?></small><?php endif; ?>
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-secondary">Nº <?php echo $safe_id; ?></h5>
            <small>Data: <?php echo $dataServico; ?></small>
        </div>
    </div>

    <h4 class="text-center doc-title"><?php echo $tituloDoc; ?></h4>

    <!-- Dados do Cliente -->
    <div class="box-info">
        <div class="row">
            <div class="col-md-8 mb-2">
                <strong>Cliente:</strong> <?php echo htmlspecialchars($servico['nome_cliente_cadastrado'] ?? $servico['cliente']); ?>
                <?php if (!empty($servico['razao_social'])): ?>
                    <br><small class="text-muted">Razão Social: <?php echo htmlspecialchars($servico['razao_social']); ?></small>
                <?php endif; ?>
            </div>
            <?php $documento = $servico['cpf'] ?: $servico['cnpj']; ?>
            <?php if (!empty($documento)): ?>
                <div class="col-md-4 mb-2">
                    <strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($documento); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($servico['endereco'])): ?>
                <div class="col-md-8">
                    <strong>Endereço:</strong> <?php echo htmlspecialchars($servico['endereco']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($servico['telefone'])): ?>
                <div class="col-md-4">
                    <strong>Telefone:</strong> <?php echo htmlspecialchars($servico['telefone']); ?>
                </div>
            <?php endif; ?>
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

<script>
    async function sharePDF(btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando PDF...';
        btn.disabled = true;

        const element = document.querySelector('.page');
        const opt = {
            margin: 0,
            filename: 'OS_<?php echo $safe_id; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        try {
            // Gera o PDF como um objeto Blob (arquivo em memória)
            const pdfBlob = await html2pdf().set(opt).from(element).output('blob');
            const file = new File([pdfBlob], `OS_<?php echo $safe_id; ?>.pdf`, { type: 'application/pdf' });

            // Tenta usar o compartilhamento nativo do celular (Android/iOS)
            if (navigator.canShare && navigator.share) {
                await navigator.share({ files: [file], title: 'Ordem de Serviço', text: 'Segue a OS em anexo.' });
            } else {
                // Se estiver no PC, apenas baixa o arquivo
                const url = URL.createObjectURL(pdfBlob);
                const a = document.createElement('a'); a.href = url; a.download = `OS_<?php echo $safe_id; ?>.pdf`; a.click();
            }
        } catch (err) {
            alert('Erro ao gerar PDF: ' + err.message);
        } finally {
            btn.innerHTML = originalText; btn.disabled = false;
        }
    }
</script>
</body>
</html>