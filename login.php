<?php

require_once __DIR__ . '/includes/seguranca.php';

require_once __DIR__ . '/gerente/conexaoDB.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

validarCsrf();

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'), 0, 45);

// Limite: cinco falhas para o mesmo login/IP em 15 minutos.
$stmtLimite = mysqli_prepare($conexao, 'SELECT COUNT(*) total FROM tentativas_login WHERE login = ? AND ip = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
mysqli_stmt_bind_param($stmtLimite, 'ss', $login, $ip);
mysqli_stmt_execute($stmtLimite);
$falhas = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmtLimite))['total'] ?? 0);
mysqli_stmt_close($stmtLimite);
if ($falhas >= 5) {
    header('Retry-After: 900');
    header('Location: index.php?erro=bloqueado');
    exit;
}

function registrarFalhaLogin(mysqli $conexao, string $login, string $ip): void
{
    $stmt = mysqli_prepare($conexao, 'INSERT INTO tentativas_login (login, ip) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ss', $login, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function limparFalhasLogin(mysqli $conexao, string $login, string $ip): void
{
    $stmt = mysqli_prepare($conexao, 'DELETE FROM tentativas_login WHERE login = ? AND ip = ?');
    mysqli_stmt_bind_param($stmt, 'ss', $login, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($cargo == 'administrador') {
    $sql = 'SELECT id, nome, senha
            FROM administradores
            WHERE login = ?
            AND ativo = 1';

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 's', $login);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($resultado);

    $senhaValida = false;
    if ($admin) {
        if (password_verify($senha, $admin['senha'])) {
            $senhaValida = true;
            if (password_needs_rehash($admin['senha'], PASSWORD_BCRYPT)) {
                $novoHash = password_hash($senha, PASSWORD_BCRYPT);
                $stmtRehash = mysqli_prepare($conexao, 'UPDATE administradores SET senha = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $admin['id']);
                mysqli_stmt_execute($stmtRehash);
                mysqli_stmt_close($stmtRehash);
            }
        }
    }

    if (!$senhaValida) {
        registrarFalhaLogin($conexao, $login, $ip);
        header('Location: index.php?erro=login');
        exit;
    }

    session_regenerate_id(true);
    limparFalhasLogin($conexao, $login, $ip);

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_nome'] = $admin['nome'];
    $_SESSION['perfil'] = 'administrador';

    header('Location: administrador/inicioadmin.php');
    exit;
}

$sql = 'SELECT u.id, u.nome, u.senha, u.cargo, u.farmacia_id, f.nome AS farmacia_nome
        FROM usuarios u
        INNER JOIN farmacias f ON f.id = u.farmacia_id
        WHERE u.login = ?
        AND u.cargo = ?
        AND u.ativo = 1
        AND f.ativo = 1';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $login, $cargo);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

$senhaValida = false;
if ($usuario) {
    if (password_verify($senha, $usuario['senha'])) {
        $senhaValida = true;
        if (password_needs_rehash($usuario['senha'], PASSWORD_BCRYPT)) {
            $novoHash = password_hash($senha, PASSWORD_BCRYPT);
            $stmtRehash = mysqli_prepare($conexao, 'UPDATE usuarios SET senha = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $usuario['id']);
            mysqli_stmt_execute($stmtRehash);
            mysqli_stmt_close($stmtRehash);
        }
    }
}

if (!$senhaValida) {
    registrarFalhaLogin($conexao, $login, $ip);
    header('Location: index.php?erro=login');
    exit;
}

session_regenerate_id(true);
limparFalhasLogin($conexao, $login, $ip);

$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['cargo'] = $usuario['cargo'];
$_SESSION['farmacia_id'] = $usuario['farmacia_id'];
$_SESSION['farmacia_nome'] = $usuario['farmacia_nome'];
$_SESSION['perfil'] = $usuario['cargo'];
$_SESSION['carrinho'] = array();

if ($usuario['cargo'] == 'gerente') {
    header('Location: gerente/iniciogerente.php');
} else {
    header('Location: balconista/iniciobalconista.php');
}

exit;
