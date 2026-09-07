<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';

exigirLogin('gerente');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: produtosgerente.php');
    exit;
}

$farmaciaId = (int) $_SESSION['farmacia_id'];
$nome = trim($_POST['nomeProduto']);
$preco = (float) str_replace(',', '.', $_POST['valor']);
$quantidade = (int) $_POST['quantidade'];
$estoqueMinimo = (int) $_POST['estoque_minimo'];
$prateleira = trim($_POST['prateleira']);
$descricao = '';

$imagem = basename($_FILES['imagem']['name']);
$temporario = $_FILES['imagem']['tmp_name'];
$extensao = strtolower(pathinfo($imagem, PATHINFO_EXTENSION));
$permitidas = array('jpg', 'jpeg', 'png', 'webp');

if (!in_array($extensao, $permitidas)) {
    die('Imagem invalida. Use JPG, PNG ou WEBP.');
}

if ($_FILES['imagem']['size'] > 3000000) {
    die('A imagem deve ter no maximo 3 MB.');
}

$pasta = __DIR__ . '/uploads/';

if (!is_dir($pasta)) {
    mkdir($pasta);
}

$nomeImagem = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $imagem);
move_uploaded_file($temporario, $pasta . $nomeImagem);

$sql = 'INSERT INTO produtos
        (farmacia_id, nome, descricao, preco, quantidade,
         estoque_minimo, imagem, prateleira)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    'issdiiss',
    $farmaciaId,
    $nome,
    $descricao,
    $preco,
    $quantidade,
    $estoqueMinimo,
    $nomeImagem,
    $prateleira
);

mysqli_stmt_execute($stmt);

header('Location: produtosgerente.php?cadastro=1');
exit;
