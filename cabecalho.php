<?php

function cabecalho($titulo, $pasta, $paginaAtiva)
{
    $nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
    $nomeFarmacia = htmlspecialchars($_SESSION['farmacia_nome'] ?? 'Farmacia');

    // Define a página inicial de acordo com o tipo de usuário
    if ($pasta == 'gerente') {
        $inicio = 'iniciogerente.php';
    } else {
        $inicio = 'iniciobalconista.php';
    }

    echo '<header class="topo">';

    // Logo clicável que direciona para o início
    echo '<a href="' . $inicio . '" class="logo-link">';
    echo '<img src="../assets/LOGO_1.png" width="200" alt="Logo">';
    echo '</a>';

    echo '<h1>' . $titulo . '</h1>';

    echo '<div class="usuario-topo">';
    echo '<span>' . $nomeFarmacia . ' — ' . $nomeUsuario . '</span>';
    echo '<a class="logout" href="../logout.php">SAIR</a>';
    echo '</div>';

    echo '</header>';

    echo '<nav class="menu">';

    if ($pasta == 'gerente') {
        echo '<a href="iniciogerente.php"' . ($paginaAtiva == 'inicio' ? ' class="ativo"' : '') . '>INÍCIO</a>';
        echo '<a href="produtosgerente.php"' . ($paginaAtiva == 'produtos' ? ' class="ativo"' : '') . '>PRODUTOS</a>';
        echo '<a href="movimentacoesestoque.php"' . ($paginaAtiva == 'movimentacoes' ? ' class="ativo"' : '') . '>MOVIMENTAÇÕES</a>';
        echo '<a href="funcionarios/funcionarios.php"' . ($paginaAtiva == 'funcionarios' ? ' class="ativo"' : '') . '>FUNCIONÁRIOS</a>';
        echo '<a href="historicorecibogerente.php"' . ($paginaAtiva == 'recibos' ? ' class="ativo"' : '') . '>RECIBOS</a>';
    } else {
        echo '<a href="iniciobalconista.php">INÍCIO</a>';
        echo '<a href="produtosbalconista.php">PRODUTOS</a>';
        echo '<a href="carrinhobalconista.php">CARRINHO</a>';
        echo '<a href="historicorecibobalconista.php">HISTÓRICO</a>';
    }

    echo '</nav>';
}
