<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirLogin('balconista');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$produtoId = (int) $_POST['produto_id'];
$quantidade = (int) $_POST['quantidade'];

$sql = 'SELECT quantidade
        FROM produtos
        WHERE id = ? AND farmacia_id = ? AND ativo = 1';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $produtoId, $farmaciaId);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$produto = mysqli_fetch_assoc($resultado);

if (!$produto || $quantidade < 1 || $quantidade > $produto['quantidade']) {
    header('Location: carrinhobalconista.php?erro=Quantidade invalida');
    exit;
}

$_SESSION['carrinho'][$produtoId] = $quantidade;

header('Location: carrinhobalconista.php');
exit;
