<?php

function cabecalhoAdmin($titulo)
{
    // Nome do administrador
    $nome = htmlspecialchars(
        $_SESSION['admin_nome'] ?? 'Administrador',
        ENT_QUOTES,
        'UTF-8'
    );

    // Descobre automaticamente o arquivo da página atual
    $paginaAtual = basename($_SERVER['PHP_SELF']);

    echo '<header class="topo">';

    // =====================================================
    // LOGO
    // =====================================================

    echo '<a href="inicioadmin.php" class="logo-link">';

    echo '<img 
            src="../assets/LOGO_1.png" 
            width="200" 
            alt="Logo FarmaCerta"
        >';

    echo '</a>';


    // =====================================================
    // TÍTULO
    // =====================================================

    echo '<h1>';
    echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    echo '</h1>';


    // =====================================================
    // USUÁRIO + SAIR
    // =====================================================

    echo '<div class="usuario-topo">';

    echo '<span>';
    echo $nome;
    echo '</span>';

    echo '<a class="logout" href="../logout.php">';
    echo 'SAIR';
    echo '</a>';

    echo '</div>';

    echo '</header>';


    // =====================================================
    // MENU
    // =====================================================

    echo '<nav class="menu">';


    // =====================================================
    // BOTÃO INÍCIO
    // =====================================================

    if ($paginaAtual === 'inicioadmin.php') {

        echo '<a href="inicioadmin.php" class="menu-ativo">';
        echo 'INÍCIO';
        echo '</a>';

    } else {

        echo '<a href="inicioadmin.php">';
        echo 'INÍCIO';
        echo '</a>';

    }


    // =====================================================
    // BOTÃO FARMÁCIAS
    // =====================================================

    if ($paginaAtual === 'farmacias.php') {

        echo '<a href="farmacias.php" class="menu-ativo">';
        echo 'FARMÁCIAS';
        echo '</a>';

    } else {

        echo '<a href="farmacias.php">';
        echo 'FARMÁCIAS';
        echo '</a>';

    }


    echo '</nav>';
}
