<?php

require_once __DIR__ . '/../includes/recursos.php';

function cabecalhoAdmin($titulo)
{
    $nome = htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8');
    $paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
    $raiz = prefixoRelativo();

    echo '<nav class="navbar navbar-expand-lg navbar-dark navbar-farmacia py-3">';
    echo '<div class="container-fluid px-3 px-lg-4">';
    echo '<a class="navbar-brand" href="inicioadmin.php">';
    echo '<img src="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'assets/LOGO_1.png" alt="Logo FarmaCerta">';
    echo '</a>';
    echo '<span class="text-white fw-bold fs-5 d-none d-md-inline">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</span>';
    echo '<div class="d-flex align-items-center gap-2 ms-auto">';
    echo '<span class="text-white small d-none d-lg-inline">' . $nome . '</span>';
    echo '<a class="btn btn-sair rounded-pill px-3 d-none d-lg-inline-flex" href="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'logout.php">SAIR</a>';
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menu">';
    echo '<span class="navbar-toggler-icon"></span>';
    echo '</button>';
    echo '</div>';
    echo '</div>';
    echo '</nav>';

    echo '<div class="menu-app py-2 d-none d-lg-block">';
    echo '<nav class="nav justify-content-center flex-wrap gap-2 px-3" aria-label="Menu administrador">';
    imprimirLinksAdmin($paginaAtual);
    echo '</nav>';
    echo '</div>';

    echo '<div class="offcanvas offcanvas-end" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">';
    echo '<div class="offcanvas-header">';
    echo '<h2 class="offcanvas-title h5" id="menuLateralLabel">Menu</h2>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>';
    echo '</div>';
    echo '<div class="offcanvas-body d-flex flex-column gap-3">';
    echo '<p class="small text-secondary mb-0">' . $nome . '</p>';
    echo '<nav class="nav flex-column gap-2 menu-app border-0 shadow-none bg-transparent">';
    imprimirLinksAdmin($paginaAtual, true);
    echo '</nav>';
    echo '<a class="btn btn-primary rounded-pill mt-auto" href="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'logout.php">SAIR</a>';
    echo '</div>';
    echo '</div>';
}

function imprimirLinksAdmin($paginaAtual, $mobile = false)
{
    $inicioAtivo = in_array($paginaAtual, array('inicioadmin.php'), true);
    $farmaciasAtivo = in_array($paginaAtual, array('farmacias.php', 'editarfarmacia.php'), true);
    $auditoriaAtivo = in_array($paginaAtual, array('auditoria.php'), true);
    $backupAtivo = in_array($paginaAtual, array('backup.php'), true);
    $extra = $mobile ? ' border' : '';

    $classeInicio = 'nav-link rounded-pill px-3 py-2' . $extra . ($inicioAtivo ? ' active' : '');
    $classeFarmacias = 'nav-link rounded-pill px-3 py-2' . $extra . ($farmaciasAtivo ? ' active' : '');
    $classeAuditoria = 'nav-link rounded-pill px-3 py-2' . $extra . ($auditoriaAtivo ? ' active' : '');
    $classeBackup = 'nav-link rounded-pill px-3 py-2' . $extra . ($backupAtivo ? ' active' : '');

    echo '<a class="' . $classeInicio . '" href="inicioadmin.php">INÍCIO</a>';
    echo '<a class="' . $classeFarmacias . '" href="farmacias.php">FARMÁCIAS</a>';
    echo '<a class="' . $classeAuditoria . '" href="auditoria.php">AUDITORIA</a>';
    echo '<a class="' . $classeBackup . '" href="backup.php">BACKUP</a>';
}
