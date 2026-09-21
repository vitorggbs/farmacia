<?php
// Toda configuração sensível deve ser fornecida por variáveis de ambiente.
$host = getenv('DB_HOST') ?: 'db';
$porta = (int) (getenv('DB_PORT') ?: 3306);
$usuarioBanco = getenv('DB_USER') ?: 'farmacia_app';
$senhaBanco = getenv('DB_PASSWORD');
$nomeBanco = getenv('DB_NAME') ?: 'farmacerta';
$socket = getenv('DB_SOCKET') ?: null;

mysqli_report(MYSQLI_REPORT_OFF);
$conexao = $socket
    ? @mysqli_connect(null, $usuarioBanco, $senhaBanco ?: '', $nomeBanco, null, $socket)
    : @mysqli_connect($host, $usuarioBanco, $senhaBanco ?: '', $nomeBanco, $porta);

if (!$conexao) {
    error_log('Falha na conexão MySQL: '.mysqli_connect_error());
    http_response_code(503);
    exit('Serviço temporariamente indisponível. Tente novamente mais tarde.');
}
mysqli_set_charset($conexao, 'utf8mb4');
$nomeFarmacia = 'Farmácia';

if (isset($_SESSION['farmacia_id'])) {
    $farmaciaId = (int) $_SESSION['farmacia_id'];
    $stmtFarmacia = mysqli_prepare($conexao, 'SELECT nome FROM farmacias WHERE id = ?');
    mysqli_stmt_bind_param($stmtFarmacia, 'i', $farmaciaId);
    mysqli_stmt_execute($stmtFarmacia);
    mysqli_stmt_bind_result($stmtFarmacia, $nomeEncontrado);
    if (mysqli_stmt_fetch($stmtFarmacia)) $nomeFarmacia = $nomeEncontrado;
    mysqli_stmt_close($stmtFarmacia);
}
