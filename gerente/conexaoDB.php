<?php

$host = 'localhost';
$usuarioBanco = 'root';
$senhaBanco = 'usbw';
$nomeBanco = 'farmacerta';

$conexao = mysqli_connect(
    $host,
    $usuarioBanco,
    $senhaBanco,
    $nomeBanco
);

if (!$conexao) {
    die('Erro ao conectar ao banco: ' . mysqli_connect_error());
}

mysqli_set_charset($conexao, 'utf8mb4');

$nomeFarmacia = 'Farmacia';

if (isset($_SESSION['farmacia_id'])) {
    $farmaciaId = (int) $_SESSION['farmacia_id'];

    $sqlFarmacia = 'SELECT nome FROM farmacias WHERE id = ?';
    $stmtFarmacia = mysqli_prepare($conexao, $sqlFarmacia);
    mysqli_stmt_bind_param($stmtFarmacia, 'i', $farmaciaId);
    mysqli_stmt_execute($stmtFarmacia);
    mysqli_stmt_bind_result($stmtFarmacia, $nomeEncontrado);

    if (mysqli_stmt_fetch($stmtFarmacia)) {
        $nomeFarmacia = $nomeEncontrado;
    }

    mysqli_stmt_close($stmtFarmacia);
}
