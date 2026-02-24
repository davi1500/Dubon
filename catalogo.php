<?php
// Habilita exibição de erros para identificar o problema (HTTP 500)
ini_set('display_errors', 1);
ini_set('log_errors', 1); // Garante que erros sejam logados
error_reporting(E_ALL); // Reporta todos os erros
    
session_start();
// Se não for admin, chuta de volta pro index
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header('Location: index.php');
    exit;
}
    
// Configuração dos arquivos
require_once 'conexao.php';
$arquivo_catalogo = 'catalogo.json';
$arquivo_categorias = 'categorias.json';
    
// API para SALVAR o catálogo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = file_get_contents('php://input');
    if (json_decode($dados) !== null) {
        $targetFile = $arquivo_catalogo;
        if (isset($_GET['acao']) && $_GET['acao'] === 'salvar_categorias') {
            $targetFile = $arquivo_categorias;
        }
        file_put_contents($targetFile, $dados);
        echo json_encode(['status' => 'sucesso']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'msg' => 'Dados inválidos']);
    }
    exit;
}
    
// API para LER dados (Catálogo e Histórico de Serviços para o Dashboard)
if (isset($_GET['acao'])) {
    switch ($_GET['acao']) {
        case 'ler_catalogo':
            if (!file_exists($arquivo_catalogo)) {
                $defaults = [
                    ["nome" => "Carga de Fluido Refrigerante (Gás)", "categoria" => "ar_condicionado", "custo" => 80, "valor" => 350],
                    ["nome" => "Carga de Gás Balcão Refrigerado", "categoria" => "balcao", "custo" => 70, "valor" => 400],
                    ["nome" => "Carga de Gás Câmara Fria", "categoria" => "camara_fria", "custo" => 200, "valor" => 800],
                    ["nome" => "Carga de Gás Geladeira + Filtro", "categoria" => "refrigeracao", "custo" => 60, "valor" => 380],
                    ["nome" => "Conserto Máquina de Lavar", "categoria" => "maquina_lavar", "custo" => 50, "valor" => 600],
                    ["nome" => "Desinstalação de Ar Condicionado", "categoria" => "ar_condicionado", "custo" => 0, "valor" => 150],
                    ["nome" => "Higienização Máquina de Lavar", "categoria" => "maquina_lavar", "custo" => 20, "valor" => 280],
                    ["nome" => "Infraestrutura (Tubulação por metro)", "categoria" => "ar_condicionado", "custo" => 40, "valor" => 120],
                    ["nome" => "Infraestrutura AR", "categoria" => "ar_condicionado", "custo" => 100, "valor" => 600],
                    ["nome" => "Instalação Ar", "categoria" => "ar_condicionado", "custo" => 50, "valor" => 600],
                    ["nome" => "Instalação Split 18000/24000 BTUs", "categoria" => "ar_condicionado", "custo" => 80, "valor" => 700],
                    ["nome" => "Instalação Split 30000 BTUs ou Piso Teto", "categoria" => "ar_condicionado", "custo" => 120, "valor" => 950],
                    ["nome" => "Instalação Split 9000/12000 BTUs", "categoria" => "ar_condicionado", "custo" => 50, "valor" => 500],
                    ["nome" => "Limpeza Completa Ar", "categoria" => "ar_condicionado", "custo" => 20, "valor" => 400],
                    ["nome" => "Limpeza de Condensador Expositor", "categoria" => "balcao", "custo" => 30, "valor" => 300],
                    ["nome" => "Limpeza de Condensadora (Câmara)", "categoria" => "camara_fria", "custo" => 30, "valor" => 350],
                    ["nome" => "Limpeza de Dreno", "categoria" => "ar_condicionado", "custo" => 0, "valor" => 100],
                    ["nome" => "Limpeza de Dreno (Desentupimento)", "categoria" => "ar_condicionado", "custo" => 0, "valor" => 150],
                    ["nome" => "Limpeza de Sistema Geladeira", "categoria" => "refrigeracao", "custo" => 50, "valor" => 600],
                    ["nome" => "Limpeza e Instalação", "categoria" => "ar_condicionado", "custo" => 70, "valor" => 800],
                    ["nome" => "Limpeza Interna Ar", "categoria" => "ar_condicionado", "custo" => 10, "valor" => 200],
                    ["nome" => "Limpeza Química Completa (Split)", "categoria" => "ar_condicionado", "custo" => 20, "valor" => 250],
                    ["nome" => "Manutenção Preventiva Câmara Fria", "categoria" => "camara_fria", "custo" => 50, "valor" => 450],
                    ["nome" => "Material", "categoria" => "outros", "custo" => 0, "valor" => 0],
                    ["nome" => "Outros", "categoria" => "outros", "custo" => 0, "valor" => 0],
                    ["nome" => "Reparo de Vazamento", "categoria" => "ar_condicionado", "custo" => 50, "valor" => 700],
                    ["nome" => "Troca de Agitador", "categoria" => "maquina_lavar", "custo" => 60, "valor" => 220],
                    ["nome" => "Troca de Borracha (Gaxeta)", "categoria" => "refrigeracao", "custo" => 80, "valor" => 250],
                    ["nome" => "Troca de Borracha de Vedação (Câmara)", "categoria" => "camara_fria", "custo" => 150, "valor" => 400],
                    ["nome" => "Troca de Capacitor de Partida", "categoria" => "ar_condicionado", "custo" => 35, "valor" => 180],
                    ["nome" => "Troca de Capacitor Lavadora", "categoria" => "maquina_lavar", "custo" => 30, "valor" => 150],
                    ["nome" => "Troca de Compressor Ar", "categoria" => "ar_condicionado", "custo" => 400, "valor" => 800],
                    ["nome" => "Troca de Compressor Geladeira", "categoria" => "refrigeracao", "custo" => 400, "valor" => 950],
                    ["nome" => "Troca de Contatora", "categoria" => "ar_condicionado", "custo" => 80, "valor" => 300],
                    ["nome" => "Troca de Controlador Digital", "categoria" => "camara_fria", "custo" => 180, "valor" => 550],
                    ["nome" => "Troca de Eletrobomba de Drenagem", "categoria" => "maquina_lavar", "custo" => 45, "valor" => 220],
                    ["nome" => "Troca de Mecanismo (Câmbio)", "categoria" => "maquina_lavar", "custo" => 250, "valor" => 650],
                    ["nome" => "Troca de Micromotor/Ventilador", "categoria" => "camara_fria", "custo" => 120, "valor" => 480],
                    ["nome" => "Troca de Placa (Interface/Potência)", "categoria" => "maquina_lavar", "custo" => 180, "valor" => 480],
                    ["nome" => "Troca de Placa Eletrônica Universal", "categoria" => "ar_condicionado", "custo" => 150, "valor" => 450],
                    ["nome" => "Troca de Placa Máquina de Lavar", "categoria" => "maquina_lavar", "custo" => 150, "valor" => 300],
                    ["nome" => "Troca de Termostato/Sensor", "categoria" => "refrigeracao", "custo" => 40, "valor" => 220],
                    ["nome" => "Troca de Tirantes (Suspensão)", "categoria" => "maquina_lavar", "custo" => 40, "valor" => 180],
                    ["nome" => "Troca de Válvula de Entrada de Água", "categoria" => "maquina_lavar", "custo" => 50, "valor" => 200],
                    ["nome" => "Troca de Válvula Máquina", "categoria" => "maquina_lavar", "custo" => 30, "valor" => 90],
                    ["nome" => "Troca de Ventilador Geladeira", "categoria" => "refrigeracao", "custo" => 90, "valor" => 320],
                    ["nome" => "Visita Técnica / Orçamento", "categoria" => "outros", "custo" => 0, "valor" => 80]
                ];
                $json_data = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($arquivo_catalogo, $json_data);
                echo $json_data;
            } else {
                echo file_get_contents($arquivo_catalogo);
            }
            break;

        case 'ler_historico':
            // Agora lê do SQLite para gerar as estatísticas
            $stmt = $pdo->query("SELECT s.id, s.valor_total as valor, s.data_servico, i.descricao, i.quantidade, i.valor as valor_item, i.categoria 
                                 FROM servicos s 
                                 LEFT JOIN servicos_itens i ON s.id = i.servico_id");
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formata para estrutura que o JS espera (lista de serviços com itens)
            $historico = [];
            foreach ($dados as $row) {
                $id = $row['id'];
                if (!isset($historico[$id])) {
                    $historico[$id] = ['id' => $id, 'valor' => $row['valor'], 'itens' => []];
                }
                if ($row['descricao']) {
                    $historico[$id]['itens'][] = ['descricao' => $row['descricao'], 'quantidade' => $row['quantidade'], 'valor' => $row['valor_item']];
                }
            }
            echo json_encode(array_values($historico));
            break;

        case 'ler_categorias':
            if (!file_exists($arquivo_categorias)) {
                $defaults = [
                  [ "id" => "ar_condicionado", "nome" => "Ar Cond.", "icone_emoji" => "💨", "icone_bootstrap" => "bi-fan" ],
                  [ "id" => "refrigeracao", "nome" => "Geladeira", "icone_emoji" => "❄️", "icone_bootstrap" => "bi-snow2" ],
                  [ "id" => "camara_fria", "nome" => "Câm. Fria", "icone_emoji" => "🏭", "icone_bootstrap" => "bi-building" ],
                  [ "id" => "balcao", "nome" => "Balcão", "icone_emoji" => "🏪", "icone_bootstrap" => "bi-shop" ],
                  [ "id" => "maquina_lavar", "nome" => "Lavadora", "icone_emoji" => "💧", "icone_bootstrap" => "bi-droplet-fill" ],
                  [ "id" => "outros", "nome" => "Geral", "icone_emoji" => "🛠️", "icone_bootstrap" => "bi-tools" ]
                ];
                $json_data = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($arquivo_categorias, $json_data);
                echo $json_data;
            } else {
                echo file_get_contents($arquivo_categorias);
            }
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Catálogo e Lucros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.5rem; }
        .table-custom { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
    </style>
</head>
<body>

<?php require_once '_partials/menu.php'; ?>

<div class="main-content">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0"><i class="bi bi-graph-up-arrow"></i> Dashboard & Catálogo</h2>
        <p class="text-muted mb-0">Analise seus lucros e gerencie seus preços</p>
    </div>

    <!-- Dashboard de Performance -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card card-stat p-3 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Serviço Mais Lucrativo</h6>
                        <h5 class="fw-bold mb-0 text-truncate" style="max-width: 150px;" id="topLucroNome">...</h5>
                        <small class="text-success fw-bold" id="topLucroValor">Lucro: R$ 0,00</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat p-3 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-repeat"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Mais Realizado</h6>
                        <h5 class="fw-bold mb-0" id="topFreqNome">...</h5>
                        <small class="text-primary" id="topFreqCount">0 vezes</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat p-3 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Lucro Líquido Estimado</h6>
                        <h5 class="fw-bold mb-0" id="totalLucro">R$ 0,00</h5>
                        <small class="text-muted" id="totalFaturamento">Fat: R$ 0,00</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna da Esquerda: Gerenciar Catálogo -->
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold"><i class="bi bi-list-check"></i> Catálogo de Serviços e Preços</h5>
                </div>
                <div class="card-body px-4">
                    <!-- Formulário de Adição -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <input type="text" id="novoNome" class="form-control form-control-lg bg-light border-0" placeholder="Nome do Serviço (ex: Limpeza Split)">
                        </div>
                        <div class="col-md-3">
                            <select id="novaCategoria" class="form-select form-select-lg bg-light border-0">
                                <option value="outros">🛠️ Geral</option>
                                <option value="ar_condicionado">💨 Ar Cond.</option>
                                <option value="refrigeracao">❄️ Geladeira</option>
                                <option value="camara_fria">🏭 Câm. Fria</option>
                                <option value="balcao">🏪 Balcão</option>
                                <option value="maquina_lavar">💧 Lavadora</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" id="novoCusto" class="form-control form-control-lg bg-light border-0" placeholder="Custo (R$)" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <input type="number" id="novoValor" class="form-control form-control-lg bg-light border-0" placeholder="Venda (R$)" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <button onclick="adicionarItem()" class="btn btn-primary btn-lg w-100 rounded-3"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>

                    <!-- Lista -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">Tipo</th>
                                    <th>Serviço</th>
                                    <th>Custo</th>
                                    <th>Venda</th>
                                    <th>Lucro</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaCatalogo">
                                <!-- JS preenche aqui -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita: Ranking Detalhado -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold"><i class="bi bi-bar-chart-line"></i> Ranking de Serviços</h5>
                </div>
                <div class="card-body px-4">
                    <ul class="list-group list-group-flush" id="listaRanking">
                        <!-- JS preenche aqui -->
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Gerenciador de Categorias -->
    <div class="col-lg-12 mt-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold"><i class="bi bi-tags-fill"></i> Gerenciar Categorias e Ícones</h5>
                <p class="text-muted small">Adicione ou edite as categorias de serviços e seus respectivos ícones (use classes do <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>).</p>
            </div>
            <div class="card-body px-4">
                <!-- Formulário para adicionar nova categoria -->
                <div class="row g-2 mb-4 p-3 bg-light rounded-3">
                    <div class="col-md-4"><input type="text" id="novaCatNome" class="form-control" placeholder="Nome da Categoria (ex: Portão Eletrônico)"></div>
                    <div class="col-md-4"><input type="text" id="novaCatIcone" class="form-control" placeholder="Classe do Ícone (ex: bi-gate-fill)"></div>
                    <div class="col-md-2"><input type="text" id="novaCatEmoji" class="form-control" placeholder="Emoji (ex:  GATE)"></div>
                    <div class="col-md-2"><button onclick="adicionarCategoria()" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> Adicionar</button></div>
                </div>
                <!-- Tabela de categorias existentes -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ícone</th>
                                <th>Nome</th>
                                <th>ID (Sistema)</th>
                                <th>Classe do Ícone (Bootstrap)</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaCategorias">
                            <!-- JS preenche aqui -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição de Item -->
    <div class="modal fade" id="modalEditarItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Editar Item do Catálogo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editIndex">
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço</label>
                        <input type="text" id="editNome" class="form-control form-control-lg bg-light">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria (Ícone)</label>
                        <select id="editCategoria" class="form-select form-select-lg bg-light"></select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Custo</label><input type="number" id="editCusto" class="form-control" step="0.01"></div>
                        <div class="col-6"><label class="form-label">Venda</label><input type="number" id="editValor" class="form-control" step="0.01"></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-3 px-4" onclick="salvarEdicaoItem()">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (Necessário para o Modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let catalogo = [];
    let historico = [];
    let categorias = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await carregarDados();
        renderizarTudo();
        calcularEstatisticas();
    });

    async function carregarDados() {
        try {
            const [resCat, resHist, resCategorias] = await Promise.all([
                fetch('?acao=ler_catalogo&t=' + Date.now()),
                fetch('?acao=ler_historico&t=' + Date.now()),
                fetch('?acao=ler_categorias&t=' + Date.now())
            ]);
            catalogo = await resCat.json();
            historico = await resHist.json();
            categorias = await resCategorias.json();
        } catch (e) {
            console.error("Erro ao carregar dados", e);
        }
    }

    function renderizarTudo() {
        renderizarCatalogo();
        renderizarCategorias();
        popularDropdownCategorias();
    }

    // --- Lógica do Catálogo ---

    async function salvarCatalogo() {
        await fetch('', {
            method: 'POST',
            body: JSON.stringify(catalogo)
        });
        renderizarTudo();
        calcularEstatisticas();
    }

    function adicionarItem() {
        const nome = document.getElementById('novoNome').value.trim();
        const categoria = document.getElementById('novaCategoria').value;
        const custo = document.getElementById('novoCusto').value;
        const valor = document.getElementById('novoValor').value;
        
        if (!nome) return alert('Digite o nome do serviço');

        catalogo.push({ nome, categoria, custo: custo || 0, valor: valor || 0 });
        catalogo.sort((a, b) => a.nome.localeCompare(b.nome)); // Mantém ordem alfabética
        
        document.getElementById('novoNome').value = '';
        document.getElementById('novoCusto').value = '';
        document.getElementById('novoValor').value = '';
        document.getElementById('novoNome').focus();
        
        salvarCatalogo();
    }

    function removerItem(index) {
        if (confirm('Remover este item do catálogo?')) {
            catalogo.splice(index, 1);
            salvarCatalogo();
        }
    }

    function editarItem(index) {
        const item = catalogo[index];
        
        // Preenche o modal com os dados atuais
        document.getElementById('editIndex').value = index;
        document.getElementById('editNome').value = item.nome;
        document.getElementById('editCategoria').value = item.categoria || 'outros';
        document.getElementById('editCusto').value = item.custo || 0;
        document.getElementById('editValor').value = item.valor || 0;

        // Abre o modal
        const modal = new bootstrap.Modal(document.getElementById('modalEditarItem'));
        modal.show();
    }

    function salvarEdicaoItem() {
        const index = document.getElementById('editIndex').value;
        catalogo[index].nome = document.getElementById('editNome').value;
        catalogo[index].categoria = document.getElementById('editCategoria').value;
        catalogo[index].custo = document.getElementById('editCusto').value;
        catalogo[index].valor = document.getElementById('editValor').value;
        
        salvarCatalogo();
        
        // Fecha o modal
        const modalEl = document.getElementById('modalEditarItem');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    }

    function renderizarCatalogo() {
        const tbody = document.getElementById('tabelaCatalogo');
        tbody.innerHTML = '';

        if (catalogo.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Nenhum serviço cadastrado.</td></tr>';
            return;
        }

        catalogo.forEach((item, index) => {
            const custo = parseFloat(item.custo || 0);
            const valor = parseFloat(item.valor || 0);
            const lucro = valor - custo;
            // Garante que mostre o nome mesmo se estiver salvo como 'descricao' (compatibilidade)
            const nomeExibicao = item.nome || item.descricao || 'Sem Nome';

            const categoriaDoItem = categorias.find(c => c.id === item.categoria) || categorias.find(c => c.id === 'outros');
            const iconeEmoji = categoriaDoItem ? categoriaDoItem.icone_emoji : '?';
            
            // Se o item estiver vazio (sem nome e sem valores), não exibe na tabela para não poluir
            if (nomeExibicao === 'Sem Nome' && valor === 0 && custo === 0) return;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center fs-5">${iconeEmoji}</td>
                <td class="fw-bold text-secondary">${nomeExibicao}</td>
                <td class="text-danger">R$ ${custo.toFixed(2)}</td>
                <td class="text-dark">R$ ${valor.toFixed(2)}</td>
                <td class="text-success fw-bold">R$ ${lucro.toFixed(2)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editarItem(${index})"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removerItem(${index})"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- Lógica das Categorias ---

    function popularDropdownCategorias() {
        const selects = [document.getElementById('novaCategoria'), document.getElementById('editCategoria')];
        
        selects.forEach(select => {
            if (!select) return;
            select.innerHTML = '';
            categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.innerHTML = `${cat.icone_emoji} ${cat.nome}`;
                select.appendChild(option);
            });
        });
    }

    function renderizarCategorias() {
        const tbody = document.getElementById('tabelaCategorias');
        tbody.innerHTML = '';
        categorias.forEach((cat, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fs-4"><i class="bi ${cat.icone_bootstrap}"></i></td>
                <td class="fw-bold">${cat.nome}</td>
                <td><span class="badge bg-secondary">${cat.id}</span></td>
                <td><code>${cat.icone_bootstrap}</code></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editarCategoria(${index})"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removerCategoria(${index})"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function salvarCategorias() {
        await fetch('?acao=salvar_categorias', {
            method: 'POST',
            body: JSON.stringify(categorias)
        });
        renderizarTudo();
    }

    function adicionarCategoria() {
        const nome = document.getElementById('novaCatNome').value.trim();
        const icone = document.getElementById('novaCatIcone').value.trim();
        const emoji = document.getElementById('novaCatEmoji').value.trim();
        if (!nome || !icone) return alert('Preencha o nome e o ícone da categoria.');

        const id = nome.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_{2,}/g, '_');
        if (categorias.some(c => c.id === id)) return alert('Uma categoria com este nome (ou um ID similar) já existe.');

        categorias.push({ id, nome, icone_bootstrap: icone, icone_emoji: emoji || '✨' });
        salvarCategorias();
        document.getElementById('novaCatNome').value = '';
        document.getElementById('novaCatIcone').value = '';
        document.getElementById('novaCatEmoji').value = '';
    }

    function editarCategoria(index) {
        const cat = categorias[index];
        const novoIcone = prompt(`Editar classe do ícone para "${cat.nome}":`, cat.icone_bootstrap);
        if (novoIcone && novoIcone.trim() !== '') {
            categorias[index].icone_bootstrap = novoIcone.trim();
            salvarCategorias();
        }
    }

    function removerCategoria(index) {
        const cat = categorias[index];
        if (cat.id === 'outros') return alert('A categoria "outros" não pode ser removida.');
        if (confirm(`Remover a categoria "${cat.nome}"? Isso não pode ser desfeito.`)) {
            categorias.splice(index, 1);
            salvarCategorias();
        }
    }

    // --- Lógica de Estatísticas (Dashboard) ---

    function calcularEstatisticas() {
        const stats = {};
        let totalGeral = 0;
        let totalLucroGeral = 0;

        // Processa o histórico para agrupar por nome de serviço
        historico.forEach(servico => {
            // Verifica se é o formato novo (array de itens) ou antigo (string)
            let itensParaProcessar = [];
            
            if (servico.itens && Array.isArray(servico.itens)) {
                itensParaProcessar = servico.itens;
            } else {
                // Legado
                itensParaProcessar = [{ descricao: servico.descricao, valor: servico.valor }];
            }

            itensParaProcessar.forEach(item => {
                if (!item.descricao) return;

                const nome = item.descricao.trim();
                const valorUnit = parseFloat(item.valor || 0);
                const qtd = parseInt(item.quantidade || 1);
                const valorTotalItem = valorUnit * qtd;
                
                // Busca custo no catálogo para estimar lucro
                // Proteção contra itens sem nome no catálogo para não travar o script
                const itemCatalogo = catalogo.find(c => (c.nome && c.nome.toLowerCase() === nome.toLowerCase()) || (c.descricao && c.descricao.toLowerCase() === nome.toLowerCase()));
                const custo = itemCatalogo ? parseFloat(itemCatalogo.custo || 0) : 0;
                const lucroTotalItem = (valorUnit - custo) * qtd;
                
                if (!stats[nome]) {
                    stats[nome] = { count: 0, total: 0, lucro: 0 };
                }
                
                stats[nome].count += qtd;
                stats[nome].total += valorTotalItem;
                stats[nome].lucro += lucroTotalItem;
                
                totalGeral += valorTotalItem;
                totalLucroGeral += lucroTotalItem;
            });
        });

        // Converte objeto em array para ordenar
        const ranking = Object.keys(stats).map(nome => ({
            nome,
            count: stats[nome].count,
            total: stats[nome].total,
            lucro: stats[nome].lucro
        }));

        // 1. Top Lucro
        ranking.sort((a, b) => b.lucro - a.lucro);
        const topLucro = ranking[0] || { nome: '-', total: 0 };
        
        // 2. Top Frequência
        const rankingFreq = [...ranking].sort((a, b) => b.count - a.count);
        const topFreq = rankingFreq[0] || { nome: '-', count: 0 };

        // Atualiza Dashboard
        document.getElementById('topLucroNome').innerText = topLucro.nome;
        document.getElementById('topLucroValor').innerText = 'Lucro: R$ ' + (topLucro.lucro || 0).toFixed(2);
        
        document.getElementById('topFreqNome').innerText = topFreq.nome;
        document.getElementById('topFreqCount').innerText = topFreq.count + ' vezes';
        
        document.getElementById('totalLucro').innerText = 'R$ ' + totalLucroGeral.toFixed(2);
        document.getElementById('totalFaturamento').innerText = 'Fat: R$ ' + totalGeral.toFixed(2);

        // Renderiza Lista de Ranking (Top 10)
        const listaRanking = document.getElementById('listaRanking');
        listaRanking.innerHTML = '';
        
        ranking.slice(0, 10).forEach((item, idx) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center py-3';
            li.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-light text-dark me-3 rounded-pill border">${idx + 1}º</span>
                    <div>
                        <div class="fw-bold">${item.nome}</div>
                        <small class="text-muted">${item.count} serviços realizados</small>
                    </div>
                </div>
                <span class="fw-bold text-success">Lucro: R$ ${(item.lucro || 0).toFixed(2)}</span>
            `;
            listaRanking.appendChild(li);
        });
    }
</script>

</body>
</html>