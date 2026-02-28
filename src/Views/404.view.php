<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #eef2f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
    </style>
</head>
<body>
    <div class="text-center">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <p class="fs-3"> <span class="text-danger">Opa!</span> Página não encontrada.</p>
        <p class="lead">A rota que você tentou acessar ainda não foi criada.</p>
        <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary mt-3">Voltar para a Home</a>
    </div>
</body>
</html>