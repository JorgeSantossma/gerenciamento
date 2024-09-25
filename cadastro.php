<?php
include_once('config.php');
include('protect.php');

// Se houver um pedido de busca por código
if (isset($_POST['submit_codigo_busca']) && !empty($_POST['codigo_busca'])) {
    $codigo_busca = '%' . $_POST['codigo_busca'] . '%'; // Adiciona os caracteres % para buscar por qualquer parte do código
    $sql_produto_query = "SELECT * FROM produto WHERE equipamento LIKE ? OR conjunto LIKE ?";
    $stmt_cliente = $conexao->prepare($sql_produto_query);
    $stmt_cliente->bind_param("ss", $codigo_busca, $codigo_busca);
    $stmt_cliente->execute();
    $result_produto = $stmt_cliente->get_result();
    $stmt_cliente->close();
} elseif (isset($_POST['submit_lote_busca']) && !empty($_POST['lote_busca'])) {
    $lote_busca = $_POST['lote_busca'];
    $sql_produto_query = "SELECT * FROM produto WHERE lote = ?";
    $stmt_cliente = $conexao->prepare($sql_produto_query);
    $stmt_cliente->bind_param("s", $lote_busca);
    $stmt_cliente->execute();
    $result_produto = $stmt_cliente->get_result();
    $stmt_cliente->close();
} else {
    // Se não houver busca, exibe todos os registros
    $sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
    $result_produto = $conexao->query($sql_produto_query);
}

if (isset($_POST['submit'])) {
    // Pegue os dados do formulário
    $equipamento = $_POST['equipamento'];
    $lote = $_POST['lote'];
    $conjunto = $_POST['conj'];
    
    // Consulta SQL de inserção
    $sql_insert = "INSERT INTO produto (equipamento, lote, conjunto) VALUES (?, ?, ?)";
    
    // Prepare a consulta
    if ($stmt = $conexao->prepare($sql_insert)) {
        // Bind dos parâmetros
        $stmt->bind_param("sss", $equipamento, $lote, $conjunto);
        
        // Executa a consulta
        if ($stmt->execute()) {
            // Redireciona para a mesma página sem dados POST
            header("Location: cadastro.php?status=success");
            exit(); // Importante sair após o redirecionamento para evitar execução posterior
        } else {
            echo "Erro ao executar a consulta: " . $stmt->error; // Mostra erro ao executar
        }
        $stmt->close();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
    }
}

if (isset($_POST['submit_edit'])) {
    $idProdutoEdit = $_POST['idProdutoEdit'];
    $equipamentoEdit = $_POST['equipamentoEdit'];
    $loteEdit = $_POST['loteEdit'];
    $conjEdit = $_POST['conjEdit'];

    $sql_edit = "UPDATE produto SET equipamento = ?, lote = ?, conjunto = ? WHERE idproduto = ?";
    if ($stmt = $conexao->prepare($sql_edit)) {
        $stmt->bind_param("sssi", $equipamentoEdit, $loteEdit, $conjEdit, $idProdutoEdit);
        if ($stmt->execute()) {
            header("Location: cadastro.php?status=success");
            exit();
        } else {
            echo "Erro ao atualizar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
    }
}

// Consulta para exibir produtos
$sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
$result_cliente = $conexao->query($sql_cliente_query);

// Outros códigos PHP para processar dados

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./css/cadas.css">
    <title>Cadastro</title>
    <style>
    .table thead th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
        padding: 5px 15px;
        text-align: center;
        text-transform: uppercase;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2f2;
        color: black;
    }

    svg {
        cursor: pointer;
    }

    /* Estilos do Modal */
#modalEdit {
    display: none; /* Oculta o modal por padrão */
    position: fixed; /* Fica fixo na tela */
    z-index: 1000; /* Coloca acima de outros elementos */
    left: 0;
    top: 0;
    width: 100%; /* Largura total da tela */
    height: 100%; /* Altura total da tela */
    overflow: auto; /* Adiciona rolagem se necessário */
    background-color: rgba(0, 0, 0, 0.5); 
    color: black;
}

/* Estilos para a caixa do modal */
.modal-content {
    background-color: darkolivegreen; /* Fundo branco */
    margin: 15% auto; /* 15% do topo e centralizado horizontalmente */
    padding: 20px; /* Espaçamento interno */
    border: 1px solid #888; /* Borda */
    width: 80%; /* Largura do modal */
    max-width: 500px; /* Largura máxima */
    border-radius: 8px; /* Bordas arredondadas */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    color: black; /* Sombra */
}

.modal-header, .modal-footer {
    display: flex; /* Flexbox para alinhar itens */
    justify-content: space-between; /* Espaço entre os itens */
    align-items: center;
    color: black; /* Alinhamento vertical */
}

.modal-header h2 {
    margin: 0; /* Remove margem do título */
}

.close {
    cursor: pointer; /* Muda o cursor para indicar que é clicável */
    color: #aaa; 
    font-size: 24px; 
    transition: color 0.3s; /
}

.close:hover,
.close:focus {
    color: black; /* Muda a cor ao passar o mouse */
    text-decoration: none; 
    cursor: pointer;
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
        <div class="box">
            <form action="cadastro.php" method="POST">
                <fieldset>
                    <legend><b>REGISTRO DE PEDIDOS</b></legend>
                    <br>
                    <div class="inputBox">
                        <input type="text" name="equipamento" id="equipamento" class="inputEquip"
                            onfocus="esconderLabel(this)" onblur="mostrarLabel(this)">
                        <label for="equipamento" class="labelInput">EQUIPAMENTO</label>
                    </div>
                    <br> <br>
                    <div class="inputBox">
                        <input type="text" name="lote" id="lote" class="inputEquip" onfocus="esconderLabel(this)"
                            onblur="mostrarLabel(this)">
                        <label for="lote" class="labelInput">LOTE</label>
                    </div>
                    <br> <br>
                    <div class="inputBox">
                        <input type="text" name="conj" id="conj" class="inputEquip" onfocus="esconderLabel(this)"
                            onblur="mostrarLabel(this)">
                        <label for="conj" class="labelInput">CONJUNTO</label>
                    </div>
                    <br>
                    <button type="submit" name="submit" id="submit">Salvar</button>
                </fieldset>
                <br><br>
            </form>
            <form action="cadastro.php" method="POST">
                <div class="search-section">
                    <div class="inputBox"><br>
                        <label for="codigo_busca" class="labelInput">BUSCAR POR CÓDIGO</label>
                        <input type="text" name="codigo_busca" id="codigo_busca" class="inputEquip">
                    </div>
                    <br>
                    <button type="submit" name="submit_codigo_busca" id="submit">Buscar</button>
                </div>
                <div class="search-section">
                    <div class="inputBox"><br>
                        <label for="lote_busca" class="labelInput">BUSCAR POR LOTE</label>
                        <input type="text" name="lote_busca" id="lote_busca" class="inputEquip">
                    </div>
                    <br>
                    <button type="submit" name="submit_lote_busca" id="submit">Buscar Lote</button>
                </div>
            </form>
        </div>
    </aside>
    <main>
        <div class="m-5">
            <table class="table text-white table-bg">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">EQUIPAMENTO</th>
                        <th scope="col">LOTE</th>
                        <th scope="col">CONJUNTO</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_produto) {
                        while ($user_data = mysqli_fetch_assoc($result_produto)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($user_data['idproduto']) . "</td>";
                            echo "<td>" . htmlspecialchars($user_data['equipamento']) . "</td>";
                            echo "<td>" . htmlspecialchars($user_data['lote']) . "</td>";
                            echo "<td>" . htmlspecialchars($user_data['conjunto']) . "</td>";
                            echo "<td>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square' viewBox='0 0 16 16' onclick='editarProduto(" . htmlspecialchars($user_data['idproduto']) . ")'>
                                <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
                                <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
                            </svg>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>Nenhum resultado encontrado.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

              <!-- Formulário de Edição -->
<div id="modalEdit">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Produto</h2>
            <span class="close" onclick="fecharModal()">&times;</span>
        </div>
        <form action="cadastro.php" method="POST" onsubmit="return validarFormulario()">
            <input type="hidden" name="idProdutoEdit" id="idProdutoEdit">
            <div class="inputBox1">
                <input type="text" name="equipamentoEdit" id="equipamentoEdit" class="inputEquip">
                <label for="equipamentoEdit" class="labelInput-modal">EQUIPAMENTO</label>
            </div>
            <div class="inputBox1">
                <input type="text" name="loteEdit" id="loteEdit" class="inputEquip">
                <label for="loteEdit" class="labelInput-modal">LOTE</label>
            </div>
            <div class="inputBox1">
                <input type="text" name="conjEdit" id="conjEdit" class="inputEquip">
                <label for="conjEdit" class="labelInput-modal">CONJUNTO</label>
            </div>
            <div class="modal-footer">
                <button type="submit" name="submit_edit">Salvar Edição</button>
            </div>
        </form>
    </div>
</div>


    </main>
    <script>
    function esconderLabel(input) {
        const label = input.nextElementSibling; // Seleciona o próximo elemento, que é o label
        label.style.display = 'none'; // Esconde o label
    }

    function mostrarLabel(input) {
        const label = input.nextElementSibling; // Seleciona o próximo elemento, que é o label
        if (input.value.trim() === '') { // Verifica se o campo está vazio
            label.style.display = 'block'; // Mostra o label
        }
    }

// Função para editar o produto
function editarProduto(idProduto) {
    // Faz uma requisição AJAX para obter os dados do produto
    const xhr = new XMLHttpRequest();
    xhr.open("GET", `getProduto.php?id=${idProduto}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const produto = JSON.parse(xhr.responseText);
            document.getElementById('idProdutoEdit').value = produto.idproduto;
            document.getElementById('equipamentoEdit').value = produto.equipamento;
            document.getElementById('loteEdit').value = produto.lote;
            document.getElementById('conjEdit').value = produto.conjunto;

            // Mostra o formulário de edição
            document.getElementById('modalEdit').style.display = 'block';
        } else {
            alert("Erro ao carregar os dados do produto.");
        }
    };
    xhr.send();
}

// Função para esconder o formulário de edição
function fecharModal() {
    document.getElementById('modalEdit').style.display = 'none';
}

// Para fechar o modal ao clicar fora dele
window.onclick = function(event) {
    const modal = document.getElementById('modalEdit');
    if (event.target === modal) {
        fecharModal();
    }
}

    </script>

</body>

</html>