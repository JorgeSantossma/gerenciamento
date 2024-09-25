<?php

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    die("Voçe deve logar antes de acessar a página.<p><a href=\"login.php\">Entrar</a></p>");
}
?>