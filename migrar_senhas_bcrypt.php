<?php

/**
 * Script de migração de senhas para hash Bcrypt no FarmaCerta.
 * Pode ser executado via terminal CLI ou pelo navegador por um administrador autenticado.
 */

if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/gerente/conexaoDB.php';
} else {
    require_once __DIR__ . '/includes/seguranca.php';
    require_once __DIR__ . '/autenticacao.php';
    require_once __DIR__ . '/gerente/conexaoDB.php';
    exigirAdministrador();
}

$ehCli = php_sapi_name() === 'cli';

function emitirMensagem(string $msg, bool $ehCli): void {
    if ($ehCli) {
        echo $msg . PHP_EOL;
    } else {
        echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "<br>\n";
    }
}

if (!$ehCli) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Migração de Senhas</title>';
    echo '<style>body { font-family: sans-serif; padding: 2rem; line-height: 1.6; background: #f8fafc; color: #0f172a; }</style></head><body>';
    echo '<h2>Migração de Senhas para Bcrypt</h2>';
}

emitirMensagem('Iniciando processo de migração de senhas...', $ehCli);

// 1. Ampliar tamanho da coluna para VARCHAR(255) se necessário
mysqli_query($conexao, 'ALTER TABLE administradores MODIFY COLUMN senha VARCHAR(255) NOT NULL');
mysqli_query($conexao, 'ALTER TABLE usuarios MODIFY COLUMN senha VARCHAR(255) NOT NULL');
emitirMensagem('Colunas de senha ajustadas para VARCHAR(255).', $ehCli);

// 2. Migrar administradores
$totalAdmins = 0;
$migradosAdmins = 0;

$resAdmins = mysqli_query($conexao, 'SELECT id, login, senha FROM administradores');
if ($resAdmins) {
    $stmtUpdateAdmin = mysqli_prepare($conexao, 'UPDATE administradores SET senha = ? WHERE id = ?');
    while ($admin = mysqli_fetch_assoc($resAdmins)) {
        $totalAdmins++;
        $info = password_get_info($admin['senha']);
        if ($info['algo'] === 0) {
            // Senha em texto puro -> converter para bcrypt
            $novoHash = password_hash($admin['senha'], PASSWORD_BCRYPT);
            mysqli_stmt_bind_param($stmtUpdateAdmin, 'si', $novoHash, $admin['id']);
            mysqli_stmt_execute($stmtUpdateAdmin);
            $migradosAdmins++;
        }
    }
    mysqli_stmt_close($stmtUpdateAdmin);
}

emitirMensagem("Administradores: {$totalAdmins} verificados, {$migradosAdmins} migrados para Bcrypt.", $ehCli);

// 3. Migrar usuários
$totalUsuarios = 0;
$migradosUsuarios = 0;

$resUsuarios = mysqli_query($conexao, 'SELECT id, login, senha FROM usuarios');
if ($resUsuarios) {
    $stmtUpdateUser = mysqli_prepare($conexao, 'UPDATE usuarios SET senha = ? WHERE id = ?');
    while ($usuario = mysqli_fetch_assoc($resUsuarios)) {
        $totalUsuarios++;
        $info = password_get_info($usuario['senha']);
        if ($info['algo'] === 0) {
            // Senha em texto puro -> converter para bcrypt
            $novoHash = password_hash($usuario['senha'], PASSWORD_BCRYPT);
            mysqli_stmt_bind_param($stmtUpdateUser, 'si', $novoHash, $usuario['id']);
            mysqli_stmt_execute($stmtUpdateUser);
            $migradosUsuarios++;
        }
    }
    mysqli_stmt_close($stmtUpdateUser);
}

emitirMensagem("Usuários: {$totalUsuarios} verificados, {$migradosUsuarios} migrados para Bcrypt.", $ehCli);
emitirMensagem('Migração concluída com sucesso!', $ehCli);

if (!$ehCli) {
    echo '<p><a href="administrador/inicioadmin.php">&larr; Voltar para o painel do administrador</a></p>';
    echo '</body></html>';
}
