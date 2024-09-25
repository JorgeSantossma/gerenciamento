<?php
include_once('config.php');

if (isset($_GET['id'])) {
    $idProduto = $_GET['id'];
    $sql = "SELECT * FROM produto WHERE idproduto = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $idProduto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();
        echo json_encode($produto);
    } else {
        echo json_encode(['error' => 'Produto não encontrado.']);
    }
    $stmt->close();
}

$conexao->close();
?>
