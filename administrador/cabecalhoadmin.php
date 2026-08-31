<?php

function cabecalhoAdmin($titulo)
{
    $nome = htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador');

    echo '<header class="topo">';
    echo '<img src="../assets/LOGO_1.png" width="200">';
    echo '<h1>' . $titulo . '</h1>';
    echo '<div class="usuario-topo">';
    echo '<span>' . $nome . '</span>';
    echo '<a class="logout" href="../logout.php">SAIR</a>';
    echo '</div>';
    echo '</header>';

    echo '<nav class="menu">';
    echo '<a href="inicioadmin.php">INÍCIO</a>';
    echo '<a href="farmacias.php">FARMÁCIAS</a>';
    echo '</nav>';
}
