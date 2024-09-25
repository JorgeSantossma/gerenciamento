<?php
// produtos_vinculados.php
include 'conexao.php'; // Inclua sua conexão com o banco de dados

$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
$result = $conexao->query("SELECT idproduto, equipamento, lote FROM produtos WHERE pedido_id = $pedido_id");

$produtos = [];
while ($row = $result->fetch_assoc()) {
    $produtos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($produtos);
?>
