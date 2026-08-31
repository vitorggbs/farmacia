<?php

function cabecalho($titulo, $pasta, $paginaAtiva)
{
    $nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
    $nomeFarmacia = htmlspecialchars($_SESSION['farmacia_nome'] ?? 'Farmacia');

    echo '<header class="topo">';
    echo '<img src="../assets/LOGO_1.png" width="200">';
    echo '<h1>' . $titulo . '</h1>';
    echo '<div class="usuario-topo">';
    echo '<span>' . $nomeFarmacia . ' — ' . $nomeUsuario . '</span>';
    echo '<a class="logout" href="../logout.php">SAIR</a>';
    echo '</div>';
    echo '</header>';

    echo '<nav class="menu">';

    if ($pasta == 'gerente') {
        echo '<a href="iniciogerente.php">INÍCIO</a>';
        echo '<a href="produtosgerente.php">PRODUTOS</a>';
        echo '<a href="funcionarios/funcionarios.php">FUNCIONÁRIOS</a>';
        echo '<a href="historicorecibogerente.php">RECIBOS</a>';
    } else {
        echo '<a href="iniciobalconista.php">INÍCIO</a>';
        echo '<a href="produtosbalconista.php">PRODUTOS</a>';
        echo '<a href="carrinhobalconista.php">CARRINHO</a>';
        echo '<a href="historicorecibobalconista.php">HISTÓRICO</a>';
    }

    echo '</nav>';
}
