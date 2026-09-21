<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function caminhoInicio()
{
    $pagina = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

    $pastas = array('/gerente/', '/balconista/', '/administrador/');

    foreach ($pastas as $pasta) {
        $posicao = strpos($pagina, $pasta);

        if ($posicao !== false) {
            return substr($pagina, 0, $posicao) . '/index.php';
        }
    }

    return 'index.php';
}

function exigirLogin($cargoNecessario = null)
{
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['farmacia_id'])) {
        header('Location: ' . caminhoInicio());
        exit;
    }

    if ($cargoNecessario != null && $_SESSION['cargo'] != $cargoNecessario) {
        header('Location: ' . caminhoInicio() . '?erro=acesso');
        exit;
    }
}

function exigirAdministrador()
{
    if (!isset($_SESSION['admin_id']) || ($_SESSION['perfil'] ?? '') != 'administrador') {
        header('Location: ' . caminhoInicio() . '?erro=acesso');
        exit;
    }
}
