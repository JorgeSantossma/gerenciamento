<?php
include_once('config.php');
include('protect.php');

if (isset($_POST['cliente_id']) && isset($_POST['produto_id'])) {
    $id_cliente = $_POST['cliente_id'];
    $id_produto = $_POST['produto_id'];

    // Debug: Exibir dados recebidos
    error_log("Cliente ID: $id_cliente, Produto ID: $id_produto"); // Registro no log

    $sql_insert = "INSERT INTO cliente_produto (id_cliente, id_produto) VALUES (?, ?)";
    if ($stmt = $conexao->prepare($sql_insert)) {
        $stmt->bind_param("ii", $id_cliente, $id_produto);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Produto vinculado com sucesso!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao vincular produto: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da query: ' . $conexao->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
}

$conexao->close();
?>
