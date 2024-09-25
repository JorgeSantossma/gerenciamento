<?php
include_once('config.php'); 
include('protect.php');

// Consulta para listar os produtos
$sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
$result_produto = $conexao->query($sql_produto_query);

// Carregar pedidos
$sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
$result_cliente = $conexao->query($sql_cliente_query);

// Carregar lista de pedidos e produtos vinculados junto com as datas da tabela datas_produto
$sql_lista_pedidos = "
    SELECT c.idcliente, 
           c.pedido, 
           c.cliente AS cliente, 
           c.endereco, 
           c.data_entrega AS data_entrega, 
           cp.id_lotes AS id_lote,
           GROUP_CONCAT(
    CONCAT_WS(
        '||', 
        COALESCE(p.equipamento, 'N/A'), 
        COALESCE(p.lote, 'N/A'), 
        COALESCE(p.conjunto, 'N/A'), 
        COALESCE(dp.data_engenharia, 'N/A'), 
        COALESCE(dp.datas_programacao, 'N/A'), 
        COALESCE(dp.datas_pcp, 'N/A'),
        COALESCE(dp.datas_producao, 'N/A')
    ) SEPARATOR '%%'
) AS produtos
    FROM cliente AS c
    LEFT JOIN cliente_produto AS cp ON c.idcliente = cp.id_cliente
    LEFT JOIN produto AS p ON cp.id_produto = p.idproduto
    LEFT JOIN datas_produto AS dp ON cp.id_lotes = dp.cliente_produto_id 
    GROUP BY c.idcliente
    ORDER BY c.idcliente DESC";

$result_lista_pedidos = $conexao->query($sql_lista_pedidos);

// Verifica se a consulta foi bem-sucedida
if (!$result_lista_pedidos) {
    echo "Erro ao carregar lista de pedidos: " . $conexao->error;
    exit;
}

if (!function_exists('validarData')) {
    function validarData($data) {
        $data_formatada = date('Y-m-d', strtotime($data));
        return ($data_formatada == '1970-01-01') ? false : $data_formatada;
    }
}

$data_inserida = $_GET['data_inserida'] ?? '';

$conexao->close();
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
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        color: black;
        max-width: 1000px;
    }

    .modal-content table {
        width: 100%;
        /* Garante que a tabela ocupe toda a largura disponível */
        max-height: 600px;
        /* Define uma altura máxima para o conteúdo dentro do modal */
        overflow-y: auto;
        /* Adiciona uma barra de rolagem se o conteúdo for maior que a altura */
    }

    .close {
        color: black;
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
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
    }

    .linha-engenharia {
    background-color: #f34545; /* cor para engenharia */
    }

    .linha-programacao {
    background-color: #4287f5; /* cor para programação */
    }

    .linha-pcp {
    background-color: #f7d038; /* cor para PCP */
    }

    .linha-producao {
    background-color: #3da543; /* cor para produção */
    }

    .pedido-detalhe {
    position: relative; /* Garante que os elementos internos "absolutos" sejam posicionados dentro deste bloco */
    padding: 15px;
    border: 1px solid #ddd;
    margin-bottom: 20px;
    border-radius: 5px;
    background-color: #f9f9f9;
}

    .gerar-relatorio-btn {
    background-color: #009879;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    border-radius: 5px;
    text-transform: uppercase;
    text-decoration: none;
}

    .gerar-relatorio-btn:hover {
        background-color: #007f63;
    }
    
    </style>
</head>

<body>
    <header>
        <a href="pedido.php" id="logo"> <img src="./img/logoSMA.png" width="50%"></a>
        <button id="openMenu">&#9776;</button>
        <nav id="menu">
            <button id="closeMenu">X</button>
            <a href="pedido.php">PEDIDOS</a>
            <a href="trilhadeira.php">TRILHADEIRA</a>
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
            <form action="obra.php" method="GET">
                <div class="search-section">
                    <div class="searchForm">
                        <label for="busca_unica"></label>
                        <input type="text" id="busca_unica" name="busca_unica" placeholder="Digite sua busca" oninput="filtrarItens()">
                        <button type="submit" name="submit_busca">Buscar</button>
                    </div>
                </div>

                <div class="search-section">
                    <div class="searchForm">
                        <label for="data_busca"></label>
                        <input type="date" name="busca_data" id="data_busca">
                        <button type="submit" name="submit_data">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </aside>
    <main>
        <div id="listaPedidos">
        <?php 

if ($result_lista_pedidos->num_rows > 0) {
    while ($row = $result_lista_pedidos->fetch_assoc()) {
        $mostrar_pedido = true;

        // Verifica se a busca única corresponde a algum campo
        if (!empty($busca_unica)) {
            if (
                stripos($row['pedido'], $busca_unica) === false &&
                stripos($row['cliente'], $busca_unica) === false &&
                stripos($row['endereco'], $busca_unica) === false
            ) {
                $mostrar_pedido = false;
            }
        }

        if (!empty($busca_data) && $row['data_entrega'] != $busca_data) {
            $mostrar_pedido = false;
        }

        if ($mostrar_pedido) {
            echo "<div class='pedido-detalhe'>
                <p><strong>Pedido: </strong>" . htmlspecialchars($row['pedido']) . 
                " <strong>Nome do Cliente: </strong>" . htmlspecialchars($row['cliente']) . 
                " <strong>Localidade: </strong>" . htmlspecialchars($row['endereco']) . "</p>";                
            $data_entrega = htmlspecialchars($row['data_entrega']);
            if ($data_entrega !== '0000-00-00') {
                $data_formatada = (new DateTime($data_entrega))->format('d/m/Y');
                echo "<p><strong>Data de Entrega: </strong>" . $data_formatada . "</p>";
                echo "<form method='POST' action='gerar_relatorio.php' target='_blank' style='position: absolute; top: 10px; right: 10px;'>
                <input type='hidden' name='pedido' value='" . htmlspecialchars($row['pedido']) . "'>
                <button type='submit' class='gerar-relatorio-btn'>Gerar Relatório</button>
              </form>";
            }

            if (!empty($row['produtos'])) {
                echo "<h3>Produtos Vinculados:</h3>";
                echo "<table id='tabela-produtos'>
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

                // Divida a string de produtos
                $produtos = explode('%%', $row['produtos']);
                foreach ($produtos as $produto) {
                    $dados = explode('||', $produto);
                    $equipamento = htmlspecialchars($dados[0] ?? 'N/A');
                    $lote = htmlspecialchars($dados[1] ?? 'N/A');
                    $conjunto = htmlspecialchars($dados[2] ?? 'N/A');
                
                    // Formatação das datas
                    $data_engenharia = htmlspecialchars(($dados[3] && $dados[3] != '0000-00-00') ? $dados[3] : 'N/A');
                    $data_programacao = htmlspecialchars(($dados[4] && $dados[4] != '0000-00-00') ? $dados[4] : 'N/A');
                    $data_pcp = htmlspecialchars(($dados[5] && $dados[5] != '0000-00-00') ? $dados[5] : 'N/A');
                    $data_producao = htmlspecialchars(($dados[6] && $dados[6] != '0000-00-00') ? $dados[6] : 'N/A');
                
                    // Define o unique_id
                    $unique_id = $equipamento . "_" . $lote;
                
                    
                    
                    $classe_linha = ''; // Variável para armazenar a classe CSS da linha

                    // Lógica de prioridade das classes de acordo com as datas mais recentes
                    if ($data_producao !== 'N/A' && validarData($data_producao)) {
                        $classe_linha = 'linha-producao';
                    } elseif ($data_pcp !== 'N/A' && validarData($data_pcp)) {
                        $classe_linha = 'linha-pcp';
                    } elseif ($data_programacao !== 'N/A' && validarData($data_programacao)) {
                        $classe_linha = 'linha-programacao';
                    } elseif ($data_engenharia !== 'N/A' && validarData($data_engenharia)) {
                        $classe_linha = 'linha-engenharia';
                    }
                    
                    // Exibindo a linha da tabela com a classe CSS correta
                    echo "<tr class='$classe_linha'>
                            <td>$equipamento</td>
                            <td>$lote</td>
                            <td>$conjunto</td>                                    
                            <td>$data_engenharia</td>
                            <td>$data_programacao</td>
                            <td>$data_pcp</td>
                            <td>$data_producao</td>
                            <td>
                                <form method='POST' action='salvar_datas.php'>
                                    <input type='hidden' name='pedido_id' value='" . htmlspecialchars($row['id_lote']) . "' />
                                    <button type='submit' name='edit' value='$unique_id'>Editar Datas</button>
                                </form>
                            </td>
                        </tr>";
                }
                
                echo "</table></div>"; // Fechar a tabela e o div de detalhes
            }
        }
    }
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
                                echo "<option value=\"" . htmlspecialchars($pedido['idcliente']) . "\">" . htmlspecialchars($pedido['pedido']) . "</option>";
                            }
                            ?>
                        </select>

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
                                    echo "<option value=\"" . htmlspecialchars($produto['idproduto']) 
                                    . "\" data-equipamento=\"" . htmlspecialchars($produto['equipamento']) 
                                    . "\" data-lote=\"" . htmlspecialchars($produto['lote']) 
                                    . "\" data-conjunto=\"" . htmlspecialchars($produto['conjunto'])
                                    . "\">". htmlspecialchars($produto['equipamento']) . " - " 
                                    . htmlspecialchars($produto['lote']) . "</option>";
                                }
                                ?>
                            </select>
                            <button type="button" onclick="adicionarProduto()">Adicionar Produto</button>
                        </div>

                        <button type="button" onclick="cancelar()">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function filtrarItens() {
    // Obtém o valor do campo de busca
    const input = document.getElementById('busca_unica');
    const filtro = input.value.toLowerCase(); // Converte para minúsculas para facilitar a comparação
    const tabela = document.getElementById('tabela-produtos');
    const linhas = tabela.getElementsByTagName('tr');

    // Percorre todas as linhas da tabela
    for (let i = 1; i < linhas.length; i++) { // Começa em 1 para ignorar o cabeçalho
        const colunaEquipamento = linhas[i].getElementsByTagName('td')[0];
        const colunaLote = linhas[i].getElementsByTagName('td')[1];
        const colunaConjunto = linhas[i].getElementsByTagName('td')[2];

        // Verifica se a linha deve ser exibida
        if (colunaEquipamento || colunaLote || colunaConjunto) {
            const textoEquipamento = colunaEquipamento.textContent || colunaEquipamento.innerText;
            const textoLote = colunaLote.textContent || colunaLote.innerText;
            const textoConjunto = colunaConjunto.textContent || colunaConjunto.innerText;

            // Se o valor de busca estiver presente em qualquer coluna, mostra a linha
            if (textoEquipamento.toLowerCase().indexOf(filtro) > -1 || 
                textoLote.toLowerCase().indexOf(filtro) > -1 || 
                textoConjunto.toLowerCase().indexOf(filtro) > -1) {
                linhas[i].style.display = '';
            } else {
                linhas[i].style.display = 'none'; // Esconde a linha
            }
        }
    }
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

        const inputsData = document.querySelectorAll('.input-data');

        // Função para mudar a cor da linha
        function mudarCorLinha(event) {
        // Obter o input que foi alterado
        const inputAlterado = event.target;
        
        // Encontrar a linha do produto correspondente
        const linhaProduto = inputAlterado.closest('tr');

        // Remover qualquer classe de linha colorida
        linhaProduto.classList.remove('linha-engenharia', 'linha-programacao', 'linha-pcp', 'linha-producao');

        // Adicionar a classe correta dependendo do ID do input
        if (inputAlterado.id.includes('engenharia')) {
            linhaProduto.classList.add('linha-engenharia');
        } else if (inputAlterado.id.includes('programacao')) {
            linhaProduto.classList.add('linha-programacao');
        } else if (inputAlterado.id.includes('pcp')) {
            linhaProduto.classList.add('linha-pcp');
        } else if (inputAlterado.id.includes('producao')) {
            linhaProduto.classList.add('linha-producao');
        }
        }

        // Adicionando o evento 'change' a todos os inputs de data
        inputsData.forEach(input => input.addEventListener('change', mudarCorLinha));

        function excluirVinculo(id_lote) {
            if (confirm('Tem certeza que deseja excluir este vínculo?')) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'excluir_produto.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (this.status === 200) {
                        alert('Vínculo excluído com sucesso!');
                        location.reload(); // Recarrega a página para atualizar a lista
                    } else {
                        alert('Erro ao excluir o vínculo.');
                    }
                };

                xhr.send('id_lote=' + encodeURIComponent(id_lote));
            }
        }

    </script>
</body>

</html>
