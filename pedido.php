<?php
include_once('config.php');
include('protect.php');

if (isset($_POST['submit'])) {
    // Coleta dos dados para inserção
    $pedido = $_POST['pedido'] ?? null;
    $cliente = $_POST['client'] ?? null;
    $endereco = $_POST['local'] ?? null;
    $data_entrega = $_POST['data_entrega'] ?? null;
    $produtos = $_POST['produtos'] ?? [];

    if ($pedido && $cliente && $endereco && $data_entrega) {
        $sql_cliente = "INSERT INTO cliente (pedido, cliente, endereco, data_entrega) VALUES (?, ?, ?, ?)";
        $stmt_cliente = $conexao->prepare($sql_cliente);

        if (!$stmt_cliente) {
            die("Erro na preparação da consulta cliente: " . $conexao->error);
        }

        $stmt_cliente->bind_param("ssss", $pedido, $cliente, $endereco, $data_entrega);

        if ($stmt_cliente->execute()) {
            // Redireciona para index.php após a inserção
            header("Location: pedido.php?status=success");
            exit(); // Importante sair após o redirecionamento para evitar execução posterior
        } else {
            echo "Erro ao inserir dados do cliente: " . $stmt_cliente->error . "<br>";
        }

        $stmt_cliente->close();
    }

    
}

// Busca por pedido específico
if (isset($_GET['pedido_busca']) && !empty($_GET['pedido_busca'])) {
    $pedido_busca = $_GET['pedido_busca'];
    $sql_cliente_query = "SELECT * FROM cliente WHERE pedido = ?";
    $stmt_cliente = $conexao->prepare($sql_cliente_query);
    $stmt_cliente->bind_param("s", $pedido_busca);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    $stmt_cliente->close();
} else {
    // Se não houver busca, exibe todos os registros
    $sql_cliente_query = "SELECT * FROM cliente ORDER BY idcliente DESC";
    $result_cliente = $conexao->query($sql_cliente_query);
}

// Consulta para exibir produtos
$sql_produto_query = "SELECT * FROM produto ORDER BY idproduto DESC";
$result_produto = $conexao->query($sql_produto_query);

if (isset($_POST['submit_edit'])) {
    $pedido_id = $_POST['pedido_id'] ?? null;
    $cliente = $_POST['edit_cliente'] ?? null;
    $local = $_POST['edit_local'] ?? null;
    $data_entrega = $_POST['edit_data_entrega'] ?? null;

    if ($pedido_id && $cliente && $local && $data_entrega) {
        $sql_update = "UPDATE cliente SET cliente = ?, endereco = ?, data_entrega = ? WHERE pedido = ?";
        $stmt_update = $conexao->prepare($sql_update);

        if (!$stmt_update) {
            die("Erro na preparação da consulta: " . $conexao->error);
        }

        $stmt_update->bind_param("ssss", $cliente, $local, $data_entrega, $pedido_id);

        if ($stmt_update->execute()) {
            header("Location: pedido.php?status=updated");
            exit();
        } else {
            echo "Erro ao atualizar dados do cliente: " . $stmt_update->error . "<br>";
        }

        $stmt_update->close();
    }
}


$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCP</title>
    <link rel="stylesheet" type="text/css" href="./css/index.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css">
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
        background-color: #888;
        color: black;
    }

    .modal {
    display: none; /* Oculta o modal por padrão */
    position: fixed;
    z-index: 1; /* Fica sobre o conteúdo */
    left: 0;
    top: 0;
    width: 100%; /* Largura total */
    height: 100%; /* Altura total */
    overflow: auto; /* Permite rolagem se necessário */
    background-color: rgba(0, 0, 0, 0.4); /* Cor de fundo com transparência */
    padding-top: 60px; /* Espaçamento superior */
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto; /* Centro do modal */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Largura do modal */
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

    </style>
</head>

<body>

    <header>
        <a href="pedido.php" id="logo"> <img src="./img/logoSMA.png" width="50%">
        </a>

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
    <main>
        <div class="box-1">

            <form action="pedido.php" method="POST">
                <fieldset>
                    <div class="inputBox-1">
                        <input type="text" name="pedido" id="pedido" class="inputPedi" required>
                        <label for="pedido" class="labelInput">PEDIDO</label>
                    </div>
                    <br><br>
                    <div class="inputBox-1">
                        <input type="text" name="client" id="client" class="inputPedi" required>
                        <label for="client" class="labelInput">NOME DO CLIENTE</label>
                    </div>
                    <br><br>
                    <div class="inputBox-1">
                        <input type="text" name="local" id="local" class="inputPedi" required>
                        <label for="local" class="labelInput">LOCALIDADE</label>
                    </div>
                    <br><br>
                    <div>
                        <label for="data_entrega"><b> DATA DE ENTREGA:</b></label>
                        <input type="date" name="data_entrega" id="data_entrega" required>
                    </div>
                    <br><br>
                    <div>
                        </select>
                    </div>
                    <br><br>
                    <button type="submit" name="submit" id="submit">Salvar</button>
                </fieldset>
            </form>
            <form action="pedido.php" method="GET">
                <fieldset>
                    <div class="inputBox-1">
                        <input type="text" name="pedido_busca" id="pedido_busca" class="inputPedi" required>
                        <label for="pedido_busca" class="labelInput">BUSCAR POR PEDIDO</label>
                    </div>
                    <br><br>
                    <button type="submit" name="submit_busca" id="submit_busca">Buscar</button>
                </fieldset>
            </form>
        </div>
    </main>
    <footer>
        <div class="m-6">
            <table class="table text-white table-bg">
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Localidade</th>
                        <th scope="col">Data</th>
                        <th scope="col">...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
if ($result_cliente && $result_cliente->num_rows > 0) {
    while ($user_data = $result_cliente->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user_data['pedido']) . "</td>";
        echo "<td>" . htmlspecialchars($user_data['cliente']) . "</td>";
        echo "<td>" . htmlspecialchars($user_data['endereco']) . "</td>";
        $dataOriginal = $user_data['data_entrega'];
        $data = new DateTime($dataOriginal);
        $dataFormatada = $data->format('d/m/Y');
        echo "<td>" . htmlspecialchars($dataFormatada) . "</td>";
        echo "<td>
            <a class='btn btn-sm btn-primary btn-edit' data-id='" . htmlspecialchars($user_data['pedido']) . "' 
            data-cliente='" . htmlspecialchars($user_data['cliente']) . "' 
            data-local='" . htmlspecialchars($user_data['endereco']) . "' 
            data-data='" . htmlspecialchars($user_data['data_entrega']) . "'>
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square' viewBox='0 0 16 16'>
                <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
                <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
            </svg>
            </a>
        </td>";

        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>Nenhum cliente encontrado para este pedido.</td></tr>";
}
?>

                </tbody>
            </table>
        </div>
    </footer>
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Pedido</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="pedido_id" id="pedido_id">
                <div class="inputBox-1">
                    <label for="edit_cliente">Cliente:</label>
                    <input type="text" name="edit_cliente" id="edit_cliente" required>
                </div>
                <div class="inputBox-1">
                    <label for="edit_local">Localidade:</label>
                    <input type="text" name="edit_local" id="edit_local" required>
                </div>
                <div>
                    <label for="edit_data_entrega">Data de Entrega:</label>
                    <input type="date" name="edit_data_entrega" id="edit_data_entrega" required>
                </div>
                <button type="submit" name="submit_edit">Salvar</button>
            </form>
        </div>
    </div>

    <script type="text/javascript" src="./script/script.js"></script>
    <script src="./script/lote.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById("editModal");
    const span = document.getElementsByClassName("close")[0];

    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const pedidoId = this.getAttribute('data-id');
            // Aqui você deve fazer uma chamada AJAX para buscar os dados do pedido
            // Simulando os dados de edição para o modal
            // O AJAX deve buscar os dados reais do pedido no banco de dados
            document.getElementById('pedido_id').value = pedidoId;
            document.getElementById('edit_cliente').value = this.getAttribute('data-cliente'); // Assumindo que você passe o nome do cliente
            document.getElementById('edit_local').value = this.getAttribute('data-local'); // Assumindo que você passe a localidade
            document.getElementById('edit_data_entrega').value = this.getAttribute('data-data'); // Assumindo que você passe a data de entrega

            modal.style.display = "block"; // Abre o modal
        });
    });

    span.onclick = function() {
        modal.style.display = "none"; // Fecha o modal
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none"; // Fecha o modal se clicar fora dele
        }
    }
});
</script>
</body>

</html>