<?php

function registrarAuditoria($conexao, $acao, $entidade, $entidadeId = null, $descricao = '')
{
    $farmaciaId = isset($_SESSION['farmacia_id']) ? (int) $_SESSION['farmacia_id'] : null;
    $usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    $perfil = $_SESSION['perfil'] ?? ($_SESSION['cargo'] ?? 'sistema');

    $sql = 'INSERT INTO auditoria
            (farmacia_id, usuario_id, admin_id, perfil, acao, entidade, entidade_id, descricao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        'iiisssis',
        $farmaciaId,
        $usuarioId,
        $adminId,
        $perfil,
        $acao,
        $entidade,
        $entidadeId,
        $descricao
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
