<?php
include_once('config.php'); 
include('protect.php');

// Consulta para listar os produtos
$sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
$result_produto = $conexao->query($sql_produto_query);

// Carregar pedidos
$sql_cliente_query = "SELECT * FROM cliente ORDER BY id DESC";
$result_cliente = $conexao->query($sql_cliente_query);

// Carregar lista de pedidos e produtos vinculados junto com as datas da tabela datas_produto
$sql_lista_pedidos = "
    SELECT c.id, 
           c.pedido, 
           c.cliente AS cliente, 
           c.localidade, 
           c.data1 AS data_entrega, 
           GROUP_CONCAT(
    CONCAT_WS(
        '||', 
        COALESCE(p.equipamento, 'N/A'), 
        COALESCE(p.lote, 'N/A'), 
        COALESCE(p.conjunto, 'N/A'), 
        COALESCE(p.data2, 'N/A'), 
        COALESCE(dp.data_programacao, 'N/A'), 
        COALESCE(dp.data_pcp, 'N/A'),
        COALESCE(dp.data_producao, 'N/A')
    ) SEPARATOR '%%'
) AS produtos
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.id = cp.cliente_id
    LEFT JOIN produto AS p ON cp.produto_id = p.idproduto
    LEFT JOIN datas_produto AS dp ON cp.id_lote = dp.cliente_produto_id 
    GROUP BY c.id
    ORDER BY c.id DESC";

$result_lista_pedidos = $conexao->query($sql_lista_pedidos);

// Verifica se a consulta foi bem-sucedida
if (!$result_lista_pedidos) {
    echo "Erro ao carregar lista de pedidos: " . $conexao->error;
    exit;
}

$conexao->close();

// Função para validar a data
function validarData($data) {
    $data_formatada = date('Y-m-d', strtotime($data));
    return ($data_formatada == '1970-01-01') ? false : $data_formatada;
}

// Recebendo e validando os dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['equipamento'], $_POST['lote'], $_POST['conjunto'], $_POST['data_programacao'], $_POST['data_pcp'], $_POST['data_producao'])) {
        $equipamento = $_POST['equipamento'];
        $lote = $_POST['lote'];
        $conjunto = $_POST['conjunto'];
        $data_programacao = validarData($_POST['data_programacao']);
        $data_pcp = validarData($_POST['data_pcp']);
        $data_producao = validarData($_POST['data_producao']);

        if ($data_programacao && $data_pcp && $data_producao) {
            // Sanitize input
            $equipamento = $conexao->real_escape_string($equipamento);
            $lote = $conexao->real_escape_string($lote);
            $conjunto = $conexao->real_escape_string($conjunto);
            $data_programacao = $conexao->real_escape_string($data_programacao);
            $data_pcp = $conexao->real_escape_string($data_pcp);
            $data_producao = $conexao->real_escape_string($data_producao);

            // Verificar se já existe um registro na tabela datas_produto para este cliente_produto
            $sql_check = "SELECT dp.id FROM datas_produto AS dp
                          INNER JOIN cliente_produto AS cp ON dp.cliente_produto_id = cp.id
                          WHERE cp.equipamento = '$equipamento' AND cp.lote = '$lote' AND cp.conjunto = '$conjunto'";
            $result_check = $conexao->query($sql_check);

            if ($result_check->num_rows > 0) {
                // Atualizar as datas na tabela datas_produto
                $sql = "UPDATE datas_produto 
                        SET data_programacao = '$data_programacao', 
                            data_pcp = '$data_pcp', 
                            data_producao = '$data_producao'
                        WHERE cliente_produto_id IN (SELECT cp.id FROM cliente_produto AS cp WHERE cp.equipamento = '$equipamento' AND cp.lote = '$lote' AND cp.conjunto = '$conjunto')";
            } else {
                // Inserir um novo registro na tabela datas_produto
                $sql = "INSERT INTO datas_produto (cliente_produto_id, data_programacao, data_pcp, data_producao)
                        VALUES (
                            (SELECT cp.id FROM cliente_produto AS cp WHERE cp.equipamento = '$equipamento' AND cp.lote = '$lote' AND cp.conjunto = '$conjunto'), 
                            '$data_programacao', '$data_pcp', '$data_producao'
                        )";
            }

            if ($conexao->query($sql) === TRUE) {
                echo "Datas atualizadas com sucesso!";
            } else {
                echo "Erro: " . $conexao->error;
            }
        } else {
            echo "Uma ou mais datas inseridas são inválidas.";
        }
    }
}
?>
<?php 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'config.php'; // Inclua suas credenciais e configurações de banco de dados

    // Coleta dos dados para inserção
    $cliente_produto_id = $_POST['cliente_produto_id'] ?? null;
    $data_programacao = $_POST['data_programacao'] ?? null;
    $data_pcp = $_POST['data_pcp'] ?? null;
    $data_producao = $_POST['data_producao'] ?? null;

    if ($cliente_produto_id) {
        // Inserir ou atualizar as datas na tabela datas_produto
        $sql_upsert = "INSERT INTO datas_produto (cliente_produto_id, data_programacao, data_pcp, data_producao)
                       VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE 
                       data_programacao = VALUES(data_programacao),
                       data_pcp = VALUES(data_pcp),
                       data_producao = VALUES(data_producao)";

        $stmt_upsert = $conexao->prepare($sql_upsert);

        if (!$stmt_upsert) {
            die("Erro na preparação da consulta de salvamento: " . $conexao->error);
        }

        $stmt_upsert->bind_param("isss", $cliente_produto_id, $data_programacao, $data_pcp, $data_producao);

        if ($stmt_upsert->execute()) {
            echo "Datas salvas com sucesso!<br>";
        } else {
            echo "Erro ao salvar as datas: " . $stmt_upsert->error . "<br>";
        }

        $stmt_upsert->close();
    } else {
        echo "ID do cliente_produto não fornecido.<br>";
    }

    $pedido_busca = isset($_GET['pedido_busca']) && !empty($_GET['pedido_busca']) ? $_GET['pedido_busca'] : null;
$lote_busca = isset($_GET['lote_busca']) && !empty($_GET['lote_busca']) ? $_GET['lote_busca'] : null;
$data_busca = isset($_GET['data_busca']) && !empty($_GET['data_busca']) ? $_GET['data_busca'] : null;

// Monta a query de busca de acordo com os filtros fornecidos
$sql_cliente_query = "SELECT * FROM cliente WHERE 1=1";
$params = [];
$types = '';

if ($pedido_busca) {
    $sql_cliente_query .= " AND pedido = ?";
    $params[] = $pedido_busca;
    $types .= 's';
}

if ($lote_busca) {
    $sql_cliente_query .= " AND lote = ?";
    $params[] = $lote_busca;
    $types .= 's';
}

if ($data_busca) {
    $sql_cliente_query .= " AND data = ?";
    $params[] = $data_busca;
    $types .= 's';
}

// Se não houver busca, exibe todos os registros
if (empty($pedido_busca) && empty($lote_busca) && empty($data_busca)) {
    $sql_cliente_query = "SELECT * FROM cliente ORDER BY id DESC";
    $result_cliente = $conexao->query($sql_cliente_query);
} else {
    // Prepara a consulta com os parâmetros de busca
    $stmt_cliente = $conexao->prepare($sql_cliente_query);
    if ($params) {
        $stmt_cliente->bind_param($types, ...$params);
    }
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    $stmt_cliente->close();
}

// Consulta para exibir produtos
$sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
$result_produto = $conexao->query($sql_produto_query);

$result_cliente = null;
$erro_mensagem = "";

// Verifica se foi passado algum parâmetro de busca
if (isset($_GET['pedido_busca']) && !empty($_GET['pedido_busca'])) {
    $pedido_busca = $_GET['pedido_busca'];

    // Realiza a busca pelo pedido
    $sql_cliente_query = "SELECT * FROM cliente WHERE pedido = ?";
    $stmt_cliente = $conexao->prepare($sql_cliente_query);
    $stmt_cliente->bind_param("s", $pedido_busca);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    
    // Exibe os resultados
    if ($result_cliente->num_rows == 0) {
        $erro_mensagem = "Nenhum resultado encontrado para o pedido '$pedido_busca'.";
    }
    $stmt_cliente->close();
} elseif (isset($_GET['erro']) && $_GET['erro'] == 'nenhum_resultado') {
    $erro_mensagem = "Nenhum pedido encontrado.";
} else {
    // Se não foi passada nenhuma busca, exibe todos os registros
    $sql_cliente_query = "SELECT * FROM cliente ORDER BY id DESC";
    $result_cliente = $conexao->query($sql_cliente_query);
}

    $conexao->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/cadas.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css">
    <link rel="stylesheet" type="text/css" href="./css/lista.css">
    <link rel="stylesheet" href="css/pedido.css">
    <link rel="stylesheet" href="css/modal.css">
    <title>OBRAS</title>
    <style>
    main {
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        flex: 20 0 500px;
        flex-wrap: wrap;
        overflow: auto;
        height: calc(100vh - 150px);
        margin: 3px;
    }

    .hidden {
        display: none;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        color: black;
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #listaPedidos {
        padding: 10px;
    }

    .pedido-detalhe {
        background-color: #f8f8f8;
        color: #333;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body>
    <header>
        <a href="index.php" id="logo"> <img src="./img/logoSMA.png" width="50%"></a>
        <button id="openMenu">&#9776;</button>
        <nav id="menu">
            <button id="closeMenu">X</button>
            <a href="index.php">PEDIDOS</a>
            <a href="grafico.php">GRAFICOS</a>
            <a href="cadastro.php">CADASTRO</a>
            <a href="obra.php">OBRAS</a>
            <a href="logout.php">SAIR</a>
        </nav>
    </header>

    <aside>
        <div class="classificação">
            <img src="./img/vermelho.png" width="30px">
            <p id="img"> AGUARDANDO PROGRAMAÇÃO</p>
            <img src="./img/azul.png" width="30px">
            <p id="img"> PROGRAMADO</p>
            <img src="./img/amarelo.png" width="30px">
            <p id="img"> EM PRODUÇÃO</p>
            <img src="./img/verde.png" width="30px">
            <p id="img">FINALIZADO</p>
            <a href="javascript:void(0)" id="openModalBtn" onclick="openModal()">
                <i class="bi bi-bookmark-plus"></i>
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor"
                    class="bi bi-bookmark-plus" viewBox="0 0 20 12">
                    <path
                        d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1z" />
                    <path
                        d="M8 4a.5.5 0 0 1 .5.5V6H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H6a.5.5 0 0 1 0-1h1.5V4.5A.5.5 0 0 1 8 4" />
                </svg>
            </a>
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-funnel"
                viewBox="0 0 20 12" onclick="openModalbusca()">
                <path
                    d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
            </svg>
        </div>
    </aside>

    <main>
        <div id="listaPedidos">
            <?php
if ($result_lista_pedidos->num_rows > 0) {
    while ($row = $result_lista_pedidos->fetch_assoc()) {
        // Exibindo informações do pedido
        echo "<div class='pedido-detalhe'>
                <p><strong>Pedido: </strong>" . htmlspecialchars($row['pedido']) . 
                " <strong>Nome do Cliente: </strong>" . htmlspecialchars($row['cliente']) . 
                " <strong>Localidade: </strong>" . htmlspecialchars($row['localidade']) . 
                " <strong>Data de Entrega: </strong>" . htmlspecialchars($row['data_entrega']) . "</p>";

        if (!empty($row['produtos'])) {
            echo "<h3>Produtos Vinculados:</h3>";
            echo "<table>
                    <tr>
                        <th>Equipamento</th>
                        <th>Lote</th>
                        <th>Conjunto</th>
                        <th>Data de Engenharia</th>
                        <th>Data de Programação</th>
                        <th>Data de PCP</th>
                        <th>Data de Produção</th>
                        <th>Ações</th>
                    </tr>";

            // Separando os produtos com base no delimitador '%%'
            $produtos = explode('%%', $row['produtos']);
            foreach ($produtos as $produto) {
                $dados = explode('||', $produto);

                // Atribuindo valores com verificações de existência
                $equipamento = isset($dados[0]) ? htmlspecialchars($dados[0]) : 'N/A';
                $lote = isset($dados[1]) ? htmlspecialchars($dados[1]) : 'N/A';
                $conjunto = isset($dados[2]) ? htmlspecialchars($dados[2]) : 'N/A';
                $data2 = isset($dados[3]) && !empty($dados[3]) && $dados[3] != '0000-00-00' ? htmlspecialchars($dados[3]) : 'N/A';
                $data_programacao = isset($dados[4]) && !empty($dados[4]) && $dados[4] != '0000-00-00' ? htmlspecialchars($dados[4]) : 'N/A';
                $data_pcp = isset($dados[5]) && !empty($dados[5]) && $dados[5] != '0000-00-00' ? htmlspecialchars($dados[5]) : 'N/A';
                $data_producao = isset($dados[6]) && !empty($dados[6]) && $dados[6] != '0000-00-00' ? htmlspecialchars($dados[6]) : 'N/A';

                // Definindo ID único para o botão de edição
                $unique_id = htmlspecialchars($equipamento) . "_" . htmlspecialchars($lote);

                // Exibindo os dados na tabela
                echo "<tr>
                    <td>$equipamento</td>
                    <td>$lote</td>
                    <td>$conjunto</td>
                    <td>$data2</td>
                    <td>$data_programacao</td>
                    <td>$data_pcp</td>
                    <td>$data_producao</td>
                    <td>
                        <form method='POST' action='salvar_datas.php'>
                            <input type='hidden' name='pedido_id' value='" . htmlspecialchars($row['id']) . "' />
                            <button type='submit' name='edit' value='$unique_id'>Editar Datas</button>
                        </form>
                    </td>
                </tr>";
            }

            echo "</table>";
        } else {
            echo "<p><em>Nenhum produto vinculado.</em></p>";
        }
        
        echo "</div>";
    }
} else {
    echo "<p>Nenhum pedido registrado ainda.</p>";
}
?>
            <div id="editarDatasModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="fecharEditarDatasModal()">&times;</span>
                    <h2>Editar Datas do Produto</h2>
                    <form id="editarDatasForm" action="salvar_datas.php" method="POST">
                        <button type="submit">Salvar</button>
                    </form>
                </div>
            </div>

            <div id="pedidoModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Adicionar Pedido e Vincular Produto</h2>
                    <form id="pedidoForm">
                        <label for="pedidoSelect">Pedido:</label>
                        <select name="pedido" id="pedidoSelect" onchange="vincularPedido()">
                            <option value="">Selecione um pedido</option>
                            <?php
                            while ($pedido = $result_cliente->fetch_assoc()) {
                                echo "<option value=\"" . htmlspecialchars($pedido['id']) . "\">" . htmlspecialchars($pedido['pedido']) . "</option>";
                            }
                            ?>
                        </select><br><br>

                        <div id="detalhesPedido">
                            <!-- Detalhes e produtos vinculados serão exibidos aqui -->
                        </div>

                        <div id="adicionarProdutoForm" style="display: none;">
                            <h3>Adicionar Produto</h3>
                            <input type="hidden" id="cliente_id" name="cliente_id" value="">
                            <label for="produtoSelect">Selecione um Produto:</label>
                            <select id="produtoSelect" name="produto_id">
                                <option value="">Selecione um produto</option>
                                <?php
                                while ($produto = $result_produto->fetch_assoc()) {
                                    echo "<option value=\"" . htmlspecialchars($produto['idproduto']) . "\" data-equipamento=\"" . htmlspecialchars($produto['equipamento']) . "\" data-lote=\"" . htmlspecialchars($produto['lote']) . "\" data-conjunto=\"" . htmlspecialchars($produto['conjunto']) . "\" data-data_engenharia=\"" . htmlspecialchars($produto['data2']) . "\">" . htmlspecialchars($produto['equipamento']) . " - " . htmlspecialchars($produto['lote']) . "</option>";
                                }
                                ?>
                            </select>
                            <button type="button" onclick="adicionarProduto()">Adicionar Produto</button>
                        </div>

                        <button type="button" onclick="cancelar()">Cancelar</button>
                    </form>
                </div>
            </div>
            <div id="buscaPedLote" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close" onclick="closeModalbusca()">&times;</span>
                    <h2>Localize a obra</h2>
                    <form id="searchForm" method="GET" action="busca.php">
                        <label for="pedido_busca">Buscar por Pedido:</label>
                        <input type="text" id="pedido_busca" name="pedido_busca" placeholder="Digite o pedido">

                        <label for="lote_busca">Buscar por Lote:</label>
                        <input type="text" id="lote_busca" name="lote_busca" placeholder="Digite o lote">

                        <label for="data_busca">Buscar por Data:</label>
                        <input type="date" id="data_busca" name="data_busca">

                        <button type="submit">Buscar</button>
                    </form>
                    <div id="searchResults">
                        <!-- Os resultados da busca serão exibidos aqui -->
                    </div>
                </div>
            </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function openModalbusca() {
        document.getElementById("buscaPedLote").style.display = "block";
    }

    // Função para fechar o modal
    function closeModalbusca() {
        document.getElementById("buscaPedLote").style.display = "none";
    }

    // Função para realizar a busca
    function searchPedidoLote(event) {
        event.preventDefault(); // Impede o envio padrão do formulário

        var query = document.getElementById("searchInput").value;

        // Lógica de busca, pode ser uma chamada AJAX ou qualquer outra implementação
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "buscarPedidoLote.php?query=" + query, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Exibe os resultados no div 'searchResults'
                document.getElementById("searchResults").innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }
    //
    //
    //
    //
    //
    function openModal() {
        document.getElementById('pedidoModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('pedidoModal').style.display = 'none';
    }

    function vincularPedido() {
        var pedidoId = document.getElementById('pedidoSelect').value;
        var detalhesDiv = document.getElementById('detalhesPedido');
        document.getElementById('cliente_id').value = pedidoId;
        detalhesDiv.innerHTML = '';

        if (pedidoId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'detalhes_pedido.php?id=' + pedidoId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    detalhesDiv.innerHTML = xhr.responseText;
                    document.getElementById('adicionarProdutoForm').style.display = 'block';
                } else {
                    detalhesDiv.innerHTML = 'Erro ao carregar detalhes.';
                }
            };
            xhr.send();
        } else {
            document.getElementById('adicionarProdutoForm').style.display = 'none';
        }
    }

    function adicionarProduto() {
        var produtoSelect = document.getElementById('produtoSelect');
        var produtoId = produtoSelect.value;
        var clienteId = document.getElementById('cliente_id').value;

        if (produtoId && clienteId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'adicionar_produto.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    vincularPedido();
                    var produtoTexto = produtoSelect.options[produtoSelect.selectedIndex].text;
                    var pedidoTexto = document.getElementById('pedidoSelect').options[document.getElementById(
                        'pedidoSelect').selectedIndex].text;
                    var listaPedidos = document.getElementById('listaPedidos');
                    var novoPedido = document.createElement('div');
                    novoPedido.classList.add('pedido-detalhe');
                    novoPedido.innerHTML = `
                            <h3>Pedido: ${pedidoTexto}</h3>
                            <p>Produto: ${produtoTexto}</p>
                            <p>Cliente ID: ${clienteId}</p>
                            <hr>
                        `;
                    listaPedidos.appendChild(novoPedido);
                    produtoSelect.value = '';
                } else {
                    alert('Erro ao adicionar produto.');
                }
            };
            xhr.send('cliente_id=' + encodeURIComponent(clienteId) + '&produto_id=' + encodeURIComponent(produtoId));
        } else {
            alert('Selecione um produto e um cliente.');
        }
    }

    function cancelar() {
        closeModal();
    }

    function editarDatas(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao, clienteId, produtoId) {
        // Preenche os campos do modal com os valores recebidos
        document.getElementById('cliente_id').value = clienteId;
        document.getElementById('produto_id').value = produtoId;
        document.getElementById('data_programacao').value = dataProgramacao;
        document.getElementById('data_pcp').value = dataPCP;
        document.getElementById('data_producao').value = dataProducao;

        // Exibe o modal de edição
        document.getElementById('editarDatasModal').style.display = 'block';
    }

    function fecharEditarDatasModal() {
        document.getElementById('editarDatasModal').style.display = 'none';
    }


    document.getElementById('editarDatasForm').addEventListener('submit', function(event) {
        event.preventDefault();
        salvarDatasEspecifico();
    });

    function salvarDatasEspecifico() {
        var formData = new FormData(document.getElementById('editarDatasForm'));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'salvar_datas.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Datas salvas com sucesso!');
                fecharEditarDatasModal();
                location.reload(); // Recarregar a página para refletir as alterações
            } else {
                alert('Erro ao salvar as datas. Código: ' + xhr.status);
            }
        };
        xhr.send(formData);
    }
    </script>
</body>

</html>