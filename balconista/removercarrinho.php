<?php
session_start();

$produto_id = isset($_POST['produto_id']) ? (int) $_POST['produto_id'] : 0;

if ($produto_id > 0 && isset($_SESSION['carrinho'][$produto_id])) {
    unset($_SESSION['carrinho'][$produto_id]);
}

header('Location: carrinhobalconista.php');
exit;
?>
