<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];
$produtoId = (int) $_POST['produto_id'];
$quantidade = (int) $_POST['quantidade'];

if ($produtoId < 1 || $quantidade < 1) {
    header('Location: produtosgerente.php?erro=reposicao');
    exit;
}

$sql = 'UPDATE produtos
        SET quantidade = quantidade + ?
        WHERE id = ? AND farmacia_id = ?';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'iii', $quantidade, $produtoId, $farmaciaId);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) == 0) {
    header('Location: produtosgerente.php?erro=produto');
    exit;
}

$tipo = 'entrada';
$observacao = 'Reposicao manual';

$sql = 'INSERT INTO movimentacoes_estoque
        (farmacia_id, produto_id, usuario_id, tipo, quantidade, observacao)
        VALUES (?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param(
    $stmt,
    'iiisis',
    $farmaciaId,
    $produtoId,
    $usuarioId,
    $tipo,
    $quantidade,
    $observacao
);
mysqli_stmt_execute($stmt);

header('Location: produtosgerente.php?ok=reposto');
exit;
