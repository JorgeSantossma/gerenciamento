<?php
include_once('config.php');

// Função para validar datas
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Verifica se os dados obrigatórios foram enviados
if (!isset($_POST['produto_id'], $_POST['data_programacao'], $_POST['data_pcp'], $_POST['data_producao'])) {
    die('Dados obrigatórios estão faltando.');
}

// Recebe os dados enviados via POST
$produto_id = $_POST['produto_id'];
$data_programacao = $_POST['data_programacao'];
$data_pcp = $_POST['data_pcp'];
$data_producao = $_POST['data_producao'];

// Valida as datas
if (!validateDate($data_programacao) || !validateDate($data_pcp) || !validateDate($data_producao)) {
    die('Por favor, insira datas válidas no formato Y-m-d.');
}

// Atualiza os dados no banco de dados
$sql = "UPDATE cliente_produto 
        SET data_programacao = ?, data_pcp = ?, data_producao = ? 
        WHERE produto_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('ssss', $data_programacao, $data_pcp, $data_producao, $produto_id);

if ($stmt->execute()) {
    echo 'Dados atualizados com sucesso!';
} else {
    echo 'Erro ao atualizar dados: ' . $stmt->error;
}

// Fecha a conexão
$stmt->close();
$conexao->close();
?>
