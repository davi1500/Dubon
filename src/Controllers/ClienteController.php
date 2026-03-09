<?php

class ClienteController
{
    public function index()
    {
        global $pdo;

        // Busca todos os clientes com contagem de serviços e total gasto
        $sql = "SELECT c.id, 
                c.nome,
                c.telefone,
                c.endereco,
                c.cpf,
                c.cnpj,
                c.razao_social,
                c.email,
                COUNT(s.id) as total_servicos, 
                SUM(s.valor_total) as total_gasto,
                MAX(s.data_servico) as ultima_visita
                FROM clientes c 
                LEFT JOIN servicos s ON c.id = s.cliente_id 
                GROUP BY c.id 
                ORDER BY c.nome ASC";
        $stmt = $pdo->query($sql);
        $clientes = $stmt->fetchAll();

        return view('clientes', ['clientes' => $clientes, 'BASE_URL' => BASE_URL]);
    }

    public function store()
    {
        global $pdo;

        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $razao_social = $_POST['razao_social'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $cnpj = $_POST['cnpj'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $email = $_POST['email'] ?? '';
        if (empty($nome)) {
            // Nome é obrigatório
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'O nome do cliente é obrigatório!'];
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }
        
        // Verifica se é uma edição (ID existe e é numérico)
        if (!empty($id) && is_numeric($id)) {
            // Atualizar Existente
            $stmt = $pdo->prepare("UPDATE clientes SET nome=?, razao_social=?, cpf=?, cnpj=?, telefone=?, endereco=?, email=? WHERE id=?");
            $stmt->execute([$nome, $razao_social, $cpf, $cnpj, $telefone, $endereco, $email, $id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cliente atualizado com sucesso!'];
        } else {
            // Criar Novo
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, razao_social, cpf, cnpj, telefone, endereco, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $razao_social, $cpf, $cnpj, $telefone, $endereco, $email]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cliente cadastrado com sucesso!'];
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }

    public function historico($id)
    {
        global $pdo;
        header('Content-Type: application/json');

        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE cliente_id = ? ORDER BY data_servico DESC");
        $stmt->execute([$id]);
        $servicos = $stmt->fetchAll();

        echo json_encode($servicos);
        exit;
    }

    public function delete($id)
    {
        global $pdo;
        // Apenas admin pode excluir
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. VERIFICAÇÃO DE SEGURANÇA:
            // Verifica se o cliente tem serviços associados. Se tiver, IMPEDE a exclusão para não perder histórico.
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE cliente_id = ?");
            $stmtCheck->execute([$id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Não é possível excluir: Este cliente possui Ordens de Serviço. Edite as OS e troque o cliente antes de excluir.'];
                header('Location: ' . BASE_URL . '/clientes');
                exit;
            }

            // 2. Se não tiver serviços, pode excluir tranquilamente
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cliente excluído com sucesso!'];
            } else {
                throw new Exception("O cliente com ID {$id} não foi encontrado no banco de dados.");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            // Loga o erro detalhado para o desenvolvedor (no log de erros do PHP)
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            // Define uma mensagem amigável para o usuário
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Não foi possível excluir o cliente. Verifique se ele não possui dependências ativas.'];
        }

        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }
}