<?php
include_once('config.php');

// Verificar se os parâmetros foram recebidos
if (isset($_POST['produto_id']) && isset($_POST['pedido_id'])) {
    $id_produto= $_POST['produto_id'];
    $pedido_id = $_POST['pedido_id'];

    // Preparar e executar a inserção
    $sql = "INSERT INTO cliente_produto (id_produto, pedido_id) VALUES (?, ?)";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ii", $id_produto, $pedido_id);
    
    if ($stmt->execute()) {
        echo "Produto adicionado com sucesso.";
    } else {
        echo "Erro ao adicionar produto: " . $conexao->error;
    }
    
    $stmt->close();
}

$conexao->close();
?>
