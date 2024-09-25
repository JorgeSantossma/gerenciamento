<?php
include_once('config.php');
include('protect.php');

// Ativa exceções para erros de MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Consulta para listar os lotes dos produtos
$sql_lotes_query = "SELECT DISTINCT lote FROM produto ORDER BY lote";
$result_lotes = $conexao->query($sql_lotes_query);

if (!$result_lotes) {
    die("Erro ao buscar lotes: " . $conexao->error);
}

$lote = $_POST['lote'] ?? null;
$data_engenharia = $_POST['data_engenharia'] ?? null;
$data_programacao = $_POST['data_programacao'] ?? null;
$data_pcp = $_POST['data_pcp'] ?? null;
$data_producao = $_POST['data_producao'] ?? null;

if ($lote) {
    // Obter o produto_id a partir do lote na tabela produto
    $sql_get_produto_id = "SELECT idproduto FROM produto WHERE lote = ?";
    $stmt_get_produto_id = $conexao->prepare($sql_get_produto_id);

    if (!$stmt_get_produto_id) {
        die("Erro na preparação da consulta: " . $conexao->error);
    }

    $stmt_get_produto_id->bind_param("s", $lote);
    $stmt_get_produto_id->execute();
    $stmt_get_produto_id->bind_result($produto_id);
    $stmt_get_produto_id->fetch();
    $stmt_get_produto_id->close();

    if ($produto_id) {
        // Agora, obtenha o cliente_produto_id da tabela cliente_produto usando o produto_id
        $sql_get_cliente_produto_id = "SELECT id_lotes FROM cliente_produto WHERE id_produto = ?";
        $stmt_get_cliente_produto_id = $conexao->prepare($sql_get_cliente_produto_id);

        if (!$stmt_get_cliente_produto_id) {
            die("Erro na preparação da consulta: " . $conexao->error);
        }

        $stmt_get_cliente_produto_id->bind_param("i", $produto_id);
        $stmt_get_cliente_produto_id->execute();
        $stmt_get_cliente_produto_id->bind_result($cliente_produto_id);
        $stmt_get_cliente_produto_id->fetch();
        $stmt_get_cliente_produto_id->close();

        if ($cliente_produto_id) {
            echo "Registro encontrado: " . htmlspecialchars($cliente_produto_id) . "<br>";

            // Inserir ou atualizar as datas na tabela datas_produto
            $sql_upsert = "INSERT INTO datas_produto (cliente_produto_id, data_engenharia, datas_programacao, datas_pcp, datas_producao)
                           VALUES (?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE 
                           data_engenharia = VALUES(data_engenharia),
                           datas_programacao = VALUES(datas_programacao),
                           datas_pcp = VALUES(datas_pcp),
                           datas_producao = VALUES(datas_producao)";

            $stmt_upsert = $conexao->prepare($sql_upsert);

            if (!$stmt_upsert) {
                die("Erro na preparação da consulta de salvamento: " . $conexao->error);
            }

            // Vincular os parâmetros
            if (!$stmt_upsert->bind_param("issss", $cliente_produto_id, $data_engenharia, $data_programacao, $data_pcp, $data_producao)) {
                die("Erro ao vincular parâmetros: " . $stmt_upsert->error);
            }

            // Executar a consulta e verificar erros
            try {
                if ($stmt_upsert->execute()) {
                    // Determina qual data foi inserida
                    $data_inserida = '';
                    if (!empty($data_engenharia)) {
                        $data_inserida = 'engenharia';
                    } elseif (!empty($data_programacao)) {
                        $data_inserida = 'programacao';
                    } elseif (!empty($data_pcp)) {
                        $data_inserida = 'pcp';
                    } elseif (!empty($data_producao)) {
                        $data_inserida = 'producao';
                    }

                    // Redirecionar para obra.php com a informação da data inserida
                    header("Location: obra.php?data_inserida=" . $data_inserida);
                    exit;
                } else {
                    echo "Erro ao salvar as datas: " . htmlspecialchars($stmt_upsert->error) . "<br>";
                }
            } catch (mysqli_sql_exception $e) {
                echo "Erro ao salvar as datas: " . htmlspecialchars($e->getMessage()) . "<br>";
            }

            $stmt_upsert->close();
        } else {
            echo "Nenhum registro encontrado na tabela cliente_produto com produto_id = " . htmlspecialchars($produto_id) . ".<br>";
        }
    } else {
        echo "Nenhum produto encontrado com o lote = " . htmlspecialchars($lote) . ".<br>";
    }
} else {
    echo "Lote não fornecido.<br>";
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserir Datas no Banco de Dados</title>
    
</head>
<body>
    <h1>Inserir Datas</h1>

    <form action="salvar_datas.php" method="POST">
        <label for="lote">Lote do Produto:</label>
        <select id="lote" name="lote" required onchange="carregarDatas()">
            <option value="">Selecione um lote</option>
            <?php
            while ($row = $result_lotes->fetch_assoc()) {
                $lote = htmlspecialchars($row['lote']);
                echo "<option value=\"$lote\">$lote</option>";
            }
            ?>
        </select>
        <br><br>

        
        <label for="data_engenharia">Data de Engenharia:</label>
        <input type="date" id="data_engenharia" name="data_engenharia"">
        <br><br>

        <label for="data_programacao">Data de Programação:</label>
        <input type="date" id="data_programacao" name="data_programacao">
        <br><br>

        <label for="data_pcp">Data de PCP:</label>
        <input type="date" id="data_pcp" name="data_pcp">
        <br><br>

        <label for="data_producao">Data de Produção:</label>
        <input type="date" id="data_producao" name="data_producao">
        <br><br>

        <button type="submit" name="submit">Salvar Datas</button>
    </form>

<script>
function carregarDatas() {
    const lote = document.getElementById('lote').value;
    
    if (lote) {
        fetch('obter_dados.php?lote=' + lote)
            .then(response => response.json())
            .then(data => {
                document.getElementById('data_engenharia').value = data.data_engenharia || '';
                document.getElementById('data_programacao').value = data.data_programacao || '';
                document.getElementById('data_pcp').value = data.data_pcp || '';
                document.getElementById('data_producao').value = data.data_producao || '';
            })
            .catch(error => console.error('Erro ao carregar dados:', error));
    }
}
</script>
</body>
</html>
