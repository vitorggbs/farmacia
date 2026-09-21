<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__.'/gerente/conexaoDB.php';
$nome = getenv('ADMIN_NAME') ?: 'Administrador do Sistema';
$login = getenv('ADMIN_LOGIN') ?: '';
$senha = getenv('ADMIN_PASSWORD') ?: '';
if ($login === '' || strlen($senha) < 12) {
    fwrite(STDERR, "Defina ADMIN_LOGIN e ADMIN_PASSWORD (mínimo de 12 caracteres).\n");
    exit(1);
}
$hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conexao, 'INSERT INTO administradores (nome,login,senha) VALUES (?,?,?)');
mysqli_stmt_bind_param($stmt, 'sss', $nome, $login, $hash);
if (!mysqli_stmt_execute($stmt)) {
    fwrite(STDERR, "Não foi possível criar o administrador. Verifique se o login já existe.\n");
    exit(1);
}
fwrite(STDOUT, "Administrador criado com sucesso.\n");
