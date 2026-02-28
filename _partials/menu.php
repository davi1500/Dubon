<?php
// Garante que a sessão foi iniciada em algum lugar antes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nivel_acesso = $_SESSION['usuario_nivel'] ?? '';
$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Pega a URI atual para o menu 'active'
$uri = $_SERVER['REQUEST_URI'];
?>
<style>
    /* Estilos para o menu lateral e conteúdo principal */
    :root {
        --sidebar-width: 260px;
        --sidebar-bg: #fff;
        --sidebar-border: #e9ecef;
    }
    body {
        background-color: #f8f9fa;
    }
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: var(--sidebar-width);
        background-color: var(--sidebar-bg);
        border-right: 1px solid var(--sidebar-border);
        padding: 15px;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
        z-index: 1030;
    }
    .main-content {
        margin-left: var(--sidebar-width);
        transition: margin-left 0.3s ease;
        padding: 2rem;
        width: calc(100% - var(--sidebar-width));
    }
    .sidebar-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--sidebar-border);
    }
    .sidebar-header h3 {
        margin: 0;
        font-size: 1.5rem;
        color: #0d6efd;
        font-weight: 700;
    }
    .sidebar .nav-link {
        color: #495057;
        padding: 12px 15px;
        display: block;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: background-color 0.2s, color 0.2s;
        font-weight: 500;
    }
    .sidebar .nav-link:hover {
        background-color: #f1f3f5;
        color: #212529;
        text-decoration: none;
    }
    .sidebar .nav-link.active {
        background-color: #0d6efd;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }
    .sidebar .nav-link i {
        margin-right: 15px;
        width: 20px;
        text-align: center;
    }
    .sidebar-footer {
        margin-top: auto;
        text-align: center;
        padding-top: 15px;
        border-top: 1px solid var(--sidebar-border);
    }
    .sidebar-footer .user-info {
        margin-bottom: 10px;
        color: #6c757d;
    }
    .sidebar-footer a {
        color: #dc3545;
        font-weight: bold;
    }
</style>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-snow2"></i> Dubom</h3>
    </div>

    <ul class="nav flex-column flex-grow-1">
        <li class="nav-item">
            <a class="nav-link <?php echo ($uri == '/' || str_starts_with($uri, '/servicos')) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/">
                <i class="bi bi-file-earmark-text"></i> Ordens de Serviço
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/clientes.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/clientes.php">
                <i class="bi bi-people-fill"></i> Clientes
            </a>
        </li>
        
        <?php if ($nivel_acesso === 'admin'): ?>
        <li class="nav-item mt-3"><h6 class="text-muted ps-3" style="font-size: 0.8rem;">ADMINISTRAÇÃO</h6></li>
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/catalogo') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/catalogo">
                <i class="bi bi-tags-fill"></i> Meus Serviços
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="user-info">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars($nome_usuario); ?></span>
        </div>
        <a href="<?php echo BASE_URL; ?>/login.php?acao=sair">Sair <i class="bi bi-box-arrow-right"></i></a>
    </div>
</nav>
