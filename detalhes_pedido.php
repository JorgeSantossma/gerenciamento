<?php
include_once('config.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $pedido_id = intval($_GET['id']);

    $sql = "SELECT p.equipamento, p.lote, p.conjunto, cp.id_lotes
            FROM cliente_produto AS cp
            JOIN produto AS p ON cp.id_produto = p.idproduto 
            WHERE cp.id_cliente = ?";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<style>
                .styled-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 25px 0;
                    font-size: 18px;
                    font-family: Arial, sans-serif;
                    min-width: 400px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
                }

                .styled-table thead tr {
                    background-color: #009879;
                    color: #ffffff;
                    text-align: left;
                }

                .styled-table th,
                .styled-table td {
                    padding: 12px 15px;
                }

                .styled-table tbody tr {
                    border-bottom: 1px solid #dddddd;
                }

                .styled-table tbody tr:nth-of-type(even) {
                    background-color: #f3f3f3;
                }

                .styled-table tbody tr:last-of-type {
                    border-bottom: 2px solid #009879;
                }

                .styled-table tbody tr.active-row {
                    font-weight: bold;
                    color: #009879;
                }
               </style>";

    echo "<h3>Produtos Vinculados:</h3>";
    echo "<table class='styled-table'>
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>Lote</th>
                    <th>Conjunto</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>";
            if ($result->num_rows > 0) {
                while ($produto = $result->fetch_assoc()) {
                    $id_lotes = htmlspecialchars($produto['id_lotes']);
                    echo "<tr>
                            <td>" . htmlspecialchars($produto['equipamento']) . "</td>
                            <td>" . htmlspecialchars($produto['lote']) . "</td>
                            <td>" . htmlspecialchars($produto['conjunto']) . "</td>
                            <td>
                                <button onclick=\"excluirVinculo('$id_lotes')\">Excluir</button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'><em>Nenhum produto vinculado a este pedido.</em></td></tr>";
            }
    
            echo "</tbody></table>";
            $stmt->close();
        } else {
            echo "<p>ID do pedido não fornecido.</p>";
        }
            ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido</title>
    <style>
    /* Estilos do modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0, 0, 0);
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
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
    </style>
</head>

<body>
    <!-- O modal -->
    <div id="editarDatasModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharEditarDatasModal()">&times;</span>
            <h2>Editar Datas</h2>
            <label for="equipamentoInput">Equipamento:</label>
            <input type="text" id="equipamentoInput" readonly><br>
            <label for="loteInput">Lote:</label>
            <input type="text" id="loteInput" readonly><br>
            <label for="conjuntoInput">Conjunto:</label>
            <input type="text" id="conjuntoInput" readonly><br>
            <label for="dataProgramacaoInput">Data Programação:</label>
            <input type="date" id="dataProgramacaoInput"><br>
            <label for="dataPCPInput">Data PCP:</label>
            <input type="date" id="dataPCPInput"><br>
            <label for="dataProducaoInput">Data Produção:</label>
            <input type="date" id="dataProducaoInput"><br><br>
            <button onclick="salvarDatasEspecifico()">Salvar</button>
        </div>
    </div>

    <script>
        // Funções JavaScript para manipulação do modal e chamadas AJAX
        function abrirEditarDatasModal(equipamento, lote, conjunto, dataProgramacao, dataPCP, dataProducao) {
            document.getElementById('equipamentoInput').value = equipamento;
            document.getElementById('loteInput').value = lote;
            document.getElementById('conjuntoInput').value = conjunto;
            document.getElementById('dataProgramacaoInput').value = dataProgramacao;
            document.getElementById('dataPCPInput').value = dataPCP;
            document.getElementById('dataProducaoInput').value = dataProducao;
            document.getElementById('editarDatasModal').style.display = 'block';
        }

        function fecharEditarDatasModal() {
            document.getElementById('editarDatasModal').style.display = 'none';
        }

        function salvarDatasEspecifico() {
            var equipamento = document.getElementById('equipamentoInput').value;
            var lote = document.getElementById('loteInput').value;
            var conjunto = document.getElementById('conjuntoInput').value;
            var dataProgramacao = document.getElementById('dataProgramacaoInput').value;
            var dataPCP = document.getElementById('dataPCPInput').value;
            var dataProducao = document.getElementById('dataProducaoInput').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'salvar_datas.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Datas salvas com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar datas.');
                }
            };
            xhr.send('equipamento=' + encodeURIComponent(equipamento) +
                '&lote=' + encodeURIComponent(lote) +
                '&conjunto=' + encodeURIComponent(conjunto) +
                '&data_programacao=' + encodeURIComponent(dataProgramacao) +
                '&data_pcp=' + encodeURIComponent(dataPCP) +
                '&data_producao=' + encodeURIComponent(dataProducao));
        }

    </script>
</body>
</html>