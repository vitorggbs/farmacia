<?php

$host = "localhost";
$usuario = "root";
$senha = "usbw";
$banco = "farmacia1";

$conexao = mysqli_connect(
    $host,
    $usuario,
    $senha,
    $banco
);

if (!$conexao) {
    die("Erro ao conectar ao banco: " . mysqli_connect_error());
}

mysqli_set_charset($conexao, "utf8");
