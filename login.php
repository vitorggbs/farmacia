<?php

session_start();

require_once __DIR__ . '/gerente/conexaoDB.php';
require_once __DIR__ . '/includes/seguranca.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = (string) ($_POST['senha'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');

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

    if (!$admin || !PasswordService::verify($senha, $admin['senha'])) {
        header('Location: index.php?erro=login');
        exit;
    }

    // Atualização automática transparente caso a senha seja legada ou precise de rehash
    if (PasswordService::needsRehash($admin['senha'])) {
        $novoHash = PasswordService::hash($senha);
        $stmtRehash = mysqli_prepare($conexao, 'UPDATE administradores SET senha = ? WHERE id = ?');
        if ($stmtRehash) {
            mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $admin['id']);
            mysqli_stmt_execute($stmtRehash);
            mysqli_stmt_close($stmtRehash);
        }
    }

    session_regenerate_id(true);

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

if (!$usuario || !PasswordService::verify($senha, $usuario['senha'])) {
    header('Location: index.php?erro=login');
    exit;
}

// Atualização automática transparente caso a senha seja legada ou precise de rehash
if (PasswordService::needsRehash($usuario['senha'])) {
    $novoHash = PasswordService::hash($senha);
    $stmtRehash = mysqli_prepare($conexao, 'UPDATE usuarios SET senha = ? WHERE id = ?');
    if ($stmtRehash) {
        mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $usuario['id']);
        mysqli_stmt_execute($stmtRehash);
        mysqli_stmt_close($stmtRehash);
    }
}

session_regenerate_id(true);

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
