<?php
include_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_lote'])) {
    $id_lote = intval($_POST['id_lote']); // Obtém o id_lote enviado

    $sql = "DELETE FROM cliente_produto WHERE id_lotes = ?";
    $stmt = $conexao->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_lote); // 'i' indica que o parâmetro é um inteiro
        if ($stmt->execute()) {
            echo 'Vínculo excluído com sucesso!';
        } else {
            echo 'Erro ao excluir o vínculo: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        echo 'Erro na preparação da consulta: ' . $conexao->error;
    }
} else {
    echo 'Dados inválidos fornecidos.';
}

$conexao->close();
?>
