<?php

require_once __DIR__ . '/includes/recursos.php';

function cabecalho($titulo, $pasta, $paginaAtiva)
{
    $nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8');
    $nomeFarmacia = htmlspecialchars($_SESSION['farmacia_nome'] ?? 'Farmácia', ENT_QUOTES, 'UTF-8');
    $raiz = prefixoRelativo();
    $prefixo = prefixoPaginas($pasta);
    $quantidadeCarrinho = isset($_SESSION['carrinho']) ? (int) array_sum($_SESSION['carrinho']) : 0;

    if ($pasta === 'gerente') {
        $inicio = $prefixo . 'iniciogerente.php';
        $itens = array(
            'inicio' => array($prefixo . 'iniciogerente.php', 'INÍCIO'),
            'produtos' => array($prefixo . 'produtosgerente.php', 'PRODUTOS'),
            'lotes' => array($prefixo . 'lotes.php', 'LOTES/VALIDADE'),
            'movimentacoes' => array($prefixo . 'movimentacoesestoque.php', 'MOVIMENTAÇÕES'),
            'fornecedores' => array($prefixo . 'fornecedores.php', 'FORNECEDORES'),
            'entradas' => array($prefixo . 'entradasmercadoria.php', 'ENTRADAS'),
            'funcionarios' => array($prefixo . 'funcionarios/funcionarios.php', 'FUNCIONÁRIOS'),
            'clientes' => array($prefixo . 'clientes.php', 'CLIENTES'),
            'recibos' => array($prefixo . 'historicorecibogerente.php', 'RECIBOS'),
            'relatorios' => array($prefixo . 'relatorios.php', 'RELATÓRIOS'),
            'auditoria' => array($prefixo . 'auditoria.php', 'AUDITORIA'),
            'fiscal' => array($prefixo . 'fiscal.php', 'FISCAL'),
        );
    } else {
        $inicio = $prefixo . 'iniciobalconista.php';
        $carrinhoTexto = 'CARRINHO';
        if ($quantidadeCarrinho > 0) {
            $carrinhoTexto .= ' (' . $quantidadeCarrinho . ')';
        }
        $itens = array(
            'inicio' => array($prefixo . 'iniciobalconista.php', 'INÍCIO'),
            'produtos' => array($prefixo . 'produtosbalconista.php', 'PRODUTOS'),
            'clientes' => array($prefixo . 'clientes.php', 'CLIENTES'),
            'carrinho' => array($prefixo . 'carrinhobalconista.php', $carrinhoTexto),
            'historico' => array($prefixo . 'historicorecibobalconista.php', 'HISTÓRICO'),
        );
    }

    $identidade = $nomeFarmacia . ' — ' . $nomeUsuario;

    echo '<nav class="navbar navbar-expand-lg navbar-dark navbar-farmacia py-3">';
    echo '<div class="container-fluid px-3 px-lg-4">';
    echo '<a class="navbar-brand d-flex align-items-center gap-2" href="' . htmlspecialchars($inicio, ENT_QUOTES, 'UTF-8') . '">';
    echo '<img src="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'assets/LOGO_1.png" alt="FarmaCerta">';
    echo '</a>';
    echo '<span class="text-white fw-bold fs-5 d-none d-md-inline">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</span>';
    echo '<div class="d-flex align-items-center gap-2 ms-auto">';
    echo '<span class="text-white small d-none d-lg-inline">' . $identidade . '</span>';
    echo '<a class="btn btn-sair rounded-pill px-3 d-none d-lg-inline-flex" href="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'logout.php">SAIR</a>';
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menu">';
    echo '<span class="navbar-toggler-icon"></span>';
    echo '</button>';
    echo '</div>';
    echo '</div>';
    echo '</nav>';

    echo '<div class="menu-app py-2 d-none d-lg-block">';
    echo '<nav class="nav justify-content-center flex-wrap gap-2 px-3" aria-label="Menu principal">';
    foreach ($itens as $chave => $item) {
        $classe = $paginaAtiva === $chave ? ' nav-link rounded-pill px-3 py-2 active' : ' nav-link rounded-pill px-3 py-2';
        echo '<a class="' . $classe . '" href="' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</nav>';
    echo '</div>';

    echo '<div class="offcanvas offcanvas-end" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">';
    echo '<div class="offcanvas-header">';
    echo '<h2 class="offcanvas-title h5" id="menuLateralLabel">Menu</h2>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>';
    echo '</div>';
    echo '<div class="offcanvas-body d-flex flex-column gap-3">';
    echo '<p class="small text-secondary mb-0">' . $identidade . '</p>';
    echo '<nav class="nav flex-column gap-2 menu-app border-0 shadow-none bg-transparent">';
    foreach ($itens as $chave => $item) {
        $classe = $paginaAtiva === $chave ? ' nav-link rounded-pill px-3 py-2 active border border-primary' : ' nav-link rounded-pill px-3 py-2 border';
        echo '<a class="' . $classe . '" href="' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</nav>';
    echo '<a class="btn btn-primary rounded-pill mt-auto" href="' . htmlspecialchars($raiz, ENT_QUOTES, 'UTF-8') . 'logout.php">SAIR</a>';
    echo '</div>';
    echo '</div>';
}
