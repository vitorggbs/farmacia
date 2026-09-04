<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];

function buscarResumo($conexao, $farmaciaId, $condicao)
{
    $sql = "SELECT COUNT(*) AS vendas,
            COALESCE(SUM(valor_total), 0) AS total,
            COALESCE(AVG(valor_total), 0) AS media
            FROM vendas
            WHERE farmacia_id = ? AND $condicao";

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $dados;
}

function categorizarProduto($nome)
{
    $nome = mb_strtolower($nome, 'UTF-8');



    return 'Medicamentos';
}

$resumoDia = buscarResumo($conexao, $farmaciaId, 'DATE(data_venda) = CURDATE()');
$resumoMes = buscarResumo($conexao, $farmaciaId, 'YEAR(data_venda) = YEAR(CURDATE()) AND MONTH(data_venda) = MONTH(CURDATE())');

$sql = 'SELECT DATE(data_venda) AS dia, COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE farmacia_id = ?
        AND DATE(data_venda) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
        GROUP BY DATE(data_venda)
        ORDER BY dia';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultadoFaturamento = mysqli_stmt_get_result($stmt);
$faturamentoPorDia = array();
while ($linha = mysqli_fetch_assoc($resultadoFaturamento)) {
    $faturamentoPorDia[$linha['dia']] = (float) $linha['total'];
}
mysqli_stmt_close($stmt);

$graficoDias = array();
$graficoValores = array();
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i day"));
    $graficoDias[] = date('d/m', strtotime($data));
    $graficoValores[] = isset($faturamentoPorDia[$data]) ? $faturamentoPorDia[$data] : 0;
}

$sql = 'SELECT id, nome, quantidade, estoque_minimo, imagem
        FROM produtos
        WHERE farmacia_id = ? AND ativo = 1 AND quantidade <= estoque_minimo
        ORDER BY quantidade ASC, nome ASC
        LIMIT 5';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$produtosBaixo = mysqli_stmt_get_result($stmt);

$estoqueBaixo = array();
while ($produto = mysqli_fetch_assoc($produtosBaixo)) {
    $estoqueBaixo[] = $produto;
}
mysqli_stmt_close($stmt);

$sql = 'SELECT v.id, v.data_venda, v.forma_pagamento, v.valor_total,
        COALESCE(NULLIF(v.cliente, ""), "Cliente Final") AS cliente,
        u.nome AS balconista
        FROM vendas v
        INNER JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.farmacia_id = ?
        ORDER BY v.id DESC
        LIMIT 5';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$ultimas = mysqli_stmt_get_result($stmt);

$ultimasVendas = array();
while ($venda = mysqli_fetch_assoc($ultimas)) {
    $ultimasVendas[] = $venda;
}
mysqli_stmt_close($stmt);

$formas = array(
    'dinheiro' => 0,
    'debito' => 0,
    'credito' => 0,
    'pix' => 0
);

$sql = 'SELECT forma_pagamento, COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE farmacia_id = ?
        AND YEAR(data_venda) = YEAR(CURDATE())
        AND MONTH(data_venda) = MONTH(CURDATE())
        GROUP BY forma_pagamento';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultadoFormas = mysqli_stmt_get_result($stmt);
while ($forma = mysqli_fetch_assoc($resultadoFormas)) {
    $formas[$forma['forma_pagamento']] = (float) $forma['total'];
}
mysqli_stmt_close($stmt);

$totalPagamentos = array_sum($formas);

$categorias = array(
    'Medicamentos' => 0,
);

$sql = 'SELECT p.nome, SUM(iv.subtotal) AS total
        FROM itens_venda iv
        INNER JOIN produtos p ON p.id = iv.produto_id
        INNER JOIN vendas v ON v.id = iv.venda_id
        WHERE v.farmacia_id = ?
        AND YEAR(v.data_venda) = YEAR(CURDATE())
        AND MONTH(v.data_venda) = MONTH(CURDATE())
        GROUP BY p.id, p.nome';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultadoCategorias = mysqli_stmt_get_result($stmt);
while ($item = mysqli_fetch_assoc($resultadoCategorias)) {
    $categoria = categorizarProduto($item['nome']);
    if (!isset($categorias[$categoria])) {
        $categoria = 'Outros';
    }
    $categorias[$categoria] += (float) $item['total'];
}
mysqli_stmt_close($stmt);

$totalCategorias = array_sum($categorias);

$coresCategorias = array(
    'Medicamentos' => '#ef233c'
);

$angulos = array();
$acumulado = 0;
foreach ($categorias as $nome => $valor) {
    $percentual = $totalCategorias > 0 ? ($valor / $totalCategorias) * 100 : 0;
    $inicio = $acumulado;
    $fim = $acumulado + $percentual;
    $angulos[] = $coresCategorias[$nome] . ' ' . number_format($inicio, 2, '.', '') . '% ' . number_format($fim, 2, '.', '') . '%';
    $acumulado = $fim;
}

if ($totalCategorias == 0) {
    $fundoRosca = '#e5e7eb 0% 100%';
} else {
    $fundoRosca = implode(', ', $angulos);
}

$maiorGrafico = max($graficoValores);
if ($maiorGrafico <= 0) {
    $maiorGrafico = 1;
}

$pontosGrafico = array();
$areaGrafico = array('0,180');
foreach ($graficoValores as $indice => $valor) {
    $x = ($indice / 6) * 600;
    $y = 180 - (($valor / $maiorGrafico) * 145);
    $pontosGrafico[] = round($x, 2) . ',' . round($y, 2);
    $areaGrafico[] = round($x, 2) . ',' . round($y, 2);
}
$areaGrafico[] = '600,180';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FarmaCerta - Gerente</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
    <style>
        <?php readfile(__DIR__ . '/../style.css'); ?>
    </style>
</head>
<body>
    <?php cabecalho('FarmaCerta - Gerente', 'gerente', 'inicio'); ?>

    <main class="dashboard-gerente">
        <section class="dashboard-kpis">
            <article class="dashboard-kpi">
                <div class="dashboard-kpi-icone">$</div>
                <div>
                    <span>Vendas do Dia</span>
                    <strong>R$ <?php echo number_format($resumoDia['total'], 2, ',', '.'); ?></strong>
                    <small><?php echo (int) $resumoDia['vendas']; ?> venda(s) realizada(s) hoje</small>
                </div>
            </article>

            <article class="dashboard-kpi">
                <div class="dashboard-kpi-icone">↗</div>
                <div>
                    <span>Vendas do Mês</span>
                    <strong>R$ <?php echo number_format($resumoMes['total'], 2, ',', '.'); ?></strong>
                    <small><?php echo (int) $resumoMes['vendas']; ?> venda(s) neste mês</small>
                </div>
            </article>

            <article class="dashboard-kpi">
                <div class="dashboard-kpi-icone">▤</div>
                <div>
                    <span>Recibos do Dia</span>
                    <strong><?php echo (int) $resumoDia['vendas']; ?></strong>
                    <small>Recibos emitidos hoje</small>
                </div>
            </article>

            <article class="dashboard-kpi">
                <div class="dashboard-kpi-icone">🛒</div>
                <div>
                    <span>Média por Venda</span>
                    <strong>R$ <?php echo number_format($resumoDia['media'], 2, ',', '.'); ?></strong>
                    <small>Valor médio por recibo</small>
                </div>
            </article>
        </section>

        <section class="dashboard-graficos">
            <article class="dashboard-box dashboard-faturamento">
                <div class="dashboard-box-titulo">
                    <h2>FATURAMENTO</h2>
                    <span>Últimos 7 dias</span>
                </div>

                <div class="grafico-linha-wrap">
                    <svg class="grafico-linha" viewBox="0 0 600 210" preserveAspectRatio="none" aria-label="Faturamento dos últimos 7 dias">
                        <defs>
                            <linearGradient id="areaVermelha" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#e63946" stop-opacity="0.28" />
                                <stop offset="100%" stop-color="#e63946" stop-opacity="0.02" />
                            </linearGradient>
                        </defs>
                        <line x1="0" y1="35" x2="600" y2="35" class="grade" />
                        <line x1="0" y1="70" x2="600" y2="70" class="grade" />
                        <line x1="0" y1="105" x2="600" y2="105" class="grade" />
                        <line x1="0" y1="140" x2="600" y2="140" class="grade" />
                        <line x1="0" y1="180" x2="600" y2="180" class="grade" />
                        <polygon points="<?php echo implode(' ', $areaGrafico); ?>" fill="url(#areaVermelha)" />
                        <polyline points="<?php echo implode(' ', $pontosGrafico); ?>" class="linha-faturamento" />
                        <?php foreach ($pontosGrafico as $ponto) {
                            list($cx, $cy) = explode(',', $ponto); ?>
                            <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="5" class="ponto-faturamento" />
                        <?php } ?>
                    </svg>
                    <div class="grafico-datas">
                        <?php foreach ($graficoDias as $dia) { ?>
                            <span><?php echo $dia; ?></span>
                        <?php } ?>
                    </div>
                </div>
            </article>

            <article class="dashboard-box dashboard-categorias">
                <div class="dashboard-box-titulo">
                    <h2>VENDAS POR CATEGORIA</h2>
                    <span>Este mês</span>
                </div>

                <div class="categoria-conteudo">
                    <div class="grafico-rosca" style="background: conic-gradient(<?php echo $fundoRosca; ?>);">
                        <div class="grafico-rosca-centro">
                            <small>TOTAL</small>
                            <strong>R$ <?php echo number_format($totalCategorias, 2, ',', '.'); ?></strong>
                        </div>
                    </div>

                    <div class="categoria-legenda">
                        <?php foreach ($categorias as $nome => $valor) {
                            $percentual = $totalCategorias > 0 ? ($valor / $totalCategorias) * 100 : 0; ?>
                            <div>
                                <i style="background: <?php echo $coresCategorias[$nome]; ?>;"></i>
                                <span><?php echo $nome; ?></span>
                                <strong><?php echo number_format($percentual, 1, ',', '.'); ?>%</strong>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-detalhes">
            <article class="dashboard-box">
                <div class="dashboard-box-titulo">
                    <h2>PRODUTOS COM ESTOQUE BAIXO</h2>
                    <a href="produtosgerente.php">Ver todos</a>
                </div>

                <div class="estoque-lista">
                    <?php if (count($estoqueBaixo) == 0) { ?>
                        <div class="dashboard-vazio">Nenhum produto com estoque baixo.</div>
                    <?php } ?>

                    <?php foreach ($estoqueBaixo as $produto) {
                        $limite = max((int) $produto['estoque_minimo'], 1);
                        $porcentagem = min(100, ((int) $produto['quantidade'] / $limite) * 100);
                        $imagem = !empty($produto['imagem']) ? '../' . ltrim($produto['imagem'], '/') : '../assets/LOGO_2.png'; ?>
                        <div class="estoque-item">
                            <img src="<?php echo htmlspecialchars($imagem); ?>" alt="">
                            <div class="estoque-info">
                                <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>
                                <small>Estoque atual: <?php echo (int) $produto['quantidade']; ?></small>
                            </div>
                            <div class="estoque-status">
                                <small>Mínimo: <?php echo (int) $produto['estoque_minimo']; ?></small>
                                <div class="barra"><i style="width: <?php echo number_format($porcentagem, 0); ?>%;"></i></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </article>

            <article class="dashboard-box">
                <div class="dashboard-box-titulo">
                    <h2>ÚLTIMAS VENDAS</h2>
                    <a href="historicorecibogerente.php">Ver todas</a>
                </div>

                <div class="dashboard-tabela-wrap">
                    <table class="dashboard-tabela">
                        <thead>
                            <tr>
                                <th>HORA</th>
                                <th>RECIBO</th>
                                <th>BALCONISTA</th>
                                <th>VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ultimasVendas) == 0) { ?>
                                <tr><td colspan="4" class="dashboard-vazio">Nenhuma venda registrada.</td></tr>
                            <?php } ?>
                            <?php foreach ($ultimasVendas as $venda) { ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($venda['data_venda'])); ?></td>
                                    <td>#<?php echo (int) $venda['id']; ?></td>
                                    <td><?php echo htmlspecialchars($venda['balconista']); ?></td>
                                    <td>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="dashboard-box">
                <div class="dashboard-box-titulo">
                    <h2>FORMAS DE PAGAMENTO</h2>
                    <span>Este mês</span>
                </div>

                <div class="pagamentos-lista">
                    <?php
                    $nomesFormas = array(
                        'dinheiro' => 'Dinheiro',
                        'debito' => 'Cartão de Débito',
                        'credito' => 'Cartão de Crédito',
                        'pix' => 'PIX'
                    );
                    foreach ($formas as $chave => $valor) {
                        $percentual = $totalPagamentos > 0 ? ($valor / $totalPagamentos) * 100 : 0;
                    ?>
                        <div class="pagamento-item pagamento-<?php echo $chave; ?>">
                            <span class="pagamento-icone"><?php echo $chave == 'dinheiro' ? '$' : ($chave == 'pix' ? '◇' : '▣'); ?></span>
                            <span class="pagamento-nome"><?php echo $nomesFormas[$chave]; ?></span>
                            <div class="pagamento-barra"><i style="width: <?php echo number_format($percentual, 0); ?>%;"></i></div>
                            <strong><?php echo number_format($percentual, 1, ',', '.'); ?>%</strong>
                            <span class="pagamento-valor">R$ <?php echo number_format($valor, 2, ',', '.'); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </article>
        </section> 

        <section class="dashboard-dica">
            <span class="dica-icone">☼</span>
            <p>Dica: Acompanhe seus recibos e o estoque para tomar decisões melhores para sua farmácia.</p>
            <a href="historicorecibogerente.php">VER RELATÓRIOS</a>
        </section>
    </main>
</body>
</html>