<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirAdministrador();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: farmacias.php');
    exit;
}

$sql = 'UPDATE farmacias SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: farmacias.php?ok=1');
exit;
