<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirAdministrador();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: farmacias.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$gerenteNome = trim($_POST['gerente_nome'] ?? '');
$gerenteCpf = preg_replace('/\D/', '', $_POST['gerente_cpf'] ?? '');
$gerenteLogin = trim($_POST['gerente_login'] ?? '');
$gerenteSenha = $_POST['gerente_senha'] ?? '';

if ($nome == '' || $gerenteNome == '' || $gerenteLogin == '' || strlen($gerenteSenha) < 6) {
    header('Location: farmacias.php?erro=campos#cadastrar');
    exit;
}

$sql = 'SELECT id FROM usuarios WHERE login = ?';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 's', $gerenteLogin);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_fetch_assoc($resultado)) {
    header('Location: farmacias.php?erro=login#cadastrar');
    exit;
}

mysqli_begin_transaction($conexao);

$sql = 'INSERT INTO farmacias (nome, cnpj, telefone, endereco) VALUES (?, ?, ?, ?)';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ssss', $nome, $cnpj, $telefone, $endereco);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_rollback($conexao);
    header('Location: farmacias.php?erro=banco#cadastrar');
    exit;
}

$farmaciaId = mysqli_insert_id($conexao);
$cargo = 'gerente';

$sql = 'INSERT INTO usuarios (farmacia_id, nome, cpf, login, senha, cargo)
        VALUES (?, ?, ?, ?, ?, ?)';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param(
    $stmt,
    'isssss',
    $farmaciaId,
    $gerenteNome,
    $gerenteCpf,
    $gerenteLogin,
    $gerenteSenha,
    $cargo
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_rollback($conexao);
    header('Location: farmacias.php?erro=banco#cadastrar');
    exit;
}

mysqli_commit($conexao);
header('Location: farmacias.php?ok=1');
exit;
