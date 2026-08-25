<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Evita que o mysqli encerre a página sem mostrar o erro */
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . '/../conexaoDB.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: funcionarios.php');
    exit;
}

$nome = trim($_POST['nome_completo']);
$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$nascimento = $_POST['data_nascimento'];
$telefone = trim($_POST['telefone']);
$email = trim($_POST['email']);
$endereco = trim($_POST['endereco']);

$cargo = 'Caixa';

$admissao = $_POST['data_admissao'];
$salario = $_POST['salario'] == '' ? 0 : (float) $_POST['salario'];

$horario = trim($_POST['horario']);
$escala = '6x1';

$horario_escala = $horario . ' / ' . $escala;

$login = trim($_POST['login']);
$senha = $_POST['senha'];

$permissao = 'balconista';

$sql = "INSERT INTO funcionarios
        (nome_completo, cpf, data_nascimento, telefone, email, endereco,
         cargo, data_admissao, salario, horario_escala, login, senha, permissao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die('Erro ao preparar cadastro: ' . mysqli_error($conexao));
}

mysqli_stmt_bind_param(
    $stmt,
    'ssssssssdssss',
    $nome,
    $cpf,
    $nascimento,
    $telefone,
    $email,
    $endereco,
    $cargo,
    $admissao,
    $salario,
    $horario_escala,
    $login,
    $senha,
    $permissao
);

if (mysqli_stmt_execute($stmt)) {

    header('Location: funcionarios.php?cadastro=sucesso#cadastrar');
    exit;

} else {

    $erro = mysqli_stmt_error($stmt);

    if (mysqli_stmt_errno($stmt) == 1062) {
        echo '<h2>CPF ou login já cadastrado.</h2>';
    } else {
        echo '<h2>Erro ao cadastrar funcionário.</h2>';
        echo '<p>' . htmlspecialchars($erro) . '</p>';
    }

    echo '<br><a href="funcionarios.php#cadastrar">Voltar</a>';
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);

?>
