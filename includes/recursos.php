<?php

function prefixoRelativo(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($script, '/funcionarios/') !== false) {
        return '../../';
    }

    if (
        strpos($script, '/gerente/') !== false
        || strpos($script, '/balconista/') !== false
        || strpos($script, '/administrador/') !== false
    ) {
        return '../';
    }

    return '';
}

function prefixoPaginas(string $pasta): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($script, '/funcionarios/') !== false) {
        return '../';
    }

    if ($pasta === 'gerente' && strpos($script, '/balconista/') !== false) {
        return '../gerente/';
    }

    if ($pasta === 'balconista' && strpos($script, '/gerente/') !== false) {
        return '../balconista/';
    }

    return '';
}

function recursosCabeca(string $titulo): void
{
    $prefixo = htmlspecialchars(prefixoRelativo(), ENT_QUOTES, 'UTF-8');
    $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $arquivoTema = __DIR__ . '/../assets/css/tema.css';
    $versaoTema = is_file($arquivoTema) ? filemtime($arquivoTema) : time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $tituloSeguro; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $prefixo; ?>assets/LOGO_2.png">
    <link rel="manifest" href="<?php echo $prefixo; ?>manifest.webmanifest">
    <link rel="apple-touch-icon" href="<?php echo $prefixo; ?>assets/farmacerta-icon-192x192.png">
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FarmaCerta">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $prefixo; ?>assets/css/tema.css?v=<?php echo $versaoTema; ?>">
    <?php
}

function recursosRodape(): void
{
    $prefixo = json_encode(prefixoRelativo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script>';
    echo 'if ("serviceWorker" in navigator) {';
    echo 'window.addEventListener("load", function () {';
    echo 'navigator.serviceWorker.register(' . $prefixo . ' + "service-worker.js").catch(function (erro) {';
    echo 'console.error("Erro ao registrar o PWA:", erro);';
    echo '});';
    echo '});';
    echo '}';
    echo '</script>';
}
