<?php

session_start();

mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/../../autenticacao.php';
require_once __DIR__ . '/../conexaoDB.php';

exigirLogin('gerente');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: funcionarios.php');
    exit;
}

$farmaciaId = (int) $_SESSION['farmacia_id'];
$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$nascimento = trim($_POST['nascimento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$admissao = trim($_POST['admissao'] ?? '');
$salarioTexto = trim($_POST['salario'] ?? '');
$horario = trim($_POST['horario'] ?? '');
$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';
$cargo = 'balconista';

if ($nome == '' || $cpf == '' || $login == '' || $senha == '') {
    die('Preencha os campos obrigatorios. <a href="funcionarios.php#cadastrar">Voltar</a>');
}

if (strlen($cpf) != 11) {
    die('CPF invalido. <a href="funcionarios.php#cadastrar">Voltar</a>');
}

if (strlen($senha) < 6) {
    die('A senha deve ter pelo menos 6 caracteres. <a href="funcionarios.php#cadastrar">Voltar</a>');
}

if ($nascimento == '') {
    $nascimento = null;
}

if ($admissao == '') {
    $admissao = null;
}

$salarioTexto = str_replace(array('R$', ' '), '', $salarioTexto);
$salarioTexto = str_replace('.', '', $salarioTexto);
$salarioTexto = str_replace(',', '.', $salarioTexto);
$salario = $salarioTexto == '' ? 0 : (float) $salarioTexto;

if ($horario != '') {
    $horario .= ' / 6x1';
}

$sql = 'INSERT INTO usuarios
        (farmacia_id, nome, cpf, telefone, email, endereco,
         data_nascimento, data_admissao, salario, horario_escala,
         login, senha, cargo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    'isssssssdssss',
    $farmaciaId,
    $nome,
    $cpf,
    $telefone,
    $email,
    $endereco,
    $nascimento,
    $admissao,
    $salario,
    $horario,
    $login,
    $senha,
    $cargo
);

if (mysqli_stmt_execute($stmt)) {
    header('Location: funcionarios.php?ok=1#cadastrar');
    exit;
}

if (mysqli_stmt_errno($stmt) == 1062) {
    die('CPF ou login ja cadastrado. <a href="funcionarios.php#cadastrar">Voltar</a>');
}

die('Erro ao cadastrar funcionario: ' . htmlspecialchars(mysqli_stmt_error($stmt)));
