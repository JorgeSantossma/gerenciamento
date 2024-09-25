<?php
include_once('config.php');

// Configurar o tipo de conteúdo para JSON
header('Content-Type: application/json');

// Inicializar a resposta
$response = array();

// Captura os dados enviados
$pedido_id = isset($_POST['pedido_id']) ? (int) $_POST['pedido_id'] : 0;
$data_programacao = isset($_POST['data_programacao']) && !empty($_POST['data_programacao']) ? $_POST['data_programacao'] : null;
$data_pcp = isset($_POST['data_pcp']) && !empty($_POST['data_pcp']) ? $_POST['data_pcp'] : null;
$data_producao = isset($_POST['data_producao']) && !empty($_POST['data_producao']) ? $_POST['data_producao'] : null;

// Validação básica
if ($pedido_id > 0) {
    // Prepara a consulta SQL
    $sql = "UPDATE cliente_produto SET 
        data_programacao = COALESCE(?, data_programacao), 
        data_pcp = COALESCE(?, data_pcp), 
        data_producao = COALESCE(?, data_producao)
        WHERE cliente_id = ?";
    
    if ($stmt = $conexao->prepare($sql)) {
        $stmt->bind_param('sssi', $data_programacao, $data_pcp, $data_producao, $pedido_id);

        // Executa a consulta
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Dados atualizados com sucesso!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Erro ao atualizar dados: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Erro na preparação da consulta: ' . $conexao->error;
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'ID inválido ou dados insuficientes para atualização.';
}

// Fecha a conexão com o banco
$conexao->close();

// Retorna a resposta em formato JSON
echo json_encode($response);
?>
