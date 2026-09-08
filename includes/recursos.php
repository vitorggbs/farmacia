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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $prefixo; ?>assets/css/tema.css?v=<?php echo $versaoTema; ?>">
    <?php
}

function recursosRodape(): void
{
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>';
}
