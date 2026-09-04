<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$produtoId = (int) $_POST['produto_id'];

$sql = 'UPDATE produtos
        SET ativo = 0
        WHERE id = ? AND farmacia_id = ?';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $produtoId, $farmaciaId);
mysqli_stmt_execute($stmt);

header('Location: produtosgerente.php?ok=excluido');
exit;
