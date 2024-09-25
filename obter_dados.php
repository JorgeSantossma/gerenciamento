<?php
include_once('config.php');

$lote = $_GET['lote'] ?? null;

if ($lote) {
    // Consulta as datas salvas para o lote
    $sql = "SELECT data_engenharia, datas_programacao, datas_pcp, datas_producao FROM datas_produto 
            JOIN cliente_produto ON cliente_produto.id_lotes = datas_produto.cliente_produto_id 
            JOIN produto ON produto.idproduto = cliente_produto.id_produto 
            WHERE produto.lote = ?";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $lote);
    $stmt->execute();
    $stmt->bind_result($data_engenharia, $data_programacao, $data_pcp, $data_producao);
    $stmt->fetch();
    
    $stmt->close();
    $conexao->close();

    // Retorna os dados em formato JSON
    echo json_encode([
        'data_engenharia' => $data_engenharia,
        'data_programacao' => $data_programacao,
        'data_pcp' => $data_pcp,
        'data_producao' => $data_producao
    ]);
} else {
    echo json_encode([]);
}
?>