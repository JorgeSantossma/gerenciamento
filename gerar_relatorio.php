<?php
include_once('config.php'); 
include('protect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pedido'])) {
    $pedido = $_POST['pedido'];

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
        WHERE c.pedido = ?
        GROUP BY c.idcliente
        ORDER BY c.idcliente DESC";

    $stmt = $conexao->prepare($sql_lista_pedidos);
    $stmt->bind_param("s", $pedido); // 's' indica que o parâmetro é uma string
    $stmt->execute();
    $result_lista_pedidos = $stmt->get_result();

    // Verifica se a consulta foi bem-sucedida
    if (!$result_lista_pedidos) {
        echo "Erro ao carregar lista de pedidos: " . $conexao->error;
        exit;
    }

    // Estilos para impressão e visualização
    echo '<style>
    body {
        display: flex; 
        flex-direction: column;
        align-items: center;
        padding: 20px;
        font-family: Arial, sans-serif;
    }
    
    h1 {
        text-transform: uppercase;
        text-align: center;
        margin-bottom: 20px;
    }
    
    th {
        background-color: #009879;
        color: white;
        text-transform: uppercase;
    }
    td {
        font-size: 90%;
    }
    
    th, td {
        padding: 3px;
        border: 1px solid #ddd;
        text-align: center;
        text-transform: uppercase;
    }

    .relatorio, .relatorio-produtos {
        width: 100%;
        max-width: 800px;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    @media print {
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: black;
        }

        .relatorio, .relatorio-produtos {
            width: 100%;
            max-width: 1000px;
            border-collapse: collapse;
            margin: 0 auto;
            color: black;            
            margin-bottom: 20px;
        }

        .relatorio th, .relatorio-produtos th, .relatorio td, .relatorio-produtos td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
            color: black;
        }

        .relatorio th, .relatorio-produtos th {
            background-color: #f2f2f2;
            color: black;
        }

        .voltar-container {
            display: none;
        }
    }

    .voltar-btn {
        background-color: #009879;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 16px;
        border-radius: 5px;
        text-transform: uppercase;
        text-decoration: none;
        display: flex;
    }
</style>';

echo '<div class="voltar-container">
<a href="obra.php" class="voltar-btn">Voltar</a>
</div>';

    // Exibe o relatório em HTML
    echo '<h1>Relatório de Pedido</h1>';
    echo '<table class="relatorio" border="1">';
    echo '<tr>
            <th>Pedido</th>
            <th>Cliente</th>
            <th>Localidade</th>
            <th>Data de Entrega</th>
          </tr>';

    while ($row = $result_lista_pedidos->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['pedido']) . '</td>';
        echo '<td>' . htmlspecialchars($row['cliente']) . '</td>';
        echo '<td>' . htmlspecialchars($row['endereco']) . '</td>';
        $dataOriginal = $row['data_entrega'];
        $data = new DateTime($dataOriginal);
        $dataFormatada = $data->format('d/m/Y');
        echo '<td>' . htmlspecialchars($dataFormatada) . '</td>';
        echo '</tr>';

        // Exibir produtos vinculados
        if (!empty($row['produtos'])) {
            echo "<table class='relatorio-produtos' border='1'>
                    <tr>
                        <th>Equipamento</th>
                        <th>Lote</th>
                        <th>Conjunto</th>
                        <th>Data de Engenharia</th>
                        <th>Data de Programação</th>
                        <th>Data de PCP</th>
                        <th>Data de Produção</th>
                    </tr>";

            // Divida a string de produtos
            $produtos = explode('%%', $row['produtos']);
            foreach ($produtos as $produto) {
                $dados = explode('||', $produto);
                $equipamento = htmlspecialchars($dados[0] ?? 'N/A');
                $lote = htmlspecialchars($dados[1] ?? 'N/A');
                $conjunto = htmlspecialchars($dados[2] ?? 'N/A');

                // Formatação das datas
                // Formatação das datas
            $data_engenharia = ($dados[3] && $dados[3] != '0000-00-00' && $dados[3] != 'N/A') ? (new DateTime($dados[3]))->format('d/m/Y') : 'N/A';
            $data_programacao = ($dados[4] && $dados[4] != '0000-00-00' && $dados[4] != 'N/A') ? (new DateTime($dados[4]))->format('d/m/Y') : 'N/A';
            $data_pcp = ($dados[5] && $dados[5] != '0000-00-00' && $dados[5] != 'N/A') ? (new DateTime($dados[5]))->format('d/m/Y') : 'N/A';
            $data_producao = ($dados[6] && $dados[6] != '0000-00-00' && $dados[6] != 'N/A') ? (new DateTime($dados[6]))->format('d/m/Y') : 'N/A';

                echo "<tr>
                        <td>$equipamento</td>
                        <td>$lote</td>
                        <td>$conjunto</td>                                    
                        <td>$data_engenharia</td>
                        <td>$data_programacao</td>
                        <td>$data_pcp</td>
                        <td>$data_producao</td>                        
                    </tr>";
            }
            echo '</table>';
        }
    }
    echo '</table>';

    // Fecha a conexão
    $stmt->close();
    $conexao->close();
} else {
    echo "Pedido não especificado.";
    exit;
}
?>
