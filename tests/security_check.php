<?php
$root = dirname(__DIR__);
$falhas = array();
$security = file_get_contents($root.'/includes/seguranca.php');
foreach (array('hash_equals','SameSite','Content-Security-Policy','HttpOnly') as $item) {
    if (stripos($security, $item) === false) $falhas[] = "Proteção ausente: {$item}";
}
$login = file_get_contents($root.'/login.php');
if (strpos($login, "['senha'] === \$senha") !== false) $falhas[] = 'Login ainda aceita senha em texto puro';
foreach (array('/administrador/alterarstatus.php','/gerente/categorias.php') as $arquivo) {
    $conteudo=file_get_contents($root.$arquivo);
    if (preg_match('/\$_GET\s*\[\s*[\'\"](?:status|id)[\'\"]/', $conteudo)) $falhas[]="Alteração por GET em {$arquivo}";
}
if ($falhas) { fwrite(STDERR, implode(PHP_EOL,$falhas).PHP_EOL); exit(1); }
echo "Verificações básicas de segurança aprovadas.\n";
