<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php'; // Inclua sua configuração de banco de dados

    // Recebe os valores capturados do JavaScript
    $pedido = $_POST['pedido'] ?? '';
    $cliente = $_POST['cliente'] ?? '';
    $localidade = $_POST['localidade'] ?? '';
    $dataEntrega = $_POST['dataEntrega'] ?? '';

    // Executa a consulta no banco de dados
    $sql = "SELECT * FROM cliente_produto AS cp
            WHERE cp.equipamento LIKE ? AND cp.localidade LIKE ? AND cp.data_entrega = ?";

    $stmt = $conexao->prepare($sql);
    $pedido_like = "%$pedido%";
    $cliente_like = "%$cliente%";
    $localidade_like = "%$localidade%";

    $stmt->bind_param("sss", $pedido_like, $cliente_like, $dataEntrega);
    $stmt->execute();
    $result = $stmt->get_result();

    // Exibe os resultados da busca
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Equipamento: " . htmlspecialchars($row['equipamento']) . "<br>";
            echo "Lote: " . htmlspecialchars($row['lote']) . "<br>";
            echo "Data Programação: " . htmlspecialchars($row['data_programacao']) . "<br>";
            echo "Data PCP: " . htmlspecialchars($row['data_pcp']) . "<br>";
            echo "Data Produção: " . htmlspecialchars($row['data_producao']) . "<br><hr>";
        }
    } else {
        echo "Nenhum resultado encontrado.";
    }

    $stmt->close();
    $conexao->close();
}
