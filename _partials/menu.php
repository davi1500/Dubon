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
    /* Mobile Styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            z-index: 1070; /* Fica acima de tudo quando aberto */
        }
        .sidebar.show {
            transform: translateX(0);
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
        }
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding-top: 5rem !important; /* Espaço para o botão do menu */
        }
        .menu-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1060; display: none;
        }
        .menu-overlay.show { display: block; }
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

<!-- Botão Mobile e Overlay -->
<button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3 shadow" style="z-index: 1080;" onclick="toggleMenu()">
    <i class="bi bi-list fs-4"></i>
</button>
<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

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
            <a class="nav-link <?php echo str_starts_with($uri, '/clientes') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/clientes">
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
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/produtos') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produtos">
                <i class="bi bi-box-seam"></i> Produtos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/fornecedores') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/fornecedores">
                <i class="bi bi-truck"></i> Fornecedores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/usuarios') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/usuarios">
                <i class="bi bi-person-badge-fill"></i> Equipe
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo str_starts_with($uri, '/empresa') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/empresa">
                <i class="bi bi-building-gear"></i> Minha Empresa
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

<script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('menuOverlay').classList.toggle('show');
    }
</script>
