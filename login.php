<?php

session_start();

require_once __DIR__ . '/gerente/conexaoDB.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';
$cargo = $_POST['cargo'] ?? '';

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
        } elseif ($admin['senha'] === $senha) {
            $senhaValida = true;
            $novoHash = password_hash($senha, PASSWORD_BCRYPT);
            $stmtRehash = mysqli_prepare($conexao, 'UPDATE administradores SET senha = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $admin['id']);
            mysqli_stmt_execute($stmtRehash);
            mysqli_stmt_close($stmtRehash);
        }
    }

    if (!$senhaValida) {
        header('Location: index.php?erro=login');
        exit;
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
    } elseif ($usuario['senha'] === $senha) {
        $senhaValida = true;
        $novoHash = password_hash($senha, PASSWORD_BCRYPT);
        $stmtRehash = mysqli_prepare($conexao, 'UPDATE usuarios SET senha = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmtRehash, 'si', $novoHash, $usuario['id']);
        mysqli_stmt_execute($stmtRehash);
        mysqli_stmt_close($stmtRehash);
    }
}

if (!$senhaValida) {
    header('Location: index.php?erro=login');
    exit;
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
