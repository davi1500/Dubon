<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Página não encontrada</title>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <p class="fs-3"> <span class="text-danger">Opps!</span> Página não encontrada.</p>
        <p class="lead">A página que você está procurando não existe.</p>
        <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary">Voltar para o Início</a>
    </div>
</body>
</html>