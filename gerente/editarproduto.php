<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$produtoId = (int) $_POST['produto_id'];
$nome = trim($_POST['nome']);
$preco = (float) $_POST['preco'];
$estoqueMinimo = (int) $_POST['estoque_minimo'];
$prateleira = trim($_POST['prateleira']);
$categoriaId = (int) ($_POST['categoria_id'] ?? 0);
if ($categoriaId < 1) { $categoriaId = null; }

$sql = 'UPDATE produtos
        SET nome = ?, preco = ?, estoque_minimo = ?, prateleira = ?, categoria_id = ?
        WHERE id = ? AND farmacia_id = ?';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param(
    $stmt,
    'sdisiii',
    $nome,
    $preco,
    $estoqueMinimo,
    $prateleira,
    $categoriaId,
    $produtoId,
    $farmaciaId
);
mysqli_stmt_execute($stmt);

header('Location: produtosgerente.php?ok=editado');
exit;
