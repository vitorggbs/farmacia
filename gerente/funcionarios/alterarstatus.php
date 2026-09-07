<?php

session_start();

require_once __DIR__ . '/../../autenticacao.php';
require_once __DIR__ . '/../conexaoDB.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$funcionarioId = (int) $_POST['id'];

$sql = "UPDATE usuarios
        SET ativo = IF(ativo = 1, 0, 1)
        WHERE id = ?
        AND farmacia_id = ?
        AND cargo = 'balconista'";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $funcionarioId, $farmaciaId);
mysqli_stmt_execute($stmt);

header('Location: funcionarios.php');
exit;
