<?php

include_once('config.php');
include('protect.php');

    $sql = "SELECT * FROM produto ORDER BY idproduto DESC";

    $result = $conexao->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/cadas.css">
    <title>TRILHADEIRA</title>
    <style type="text/css">
        main {
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            display: flex;
            flex: 20 0 500px;
            flex-wrap: wrap;
            overflow: auto;
            height: calc(100vh - 75px);
            margin: 3px;
         }
     </style>
</head>
<body>
    <header>
        <a href="pedido.php" id="logo"> <img src="./img/logoSMA.png" width="50%">
        </a>

        <button id="openMenu">&#9776;</button>

        <nav id="menu">
            <button id="closeMenu">X</button>
            <a href="pedido.php">PEDIDOS</a>
            <a href="trilhadeira.php">TRILHADEIRA</a>
            <a href="cadastro.php">CADASTRO</a>           
            <a href="obra.php">OBRAS</a>
            <a href="logout.php">SAIR</a>
        </nav>

    </header>

    <main>
    </main>

    <script type="text/javascript" src="./script.js"></script>
</body>
</html>
