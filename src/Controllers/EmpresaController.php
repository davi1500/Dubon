<?php

class EmpresaController
{
    public function index()
    {
        global $pdo;
        // Apenas admin pode acessar
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $stmt = $pdo->query("SELECT * FROM configuracoes");
        $configuracoes_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Define valores padrão se não existirem
        $configuracoes = array_merge([
            'empresa_nome' => 'Dubom Refrigeração',
            'empresa_cnpj' => '00.000.000/0001-00',
            'empresa_telefone' => '(00) 90000-0000',
            'empresa_email' => 'contato@suaempresa.com',
            'empresa_slogan' => 'Slogan da sua empresa',
            'empresa_logo' => ''
        ], $configuracoes_raw);

        return view('empresa', ['config' => $configuracoes]);
    }

    public function store()
    {
        global $pdo;
        // Apenas admin pode acessar
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES (?, ?)");

            // Salva os campos de texto
            foreach ($_POST as $chave => $valor) {
                $stmt->execute([$chave, trim($valor)]);
            }

            // Lida com o upload do logo
            if (isset($_FILES['empresa_logo']) && $_FILES['empresa_logo']['error'] == 0) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Deleta o logo antigo se existir
                $oldLogo = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'empresa_logo'")->fetchColumn();
                if ($oldLogo && file_exists(__DIR__ . '/../../public' . $oldLogo)) {
                    unlink(__DIR__ . '/../../public' . $oldLogo);
                }

                $fileName = 'logo_' . time() . '_' . basename($_FILES['empresa_logo']['name']);
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['empresa_logo']['tmp_name'], $uploadFile)) {
                    $stmt->execute(['empresa_logo', '/uploads/' . $fileName]);
                }
            }

            header('Location: ' . BASE_URL . '/empresa');
            exit;
        }
    }
}