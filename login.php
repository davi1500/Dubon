<?php
session_start();
// Carrega as configurações (BASE_URL) logo no início para funcionar no logout
require_once __DIR__ . '/conexao.php';
$erro = '';

// Lógica de Logout
if (isset($_GET['acao']) && $_GET['acao'] === 'sair') {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Consulta segura usando Prepared Statements (Previne SQL Injection)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([':usuario' => $usuario]);
    $u = $stmt->fetch();

    $login_ok = false;

    if ($u) {
        // 1. Tenta validar como Criptografia (Padrão novo)
        if (password_verify($senha, $u['senha'])) {
            $login_ok = true;
        } 
        // 2. Se falhar, tenta validar como Texto Puro (Padrão antigo) e atualiza
        elseif ($u['senha'] === $senha) {
            $login_ok = true;
            // Auto-migração: Criptografa a senha antiga agora mesmo
            $newHash = password_hash($senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$newHash, $u['id']]);
        }
    }

    if ($login_ok) {
            $_SESSION['usuario_id'] = $u['id'];
            $_SESSION['usuario_nome'] = $u['nome'];
            $_SESSION['usuario_nivel'] = $u['nivel'];
            
            header('Location: ' . BASE_URL . '/');
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
    <?php
        // Favicon Dinâmico
        $favicon = "data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>❄️</text></svg>";
        if (isset($pdo)) {
            $stmtFav = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'empresa_logo'");
            $logoPath = $stmtFav->fetchColumn();
            if ($logoPath && file_exists(__DIR__ . '/public' . $logoPath)) {
                $favicon = BASE_URL . $logoPath;
            }
        }
    ?>
    <link rel="icon" href="<?php echo $favicon; ?>">
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
