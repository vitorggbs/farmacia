<?php

require_once __DIR__ . '/../includes/seguranca.php';

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
$categoriaId = (int) ($_POST['categoria_id'] ?? 0);
if ($categoriaId < 1) { $categoriaId = null; }

if ($categoriaId !== null) {
    $stmtCategoria = mysqli_prepare($conexao, 'SELECT id FROM categorias WHERE id=? AND farmacia_id=? AND ativo=1');
    mysqli_stmt_bind_param($stmtCategoria, 'ii', $categoriaId, $farmaciaId);
    mysqli_stmt_execute($stmtCategoria);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCategoria))) {
        header('Location: produtosgerente.php?erro=categoria'); exit;
    }
}

$imagem = basename($_FILES['imagem']['name'] ?? '');
$temporario = $_FILES['imagem']['tmp_name'] ?? '';
$mime = $temporario !== '' ? (new finfo(FILEINFO_MIME_TYPE))->file($temporario) : '';
$permitidas = array('image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp');

if (!isset($permitidas[$mime]) || !is_uploaded_file($temporario)) {
    die('Imagem invalida. Use JPG, PNG ou WEBP.');
}

if (($_FILES['imagem']['size'] ?? 0) > 3000000) {
    die('A imagem deve ter no maximo 3 MB.');
}

$pasta = __DIR__ . '/uploads/';

if (!is_dir($pasta)) {
    mkdir($pasta, 0750, true);
}

$nomeImagem = bin2hex(random_bytes(16)) . '.' . $permitidas[$mime];
if (!move_uploaded_file($temporario, $pasta . $nomeImagem)) {
    header('Location: produtosgerente.php?erro=imagem'); exit;
}

$sql = 'INSERT INTO produtos
        (farmacia_id, categoria_id, nome, descricao, preco, quantidade,
         estoque_minimo, imagem, prateleira)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    'iissdiiss',
    $farmaciaId,
    $categoriaId,
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
