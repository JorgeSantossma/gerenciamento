<?php

include('config.php');

if(isset($_POST['usuario']) || isset($_POST['senha']))

    if(strlen($_POST['usuario']) == 0) {
    echo "<div class='error-message'>Preencha seu Usuário</div>";
    } else if(strlen($_POST['senha']) == 0) {
        echo "<div class='error-message'>Preencha sua senha</div>";
    } else {
        $usuario = $conexao->real_escape_string($_POST['usuario']);
        $senha = $conexao->real_escape_string($_POST['senha']);

        $sql_code = "SELECT * FROM login1 WHERE usuario = '$usuario' AND senha = '$senha'";
        $sql_query = $conexao->query($sql_code) or die("Falha na execução do código SQL:" . $conexao->error);
        
        $quantidade = $sql_query->num_rows;

        if($quantidade == 1){

            $usuario = $sql_query->fetch_assoc();

            if(!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id'] = $usuario['id_login'];
            $_SESSION['nome'] = $usuario['usuario'];

            header("Location: index.php");

        } else {
            echo "<div class='error-message'>Falha ao logar! E-mail ou senha incorretos</div>";
        }
    }


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/login.css">
    <style>
.error-message {
    color: red;
    font-weight: bold;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    margin: 0; 
    border-radius: 5px;
}
</style>
    <title>LOGIN</title>
</head>

<body>
    <form action="" method="POST">
        <div class="main-login">
            <div class="left-login">
                <h1>Acesse sua conta</h1>
                <img src="./img/inter.svg" class="left-img" alt="inteligencia">
            </div>

            <div class="right-login">
                <div class="card-login">

                    <h1>LOGIN</h1>


                    <div class="textfield">
                        <label for="usuario">Usuário</label>
                        <input type="text" name="usuario" placeholder="Usuário">
                    </div>
                    <div class="textfield">
                        <label for="senha">Senha</label>
                        <input type="password" name="senha" placeholder="Senha">
                    </div>
                    <div>
                        <button type="submit" class="btn-login">Entrar</button>
                    </div>

                </div>
            </div>
        </div>
    </form>
</body>

</html>