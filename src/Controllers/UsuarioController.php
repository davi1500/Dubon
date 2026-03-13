<?php

class UsuarioController
{
    public function index()
    {
        global $pdo;
        // Apenas admin pode acessar
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC")->fetchAll();
        return view('usuarios', ['usuarios' => $usuarios]);
    }

    public function store()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $usuario = $_POST['usuario'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $nivel = $_POST['nivel'] ?? 'funcionario';

            if (empty($nome) || empty($usuario)) {
                die('Nome e Usuário são obrigatórios.');
            }

            if ($id) {
                // Atualizar
                if (!empty($senha)) {
                    // Se digitou senha nova, criptografa antes de salvar
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, senha=?, nivel=? WHERE id=?");
                    $stmt->execute([$nome, $usuario, $hash, $nivel, $id]);
                } else {
                    // Mantém senha antiga
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, nivel=? WHERE id=?");
                    $stmt->execute([$nome, $usuario, $nivel, $id]);
                }
            } else {
                // Criar Novo
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $usuario, $hash, $nivel]);
            }
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }
    }

    public function delete($id)
    {
        global $pdo;
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        header('Location: ' . BASE_URL . '/usuarios');
        exit;
    }
}