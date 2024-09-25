<?php
// obter_cliente_produto.php
header('Content-Type: application/json');

// Conectar ao banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "formulario_cadastro";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;

$sql = "SELECT * FROM cliente_produto WHERE pedido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

$produtos = array();

while ($row = $result->fetch_assoc()) {
    $produtos[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($produtos);
?>