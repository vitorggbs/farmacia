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
            WHERE farmacia_id = ? AND status = 'concluida' AND $condicao";

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

$resumoDia = buscarResumo(
    $conexao,
    $farmaciaId,
    'DATE(data_venda) = CURDATE()'
);

$resumoMes = buscarResumo(
    $conexao,
    $farmaciaId,
    'YEAR(data_venda) = YEAR(CURDATE()) AND MONTH(data_venda) = MONTH(CURDATE())'
);

/*
|--------------------------------------------------------------------------
| FATURAMENTO DOS ÚLTIMOS 7 DIAS
|--------------------------------------------------------------------------
*/

$sql = 'SELECT DATE(data_venda) AS dia,
               COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE farmacia_id = ?
        AND status = \'concluida\'
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

    $graficoValores[] = isset($faturamentoPorDia[$data])
        ? $faturamentoPorDia[$data]
        : 0;
}

/*
|--------------------------------------------------------------------------
| PRODUTOS COM ESTOQUE BAIXO
|--------------------------------------------------------------------------
*/

$sql = 'SELECT id, nome, quantidade, estoque_minimo, imagem
        FROM produtos
        WHERE farmacia_id = ?
        AND ativo = 1
        AND quantidade <= estoque_minimo
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

/*
|--------------------------------------------------------------------------
| ÚLTIMAS VENDAS
|--------------------------------------------------------------------------
*/

$sql = 'SELECT v.id,
               v.data_venda,
               v.forma_pagamento,
               v.valor_total,
               COALESCE(NULLIF(v.cliente, ""), "Cliente Final") AS cliente,
               u.nome AS balconista
        FROM vendas v
        INNER JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.farmacia_id = ?
        AND v.status = \'concluida\'
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

/*
|--------------------------------------------------------------------------
| FORMAS DE PAGAMENTO
|--------------------------------------------------------------------------
*/

$formas = array(
    'dinheiro' => 0,
    'debito' => 0,
    'credito' => 0,
    'pix' => 0
);

$sql = 'SELECT forma_pagamento,
               COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE farmacia_id = ?
        AND status = \'concluida\'
        AND YEAR(data_venda) = YEAR(CURDATE())
        AND MONTH(data_venda) = MONTH(CURDATE())
        GROUP BY forma_pagamento';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);

$resultadoFormas = mysqli_stmt_get_result($stmt);

while ($forma = mysqli_fetch_assoc($resultadoFormas)) {

    if (isset($formas[$forma['forma_pagamento']])) {
        $formas[$forma['forma_pagamento']] = (float) $forma['total'];
    }
}

mysqli_stmt_close($stmt);

$totalPagamentos = array_sum($formas);

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$categorias = array(
    'Medicamentos' => 0
);

$sql = 'SELECT p.nome,
               SUM(iv.subtotal) AS total
        FROM itens_venda iv
        INNER JOIN produtos p ON p.id = iv.produto_id
        INNER JOIN vendas v ON v.id = iv.venda_id
        WHERE v.farmacia_id = ?
        AND v.status = \'concluida\'
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
        $categoria = 'Medicamentos';
    }

    $categorias[$categoria] += (float) $item['total'];
}

mysqli_stmt_close($stmt);

$totalCategorias = array_sum($categorias);

$coresCategorias = array(
    'Medicamentos' => '#ef233c'
);

/*
|--------------------------------------------------------------------------
| GRÁFICO DE ROSCA
|--------------------------------------------------------------------------
*/

$angulos = array();
$acumulado = 0;

foreach ($categorias as $nome => $valor) {

    $percentual = $totalCategorias > 0
        ? ($valor / $totalCategorias) * 100
        : 0;

    $inicio = $acumulado;
    $fim = $acumulado + $percentual;

    $angulos[] =
        $coresCategorias[$nome] . ' ' .
        number_format($inicio, 2, '.', '') . '% ' .
        number_format($fim, 2, '.', '') . '%';

    $acumulado = $fim;
}

if ($totalCategorias == 0) {

    $fundoRosca = '#e5e7eb 0% 100%';

} else {

    $fundoRosca = implode(', ', $angulos);
}

/*
|--------------------------------------------------------------------------
| GRÁFICO DE LINHA
|--------------------------------------------------------------------------
*/

$maiorGrafico = max($graficoValores);

if ($maiorGrafico <= 0) {
    $maiorGrafico = 1;
}

$pontosGrafico = array();
$areaGrafico = array('0,180');

foreach ($graficoValores as $indice => $valor) {

    $x = ($indice / 6) * 600;

    $y = 180 - (($valor / $maiorGrafico) * 145);

    $pontosGrafico[] =
        round($x, 2) . ',' . round($y, 2);

    $areaGrafico[] =
        round($x, 2) . ',' . round($y, 2);
}

$areaGrafico[] = '600,180';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('FarmaCerta - Gerente'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PADRÃO VERMELHO DOS BOTÕES
        |--------------------------------------------------------------------------
        */

        .btn-gerente-vermelho {
            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-gerente-vermelho:hover {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
        }

        .btn-gerente-vermelho:focus,
        .btn-gerente-vermelho:active {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | BOTÕES OUTLINE VERMELHOS
        |--------------------------------------------------------------------------
        */

        .btn-outline-gerente-vermelho {
            color: #e52b38 !important;
            border-color: #e52b38 !important;
            background-color: transparent !important;
            transition: all 0.2s ease;
        }

        .btn-outline-gerente-vermelho:hover,
        .btn-outline-gerente-vermelho:focus,
        .btn-outline-gerente-vermelho:active {
            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | LINHA DE SELEÇÃO / HOVER DA TABELA
        |--------------------------------------------------------------------------
        */

        .table-hover > tbody > tr:hover {
            --bs-table-hover-bg: rgba(229, 43, 56, 0.10) !important;
            --bs-table-hover-color: inherit !important;
            background-color: rgba(229, 43, 56, 0.10) !important;
        }

        .table-hover > tbody > tr:hover > * {
            background-color: rgba(229, 43, 56, 0.10) !important;
            color: inherit;
            box-shadow: none !important;
        }


        /*
        |--------------------------------------------------------------------------
        | SELEÇÃO DE TEXTO
        |--------------------------------------------------------------------------
        */

        ::selection {
            background-color: #e52b38;
            color: #ffffff;
        }

        ::-moz-selection {
            background-color: #e52b38;
            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | GRÁFICO DE FATURAMENTO
        |--------------------------------------------------------------------------
        */

        .linha-faturamento {
            fill: none;
            stroke: #e52b38;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .ponto-faturamento {
            fill: #ffffff;
            stroke: #e52b38;
            stroke-width: 3;
        }

        .grade {
            stroke: #e5e7eb;
            stroke-width: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | BARRAS DE PROGRESSO
        |--------------------------------------------------------------------------
        */

        .progress-bar {
            background-color: #e52b38 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULOS
        |--------------------------------------------------------------------------
        */

        .text-primary {
            color: #e52b38 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | ÍCONES DOS INDICADORES
        |--------------------------------------------------------------------------
        */

        .kpi-icone {
            background-color: rgba(229, 43, 56, 0.10);
            color: #e52b38;
            min-width: 44px;
            width: 44px;
            height: 44px;
            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | DICA DE ESTOQUE
        |--------------------------------------------------------------------------
        */

        .dica-estoque {
            background-color: rgba(229, 43, 56, 0.08);
        }

    </style>

</head>

<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'inicio'); ?>

<main class="container-xl py-4">

    <!-- =========================================================
         INDICADORES
    ========================================================== -->

    <section class="row g-3">

        <article class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100 shadow-sm p-3 d-flex flex-row gap-3">

                <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center">
                    $
                </div>

                <div>

                    <span class="d-block small">
                        Vendas do Dia
                    </span>

                    <strong class="kpi-valor fs-5">
                        R$ <?php echo number_format($resumoDia['total'], 2, ',', '.'); ?>
                    </strong>

                    <small class="d-block text-secondary">
                        <?php echo (int) $resumoDia['vendas']; ?> venda(s) realizada(s) hoje
                    </small>

                </div>

            </div>

        </article>


        <article class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100 shadow-sm p-3 d-flex flex-row gap-3">

                <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center">
                    ↗
                </div>

                <div>

                    <span class="d-block small">
                        Vendas do Mês
                    </span>

                    <strong class="kpi-valor fs-5">
                        R$ <?php echo number_format($resumoMes['total'], 2, ',', '.'); ?>
                    </strong>

                    <small class="d-block text-secondary">
                        <?php echo (int) $resumoMes['vendas']; ?> venda(s) neste mês
                    </small>

                </div>

            </div>

        </article>


        <article class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100 shadow-sm p-3 d-flex flex-row gap-3">

                <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center">
                    ▤
                </div>

                <div>

                    <span class="d-block small">
                        Recibos do Dia
                    </span>

                    <strong class="kpi-valor fs-5">
                        <?php echo (int) $resumoDia['vendas']; ?>
                    </strong>

                    <small class="d-block text-secondary">
                        Recibos emitidos hoje
                    </small>

                </div>

            </div>

        </article>


        <article class="col-12 col-sm-6 col-xl-3">

            <div class="card h-100 shadow-sm p-3 d-flex flex-row gap-3">

                <div class="kpi-icone rounded-circle d-flex align-items-center justify-content-center">
                    🛒
                </div>

                <div>

                    <span class="d-block small">
                        Média por Venda
                    </span>

                    <strong class="kpi-valor fs-5">
                        R$ <?php echo number_format($resumoDia['media'], 2, ',', '.'); ?>
                    </strong>

                    <small class="d-block text-secondary">
                        Valor médio por recibo
                    </small>

                </div>

            </div>

        </article>

    </section>


    <!-- =========================================================
         GRÁFICOS
    ========================================================== -->

    <section class="row g-3 mt-1">

        <article class="col-12 col-xl-7">

            <div class="card h-100 shadow-sm p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h2 class="h6 fw-bold text-primary mb-0">
                        FATURAMENTO
                    </h2>

                    <span class="badge text-bg-light">
                        Últimos 7 dias
                    </span>

                </div>


                <div class="grafico-linha-wrap">

                    <svg
                        class="grafico-linha"
                        viewBox="0 0 600 210"
                        preserveAspectRatio="none"
                        aria-label="Faturamento dos últimos 7 dias"
                    >

                        <defs>

                            <linearGradient
                                id="areaVermelha"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >

                                <stop
                                    offset="0%"
                                    stop-color="#e63946"
                                    stop-opacity="0.28"
                                />

                                <stop
                                    offset="100%"
                                    stop-color="#e63946"
                                    stop-opacity="0.02"
                                />

                            </linearGradient>

                        </defs>


                        <line x1="0" y1="35" x2="600" y2="35" class="grade" />
                        <line x1="0" y1="70" x2="600" y2="70" class="grade" />
                        <line x1="0" y1="105" x2="600" y2="105" class="grade" />
                        <line x1="0" y1="140" x2="600" y2="140" class="grade" />
                        <line x1="0" y1="180" x2="600" y2="180" class="grade" />


                        <polygon
                            points="<?php echo implode(' ', $areaGrafico); ?>"
                            fill="url(#areaVermelha)"
                        />


                        <polyline
                            points="<?php echo implode(' ', $pontosGrafico); ?>"
                            class="linha-faturamento"
                        />


                        <?php foreach ($pontosGrafico as $ponto) {

                            list($cx, $cy) = explode(',', $ponto);

                        ?>

                            <circle
                                cx="<?php echo $cx; ?>"
                                cy="<?php echo $cy; ?>"
                                r="5"
                                class="ponto-faturamento"
                            />

                        <?php } ?>

                    </svg>


                    <div
                        class="d-grid gap-1 text-secondary small text-center"
                        style="grid-template-columns: repeat(7, 1fr);"
                    >

                        <?php foreach ($graficoDias as $dia) { ?>

                            <span>
                                <?php echo $dia; ?>
                            </span>

                        <?php } ?>

                    </div>

                </div>

            </div>

        </article>


        <article class="col-12 col-xl-5">

            <div class="card h-100 shadow-sm p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h2 class="h6 fw-bold text-primary mb-0">
                        VENDAS POR CATEGORIA
                    </h2>

                    <span class="badge text-bg-light">
                        Este mês
                    </span>

                </div>


                <div class="d-flex flex-column flex-sm-row align-items-center gap-4">

                    <div
                        class="grafico-rosca"
                        style="background: conic-gradient(<?php echo $fundoRosca; ?>);"
                    >

                        <div class="grafico-rosca-centro bg-white d-flex flex-column align-items-center justify-content-center text-center">

                            <small>
                                TOTAL
                            </small>

                            <strong>
                                R$ <?php echo number_format($totalCategorias, 2, ',', '.'); ?>
                            </strong>

                        </div>

                    </div>


                    <div class="flex-grow-1">

                        <?php foreach ($categorias as $nome => $valor) {

                            $percentual = $totalCategorias > 0
                                ? ($valor / $totalCategorias) * 100
                                : 0;

                        ?>

                            <div class="d-flex align-items-center gap-2 py-1 small">

                                <span
                                    class="rounded-circle d-inline-block"
                                    style="
                                        width:11px;
                                        height:11px;
                                        background:<?php echo $coresCategorias[$nome]; ?>;
                                    "
                                ></span>

                                <span class="flex-grow-1">
                                    <?php echo $nome; ?>
                                </span>

                                <strong>
                                    <?php echo number_format($percentual, 1, ',', '.'); ?>%
                                </strong>

                            </div>

                        <?php } ?>

                    </div>

                </div>

            </div>

        </article>

    </section>


    <!-- =========================================================
         ESTOQUE / VENDAS / PAGAMENTOS
    ========================================================== -->

    <section class="row g-3 mt-1">

        <!-- ESTOQUE -->

        <article class="col-12 col-lg-4">

            <div class="card h-100 shadow-sm p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h2 class="h6 fw-bold text-primary mb-0">
                        PRODUTOS COM ESTOQUE BAIXO
                    </h2>

                    <a
                        class="btn btn-sm btn-outline-gerente-vermelho rounded-pill"
                        href="produtosgerente.php"
                    >
                        Ver todos
                    </a>

                </div>


                <?php if (count($estoqueBaixo) == 0) { ?>

                    <p class="text-secondary small text-center mb-0">
                        Nenhum produto com estoque baixo.
                    </p>

                <?php } ?>


                <?php foreach ($estoqueBaixo as $produto) {

                    $limite = max(
                        (int) $produto['estoque_minimo'],
                        1
                    );

                    $porcentagem = min(
                        100,
                        ((int) $produto['quantidade'] / $limite) * 100
                    );

                    $imagem = !empty($produto['imagem'])
                        ? 'uploads/' . ltrim($produto['imagem'], '/')
                        : '../assets/LOGO_2.png';

                ?>

                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">

                        <img
                            class="rounded border"
                            src="<?php echo htmlspecialchars($imagem); ?>"
                            alt=""
                            width="36"
                            height="36"
                        >

                        <div class="flex-grow-1 min-w-0">

                            <strong class="d-block text-truncate small">
                                <?php echo htmlspecialchars($produto['nome']); ?>
                            </strong>

                            <small class="text-secondary">
                                Estoque atual:
                                <?php echo (int) $produto['quantidade']; ?>
                            </small>

                            <div
                                class="progress mt-1"
                                style="height:6px;"
                            >

                                <div
                                    class="progress-bar"
                                    style="width: <?php echo number_format($porcentagem, 0); ?>%;"
                                ></div>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </article>


        <!-- ÚLTIMAS VENDAS -->

        <article class="col-12 col-lg-4">

            <div class="card h-100 shadow-sm p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h2 class="h6 fw-bold text-primary mb-0">
                        ÚLTIMAS VENDAS
                    </h2>

                    <a
                        class="btn btn-sm btn-outline-gerente-vermelho rounded-pill"
                        href="historicorecibogerente.php"
                    >
                        Ver todas
                    </a>

                </div>


                <div class="table-responsive">

                    <table class="table table-sm table-hover align-middle mb-0">

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

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-secondary text-center"
                                    >
                                        Nenhuma venda registrada.
                                    </td>

                                </tr>

                            <?php } ?>


                            <?php foreach ($ultimasVendas as $venda) { ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo date(
                                            'H:i',
                                            strtotime($venda['data_venda'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        #<?php echo (int) $venda['id']; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($venda['balconista']); ?>
                                    </td>

                                    <td>
                                        R$
                                        <?php
                                        echo number_format(
                                            $venda['valor_total'],
                                            2,
                                            ',',
                                            '.'
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </article>


        <!-- FORMAS DE PAGAMENTO -->

        <article class="col-12 col-lg-4">

            <div class="card h-100 shadow-sm p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h2 class="h6 fw-bold text-primary mb-0">
                        FORMAS DE PAGAMENTO
                    </h2>

                    <span class="badge text-bg-light">
                        Este mês
                    </span>

                </div>


                <?php

                $nomesFormas = array(
                    'dinheiro' => 'Dinheiro',
                    'debito' => 'Cartão de Débito',
                    'credito' => 'Cartão de Crédito',
                    'pix' => 'PIX'
                );

                foreach ($formas as $chave => $valor) {

                    $percentual = $totalPagamentos > 0
                        ? ($valor / $totalPagamentos) * 100
                        : 0;

                ?>

                    <div class="pagamento-<?php echo $chave; ?> mb-3">

                        <div class="d-flex justify-content-between small">

                            <span>
                                <?php echo $nomesFormas[$chave]; ?>
                            </span>

                            <strong>
                                <?php echo number_format($percentual, 1, ',', '.'); ?>%
                            </strong>

                        </div>


                        <div
                            class="progress my-1"
                            style="height:6px;"
                        >

                            <div
                                class="progress-bar"
                                style="width: <?php echo number_format($percentual, 0); ?>%;"
                            ></div>

                        </div>


                        <div class="text-end small">

                            R$
                            <?php
                            echo number_format(
                                $valor,
                                2,
                                ',',
                                '.'
                            );
                            ?>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </article>

    </section>


    <!-- =========================================================
         DICA
    ========================================================== -->

    <section class="dica-estoque rounded-4 p-3 mt-3 d-flex flex-column flex-md-row align-items-md-center gap-3">

        <span
            class="text-primary fs-3"
            aria-hidden="true"
        >
            ☼
        </span>

        <p class="mb-0 flex-grow-1 small">
            Dica: Acompanhe seus recibos e o estoque para tomar decisões melhores para sua farmácia.
        </p>

        <a
            class="btn btn-gerente-vermelho rounded-pill"
            href="historicorecibogerente.php"
        >
            VER RELATÓRIOS
        </a>

    </section>

</main>

<?php recursosRodape(); ?>

</body>
</html>
