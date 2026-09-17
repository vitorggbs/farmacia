<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA VALIDAR AS DATAS
|--------------------------------------------------------------------------
| Aceita:
| - YYYY-MM-DD
| - DD/MM/YYYY
|
| O ano possui obrigatoriamente 4 dígitos.
| Exemplo válido: 14/09/2026
| Exemplo inválido: 14/09/123456
|--------------------------------------------------------------------------
*/
function normalizarData($data, $padrao)
{
    $data = trim((string) $data);

    // Formato brasileiro: DD/MM/YYYY
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {

        $dia = (int) $m[1];
        $mes = (int) $m[2];
        $ano = (int) $m[3];

        if ($ano >= 1 && $ano <= 9999 && checkdate($mes, $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        }

        return $padrao;
    }

    // Formato do banco/URL: YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $m)) {

        $ano = (int) $m[1];
        $mes = (int) $m[2];
        $dia = (int) $m[3];

        if ($ano >= 1 && $ano <= 9999 && checkdate($mes, $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        }

        return $padrao;
    }

    return $padrao;
}


/*
|--------------------------------------------------------------------------
| DATAS DO FILTRO
|--------------------------------------------------------------------------
*/

$inicioPadrao = date('Y-m-01');
$fimPadrao = date('Y-m-d');

$inicio = normalizarData(
    $_GET['inicio'] ?? $inicioPadrao,
    $inicioPadrao
);

$fim = normalizarData(
    $_GET['fim'] ?? $fimPadrao,
    $fimPadrao
);


/*
|--------------------------------------------------------------------------
| CONDIÇÃO DAS DATAS
|--------------------------------------------------------------------------
*/

$cond = "AND DATE(v.data_venda) BETWEEN ? AND ?";


/*
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    "SELECT
        COUNT(*) AS vendas,
        COALESCE(SUM(v.valor_total), 0) AS faturamento,
        COALESCE(AVG(v.valor_total), 0) AS ticket
     FROM vendas v
     WHERE v.farmacia_id = ?
       AND v.status = 'concluida'
       $cond"
);

mysqli_stmt_bind_param(
    $stmt,
    'iss',
    $farmaciaId,
    $inicio,
    $fim
);

mysqli_stmt_execute($stmt);

$resumo = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);


/*
|--------------------------------------------------------------------------
| PERÍODO ANTERIOR
|--------------------------------------------------------------------------
*/

$di = new DateTime($inicio);
$df = new DateTime($fim);

$dias = $di->diff($df)->days + 1;

$anteriorFim = (clone $di)
    ->modify('-1 day')
    ->format('Y-m-d');

$anteriorInicio = (clone $di)
    ->modify('-' . $dias . ' day')
    ->format('Y-m-d');


$stmt = mysqli_prepare(
    $conexao,
    "SELECT
        COALESCE(SUM(v.valor_total), 0) AS faturamento
     FROM vendas v
     WHERE v.farmacia_id = ?
       AND v.status = 'concluida'
       AND DATE(v.data_venda) BETWEEN ? AND ?"
);

mysqli_stmt_bind_param(
    $stmt,
    'iss',
    $farmaciaId,
    $anteriorInicio,
    $anteriorFim
);

mysqli_stmt_execute($stmt);

$fatAnterior = (float) mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
)['faturamento'];


$fatAtual = (float) $resumo['faturamento'];

$variacao = $fatAnterior > 0
    ? (($fatAtual - $fatAnterior) / $fatAnterior) * 100
    : null;


/*
|--------------------------------------------------------------------------
| PRODUTOS MAIS VENDIDOS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    "SELECT
        p.nome,
        c.nome AS categoria,
        SUM(i.quantidade) AS quantidade,
        SUM(i.subtotal) AS total
     FROM itens_venda i
     INNER JOIN vendas v ON v.id = i.venda_id
     INNER JOIN produtos p ON p.id = i.produto_id
     LEFT JOIN categorias c ON c.id = p.categoria_id
     WHERE v.farmacia_id = ?
       AND v.status = 'concluida'
       $cond
     GROUP BY p.id, p.nome, c.nome
     ORDER BY quantidade DESC
     LIMIT 20"
);

mysqli_stmt_bind_param(
    $stmt,
    'iss',
    $farmaciaId,
    $inicio,
    $fim
);

mysqli_stmt_execute($stmt);

$produtos = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);


/*
|--------------------------------------------------------------------------
| VENDAS POR BALCONISTA
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    "SELECT
        u.nome,
        COUNT(v.id) AS vendas,
        COALESCE(SUM(v.valor_total), 0) AS total
     FROM vendas v
     INNER JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.farmacia_id = ?
       AND v.status = 'concluida'
       $cond
     GROUP BY u.id, u.nome
     ORDER BY total DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    'iss',
    $farmaciaId,
    $inicio,
    $fim
);

mysqli_stmt_execute($stmt);

$vendedores = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);


/*
|--------------------------------------------------------------------------
| FORMAS DE PAGAMENTO
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    "SELECT
        v.forma_pagamento,
        COUNT(*) AS quantidade,
        SUM(v.valor_total) AS total
     FROM vendas v
     WHERE v.farmacia_id = ?
       AND v.status = 'concluida'
       $cond
     GROUP BY v.forma_pagamento
     ORDER BY total DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    'iss',
    $farmaciaId,
    $inicio,
    $fim
);

mysqli_stmt_execute($stmt);

$pagamentos = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Relatórios'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | CAMPO DE DATA
        |--------------------------------------------------------------------------
        */

        .campo-data {
            background-color: var(--fc-surface, #fff) !important;
            border-radius: 30px !important;
            border: 2px solid rgba(229, 43, 56, 0.35) !important;
            box-shadow: 0 0 0 4px rgba(229, 43, 56, 0.12) !important;
            color: var(--fc-text, #333) !important;
        }

        .campo-data:focus {
            background-color: var(--fc-surface, #fff) !important;
            border-color: #e52b38 !important;
            box-shadow: 0 0 0 4px rgba(229, 43, 56, 0.20) !important;
            outline: none !important;
        }

        [data-bs-theme="dark"] .campo-data {
            background-color: #1e2020 !important;
            color: #f1f5f9 !important;
            border-color: #3d4141 !important;
            box-shadow: none !important;
        }

        [data-bs-theme="dark"] .campo-data:focus {
            background-color: #232525 !important;
            color: #f1f5f9 !important;
            border-color: var(--fc-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(230, 57, 70, 0.35) !important;
        }

        @media print {

            .no-print,
            .navbar,
            .menu-app,
            .offcanvas {
                display: none !important;
            }

            body {
                background: #fff;
            }

        }

    </style>

</head>

<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'relatorios'); ?>

<main class="container py-4">


    <!-- ==========================================
         FILTRO DE RELATÓRIO
    =========================================== -->

    <section class="card card-brand rounded-4 p-4 mb-4 no-print">

        <h2 class="h3 fw-bold">
            RELATÓRIOS
        </h2>

        <form
            class="row g-3 align-items-end"
            id="formRelatorio"
            method="GET"
            action=""
        >

            <!-- DATA INICIAL -->

            <div class="col-md-4">

                <label
                    class="form-label"
                    for="inicio"
                >
                    Data inicial
                </label>

                <input
                    class="form-control campo-data"
                    type="text"
                    id="inicio"
                    name="inicio"
                    value=""
                    placeholder="DD/MM/AAAA"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="10"
                    minlength="10"
                    pattern="\d{2}/\d{2}/\d{4}"
                    oninput="mascaraData(this)"
                    required
                >

            </div>


            <!-- DATA FINAL -->

            <div class="col-md-4">

                <label
                    class="form-label"
                    for="fim"
                >
                    Data final
                </label>

                <input
                    class="form-control campo-data"
                    type="text"
                    id="fim"
                    name="fim"
                    value=""
                    placeholder="DD/MM/AAAA"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="10"
                    minlength="10"
                    pattern="\d{2}/\d{2}/\d{4}"
                    oninput="mascaraData(this)"
                    required
                >

            </div>


            <!-- BOTÕES -->

            <div class="col-md-4 d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-outline-light rounded-pill"
                >
                    FILTRAR
                </button>

                <a
                    class="btn btn-outline-light rounded-pill"
                    href="exportarrelatorio.php?inicio=<?php echo urlencode($inicio); ?>&fim=<?php echo urlencode($fim); ?>"
                >
                    CSV
                </a>

                <button
                    type="button"
                    class="btn btn-outline-light rounded-pill"
                    onclick="window.print()"
                >
                    IMPRIMIR
                </button>

            </div>

        </form>

    </section>


    <!-- ==========================================
         RESUMO
    =========================================== -->

    <section class="row g-3 mb-4">

        <div class="col-md-4">

            <article class="card p-4">

                <small>
                    FATURAMENTO
                </small>

                <strong class="fs-3 text-primary">
                    R$
                    <?php
                    echo number_format(
                        $resumo['faturamento'],
                        2,
                        ',',
                        '.'
                    );
                    ?>
                </strong>

                <small class="text-secondary mt-2">

                    Período anterior: R$
                    <?php
                    echo number_format(
                        $fatAnterior,
                        2,
                        ',',
                        '.'
                    );
                    ?>

                    <?php if ($variacao !== null) { ?>

                        ·

                        <?php echo $variacao >= 0 ? '+' : ''; ?>

                        <?php
                        echo number_format(
                            $variacao,
                            1,
                            ',',
                            '.'
                        );
                        ?>%

                    <?php } ?>

                </small>

            </article>

        </div>


        <div class="col-md-4">

            <article class="card p-4">

                <small>
                    VENDAS
                </small>

                <strong class="fs-3">
                    <?php echo (int) $resumo['vendas']; ?>
                </strong>

            </article>

        </div>


        <div class="col-md-4">

            <article class="card p-4">

                <small>
                    TICKET MÉDIO
                </small>

                <strong class="fs-3">

                    R$

                    <?php
                    echo number_format(
                        $resumo['ticket'],
                        2,
                        ',',
                        '.'
                    );
                    ?>

                </strong>

            </article>

        </div>

    </section>


    <!-- ==========================================
         TABELAS
    =========================================== -->

    <section class="row g-3">


        <!-- PRODUTOS MAIS VENDIDOS -->

        <div class="col-xl-6">

            <article class="card p-3 h-100">

                <h3 class="h5 fw-bold">
                    PRODUTOS MAIS VENDIDOS
                </h3>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Produto
                                </th>

                                <th>
                                    Categoria
                                </th>

                                <th>
                                    Qtd.
                                </th>

                                <th>
                                    Total
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($produtos as $p) { ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $p['nome']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $p['categoria'] ?: 'Sem categoria'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo (int) $p['quantidade'];
                                    ?>
                                </td>

                                <td>

                                    R$

                                    <?php
                                    echo number_format(
                                        $p['total'],
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

            </article>

        </div>


        <!-- VENDAS POR BALCONISTA -->

        <div class="col-xl-6">

            <article class="card p-3 h-100">

                <h3 class="h5 fw-bold">
                    VENDAS POR BALCONISTA
                </h3>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Balconista
                                </th>

                                <th>
                                    Vendas
                                </th>

                                <th>
                                    Total
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($vendedores as $v) { ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $v['nome']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo (int) $v['vendas'];
                                    ?>
                                </td>

                                <td>

                                    R$

                                    <?php
                                    echo number_format(
                                        $v['total'],
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

            </article>

        </div>


        <!-- FORMAS DE PAGAMENTO -->

        <div class="col-12">

            <article class="card p-3">

                <h3 class="h5 fw-bold">
                    FORMAS DE PAGAMENTO
                </h3>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Forma
                                </th>

                                <th>
                                    Vendas
                                </th>

                                <th>
                                    Total
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($pagamentos as $p) { ?>

                            <tr>

                                <td>
                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $p['forma_pagamento']
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo (int) $p['quantidade'];
                                    ?>
                                </td>

                                <td>

                                    R$

                                    <?php
                                    echo number_format(
                                        $p['total'],
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

            </article>

        </div>

    </section>

</main>

<?php recursosRodape(); ?>


<script>

/*
|--------------------------------------------------------------------------
| MÁSCARA DE DATA
|--------------------------------------------------------------------------
| Formato:
| DD/MM/AAAA
|
| Limites:
| Dia   = 01 até 31
| Mês   = 01 até 12
| Ano   = exatamente 4 dígitos
|
| Exemplo:
| 14/09/2026
|
| Não permite:
| 14/09/123456
|--------------------------------------------------------------------------
*/

function mascaraData(campo) {

    var numeros = campo.value.replace(/\D/g, '');

    /*
     * Limita a exatamente 8 números:
     *
     * DD = 2
     * MM = 2
     * AAAA = 4
     *
     * Total = 8
     */
    numeros = numeros.substring(0, 8);


    /*
     * DIA
     */
    if (numeros.length >= 2) {

        var dia = parseInt(
            numeros.substring(0, 2),
            10
        );

        if (dia > 31) {
            dia = 31;
        }

        if (dia < 1) {
            dia = 1;
        }

        numeros =
            String(dia).padStart(2, '0') +
            numeros.substring(2);

    }


    /*
     * MÊS
     */
    if (numeros.length >= 4) {

        var mes = parseInt(
            numeros.substring(2, 4),
            10
        );

        if (mes > 12) {
            mes = 12;
        }

        if (mes < 1) {
            mes = 1;
        }

        numeros =
            numeros.substring(0, 2) +
            String(mes).padStart(2, '0') +
            numeros.substring(4);

    }


    /*
     * MONTA DD/MM/AAAA
     */
    if (numeros.length <= 2) {

        campo.value = numeros;

    } else if (numeros.length <= 4) {

        campo.value =
            numeros.substring(0, 2) +
            '/' +
            numeros.substring(2);

    } else {

        campo.value =
            numeros.substring(0, 2) +
            '/' +
            numeros.substring(2, 4) +
            '/' +
            numeros.substring(4, 8);

    }

}


/*
|--------------------------------------------------------------------------
| VALIDAÇÃO ANTES DE ENVIAR
|--------------------------------------------------------------------------
| Garante:
| - DD/MM/AAAA
| - Ano com exatamente 4 números
| - Ano entre 0001 e 9999
| - Dia entre 01 e 31
| - Mês entre 01 e 12
| - Data realmente existente
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    var formulario = document.getElementById(
        'formRelatorio'
    );

    if (!formulario) {
        return;
    }


    formulario.addEventListener('submit', function (event) {

        var campos = [
            document.getElementById('inicio'),
            document.getElementById('fim')
        ];


        for (var i = 0; i < campos.length; i++) {

            var campo = campos[i];

            var valor = campo.value.trim();

            /*
             * Precisa estar exatamente em DD/MM/AAAA.
             */
            var partes = valor.match(
                /^(\d{2})\/(\d{2})\/(\d{4})$/
            );

            if (!partes) {

                event.preventDefault();

                alert(
                    'Digite a data no formato DD/MM/AAAA.\nExemplo: 14/09/2026'
                );

                campo.focus();

                return;

            }


            var dia = parseInt(
                partes[1],
                10
            );

            var mes = parseInt(
                partes[2],
                10
            );

            var ano = parseInt(
                partes[3],
                10
            );


            /*
             * Ano:
             * exatamente 4 dígitos.
             */
            if (
                partes[3].length !== 4 ||
                ano < 1 ||
                ano > 9999
            ) {

                event.preventDefault();

                alert(
                    'O ano deve possuir apenas 4 dígitos, de 0001 até 9999.'
                );

                campo.focus();

                return;

            }


            /*
             * Dia.
             */
            if (dia < 1 || dia > 31) {

                event.preventDefault();

                alert(
                    'O dia deve estar entre 01 e 31.'
                );

                campo.focus();

                return;

            }


            /*
             * Mês.
             */
            if (mes < 1 || mes > 12) {

                event.preventDefault();

                alert(
                    'O mês deve estar entre 01 e 12.'
                );

                campo.focus();

                return;

            }


            /*
             * Verifica se a data existe.
             */
            var data = new Date(
                ano,
                mes - 1,
                dia
            );

            if (
                data.getFullYear() !== ano ||
                data.getMonth() !== mes - 1 ||
                data.getDate() !== dia
            ) {

                event.preventDefault();

                alert(
                    'Digite uma data válida.'
                );

                campo.focus();

                return;

            }

        }


        /*
         * Converte:
         *
         * DD/MM/AAAA
         *
         * para:
         *
         * AAAA-MM-DD
         *
         * antes de enviar para o PHP.
         */
        campos.forEach(function (campo) {

            var partes = campo.value.match(
                /^(\d{2})\/(\d{2})\/(\d{4})$/
            );

            if (partes) {

                var dia = partes[1];
                var mes = partes[2];
                var ano = partes[3];

                campo.value =
                    ano +
                    '-' +
                    mes +
                    '-' +
                    dia;

            }

        });

    });

});

</script>


</body>
</html>

<?php

mysqli_close($conexao);

?>
