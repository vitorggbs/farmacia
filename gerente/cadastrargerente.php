<?php

// Conexão com o banco de dados
$host = "localhost";
$db   = "bancodasfarmacias.sql";
$user = "root";
$pass = "usbw";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado com sucesso!";
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}

// Dados a serem preenchidos
$nomeproduto = $_POST['nomeProduto'];
$valor = $_POST['valor'];
$quantidade = $_POST['quantidade'];
$prateleira = $_POST['prateleira'];
$imagem = $_POST['imagem'];