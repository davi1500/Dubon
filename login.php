<?php
session_start();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    require_once 'conexao.php';
    $loginSucesso = false;

    // Consulta segura usando Prepared Statements (Previne SQL Injection)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuario]);
    $u = $stmt->fetch();

    if ($u && $u['senha'] === $senha) {
            $_SESSION['usuario_id'] = $u['id'];
            $_SESSION['usuario_nome'] = $u['nome'];
            $_SESSION['usuario_nivel'] = $u['nivel'];
            $loginSucesso = true;
    }

    if ($loginSucesso) {
        header('Location: index.php');
        exit;
    } else {
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dubom Refrigeração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #eef2f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-login { width: 100%; max-width: 400px; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="card card-login p-4 bg-white">
        <div class="text-center mb-4">
            <h1 style="font-size: 3rem;">❄️</h1>
            <h4 class="fw-bold text-primary">Dubom Refrigeração</h4>
            <p class="text-muted">Acesso ao Sistema</p>
        </div>
        <?php if($erro): ?>
            <div class="alert alert-danger py-2"><?php echo $erro; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuário</label>
                <input type="text" name="usuario" class="form-control form-control-lg" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" class="form-control form-control-lg" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Entrar</button>
        </form>
    </div>
</body>
</html>
