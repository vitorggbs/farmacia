<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();


/*
|--------------------------------------------------------------------------
| Resumo das farmácias
|--------------------------------------------------------------------------
*/

$dados = mysqli_fetch_assoc(
    mysqli_query(
        $conexao,
        "SELECT
            COUNT(*) AS total,
            SUM(ativo = 1) AS ativas,
            SUM(ativo = 0) AS inativas
         FROM farmacias"
    )
);


/*
|--------------------------------------------------------------------------
| Resumo dos usuários
|--------------------------------------------------------------------------
*/

$usuarios = mysqli_fetch_assoc(
    mysqli_query(
        $conexao,
        "SELECT
            COUNT(*) AS total,
            SUM(cargo = 'gerente' AND ativo = 1) AS gerentes,
            SUM(cargo = 'balconista' AND ativo = 1) AS balconistas
         FROM usuarios"
    )
);


/*
|--------------------------------------------------------------------------
| Faturamento geral
|--------------------------------------------------------------------------
*/

$resultadoVendas = mysqli_query(
    $conexao,
    "SELECT
        COALESCE(SUM(valor_total), 0) AS total
     FROM vendas
     WHERE status = 'concluida'"
);

$totalVendas = mysqli_fetch_assoc($resultadoVendas)['total'];


/*
|--------------------------------------------------------------------------
| Ranking de faturamento por farmácia
|--------------------------------------------------------------------------
*/

$ranking = mysqli_query(
    $conexao,
    "SELECT
        f.nome,
        COUNT(v.id) AS vendas,
        COALESCE(
            SUM(
                CASE
                    WHEN v.status = 'concluida'
                    THEN v.valor_total
                    ELSE 0
                END
            ),
            0
        ) AS faturamento
     FROM farmacias f
     LEFT JOIN vendas v
        ON v.farmacia_id = f.id
     GROUP BY f.id, f.nome
     ORDER BY faturamento DESC"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Administrador'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | Botões - Vermelho
        |--------------------------------------------------------------------------
        */

        .btn-admin-vermelho {
            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-admin-vermelho:hover {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
        }

        .btn-admin-vermelho:focus,
        .btn-admin-vermelho:active {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Linha de seleção da tabela - Vermelha
        |--------------------------------------------------------------------------
        */

        .table-hover > tbody > tr:hover {
            --bs-table-hover-bg: rgba(229, 43, 56, 0.10) !important;
            --bs-table-hover-color: inherit !important;
            background-color: rgba(229, 43, 56, 0.10) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Linha selecionada/focada - Vermelha
        |--------------------------------------------------------------------------
        */

        .table > tbody > tr:active,
        .table > tbody > tr:focus {
            background-color: rgba(229, 43, 56, 0.15) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Elementos com foco - Vermelho
        |--------------------------------------------------------------------------
        */

        .form-control:focus,
        .form-select:focus {
            border-color: #e52b38 !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.20) !important;
        }

    </style>

</head>

<body>

<?php cabecalhoAdmin('FarmaCerta - Administrador'); ?>


<main class="container py-4">


    <!-- =========================================================
         VISÃO GERAL
    ========================================================== -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            VISÃO GERAL DO SISTEMA
        </h2>


        <div class="row g-3">


            <!-- FARMÁCIAS -->

            <div class="col-12 col-sm-6 col-md-3">

                <article class="card p-3 text-center h-100">

                    <strong class="fs-3 text-primary">
                        <?php echo (int) $dados['total']; ?>
                    </strong>

                    <span>
                        Farmácias
                    </span>

                </article>

            </div>


            <!-- FARMÁCIAS ATIVAS -->

            <div class="col-12 col-sm-6 col-md-3">

                <article class="card p-3 text-center h-100">

                    <strong class="fs-3 text-primary">
                        <?php echo (int) $dados['ativas']; ?>
                    </strong>

                    <span>
                        Ativas
                    </span>

                </article>

            </div>


            <!-- USUÁRIOS -->

            <div class="col-12 col-sm-6 col-md-3">

                <article class="card p-3 text-center h-100">

                    <strong class="fs-3 text-primary">
                        <?php echo (int) $usuarios['total']; ?>
                    </strong>

                    <span>
                        Usuários
                    </span>

                </article>

            </div>


            <!-- FATURAMENTO -->

            <div class="col-12 col-sm-6 col-md-3">

                <article class="card p-3 text-center h-100">

                    <strong class="fs-5 text-primary">
                        R$
                        <?php
                        echo number_format(
                            $totalVendas,
                            2,
                            ',',
                            '.'
                        );
                        ?>
                    </strong>

                    <span>
                        Faturamento geral
                    </span>

                </article>

            </div>

        </div>

    </section>


    <!-- =========================================================
         INDICADORES
    ========================================================== -->

    <section class="row g-3 mb-4">


        <!-- GERENTES -->

        <div class="col-12 col-md-4">

            <article class="card p-4 h-100">

                <h3 class="h5">
                    Gerentes ativos
                </h3>

                <strong class="fs-2">
                    <?php echo (int) $usuarios['gerentes']; ?>
                </strong>

            </article>

        </div>


        <!-- BALCONISTAS -->

        <div class="col-12 col-md-4">

            <article class="card p-4 h-100">

                <h3 class="h5">
                    Balconistas ativos
                </h3>

                <strong class="fs-2">
                    <?php echo (int) $usuarios['balconistas']; ?>
                </strong>

            </article>

        </div>


        <!-- FARMÁCIAS INATIVAS -->

        <div class="col-12 col-md-4">

            <article class="card p-4 h-100">

                <h3 class="h5">
                    Farmácias inativas
                </h3>

                <strong class="fs-2">
                    <?php echo (int) $dados['inativas']; ?>
                </strong>

            </article>

        </div>

    </section>


    <!-- =========================================================
         FATURAMENTO POR FARMÁCIA
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-3">


        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">

            <h3 class="h5 fw-bold mb-0">
                FATURAMENTO POR FARMÁCIA
            </h3>


            <a
                class="btn btn-admin-vermelho rounded-pill px-4"
                href="backup.php"
            >
                BACKUP DO BANCO
            </a>

        </div>


        <!-- TABELA -->

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>
                            Farmácia
                        </th>

                        <th>
                            Vendas
                        </th>

                        <th>
                            Faturamento
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while ($r = mysqli_fetch_assoc($ranking)) { ?>

                    <tr>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $r['nome']
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo (int) $r['vendas'];
                            ?>

                        </td>


                        <td>

                            R$
                            <?php
                            echo number_format(
                                $r['faturamento'],
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

    </section>

</main>


<?php recursosRodape(); ?>

</body>
</html>
