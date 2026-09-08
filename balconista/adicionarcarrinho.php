<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirLogin('balconista');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$produtoId = (int) $_POST['produto_id'];
$quantidade = (int) $_POST['quantidade'];

if ($quantidade < 1) {
    $quantidade = 1;
}

$sql = 'SELECT quantidade
        FROM produtos
        WHERE id = ? AND farmacia_id = ? AND ativo = 1';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $produtoId, $farmaciaId);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$produto = mysqli_fetch_assoc($resultado);

$quantidadeAtual = 0;

if (isset($_SESSION['carrinho'][$produtoId])) {
    $quantidadeAtual = $_SESSION['carrinho'][$produtoId];
}

if (!$produto || $quantidadeAtual + $quantidade > $produto['quantidade']) {
    header('Location: produtosbalconista.php?erro=Estoque insuficiente');
    exit;
}

$_SESSION['carrinho'][$produtoId] = $quantidadeAtual + $quantidade;

header('Location: produtosbalconista.php?ok=adicionado');
exit;
