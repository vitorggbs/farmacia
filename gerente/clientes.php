<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];

/*
|--------------------------------------------------------------------------
| Busca de clientes
|--------------------------------------------------------------------------
*/

$busca = trim($_GET['busca'] ?? '');

if ($busca !== '') {

    $like = '%' . $busca . '%';

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT
            c.*,
            COUNT(v.id) AS compras,
            COALESCE(
                SUM(
                    CASE
                        WHEN v.status = 'concluida'
                        THEN v.valor_total
                        ELSE 0
                    END
                ),
                0
            ) AS total
        FROM clientes c
        LEFT JOIN vendas v
            ON v.farmacia_id = c.farmacia_id
            AND (
                (
                    c.cpf IS NOT NULL
                    AND c.cpf <> ''
                    AND v.cpf_cliente = c.cpf
                )
                OR v.cliente = c.nome
            )
        WHERE c.farmacia_id = ?
        AND (
            c.nome LIKE ?
            OR c.cpf LIKE ?
        )
        GROUP BY c.id
        ORDER BY c.nome"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'iss',
        $farmaciaId,
        $like,
        $like
    );

} else {

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT
            c.*,
            COUNT(v.id) AS compras,
            COALESCE(
                SUM(
                    CASE
                        WHEN v.status = 'concluida'
                        THEN v.valor_total
                        ELSE 0
                    END
                ),
                0
            ) AS total
        FROM clientes c
        LEFT JOIN vendas v
            ON v.farmacia_id = c.farmacia_id
            AND (
                (
                    c.cpf IS NOT NULL
                    AND c.cpf <> ''
                    AND v.cpf_cliente = c.cpf
                )
                OR v.cliente = c.nome
            )
        WHERE c.farmacia_id = ?
        GROUP BY c.id
        ORDER BY c.nome"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $farmaciaId
    );
}

mysqli_stmt_execute($stmt);

$clientes = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);

mysqli_stmt_close($stmt);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Clientes'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | Botão Buscar - Vermelho
        |--------------------------------------------------------------------------
        */

        .btn-buscar-cliente {
            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-buscar-cliente:hover {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
        }

        .btn-buscar-cliente:focus,
        .btn-buscar-cliente:active {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Linha de seleção da tabela - Vermelha
        |--------------------------------------------------------------------------
        |
        | Quando o mouse passa sobre uma linha da tabela, a seleção
        | deixa de ser azul e passa a utilizar o vermelho do sistema.
        |
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
        | Campos de formulário em foco - Vermelho
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

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'clientes'); ?>

<main class="container py-4">


    <!-- =========================================================
         CABEÇALHO
    ========================================================== -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            CLIENTES
        </h2>

        <p class="mb-0">
            Visualize os clientes cadastrados pelos balconistas e acompanhe o histórico de compras.
        </p>

    </section>


    <!-- =========================================================
         CLIENTES
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-3">


        <!-- BUSCA -->

        <form
            class="row g-2 mb-3"
            method="GET"
        >

            <div class="col">

                <input
                    class="form-control"
                    type="text"
                    name="busca"
                    value="<?php echo htmlspecialchars($busca); ?>"
                    placeholder="Buscar por nome ou CPF"
                >

            </div>


            <div class="col-auto">

                <button
                    class="btn btn-buscar-cliente rounded-pill px-4"
                    type="submit"
                >
                    BUSCAR
                </button>

            </div>

        </form>


        <!-- =====================================================
             TABELA
        ====================================================== -->

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>
                            Nome
                        </th>

                        <th>
                            CPF
                        </th>

                        <th>
                            Telefone
                        </th>

                        <th>
                            E-mail
                        </th>

                        <th>
                            Compras
                        </th>

                        <th>
                            Total comprado
                        </th>

                        <th>
                            Ação
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!$clientes) { ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-secondary py-4"
                        >
                            Nenhum cliente cadastrado.
                        </td>

                    </tr>

                <?php } ?>


                <?php foreach ($clientes as $cliente) { ?>

                    <tr>

                        <!-- NOME -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $cliente['nome']
                                );
                                ?>
                            </strong>

                        </td>


                        <!-- CPF -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $cliente['cpf'] ?: '-'
                            );
                            ?>

                        </td>


                        <!-- TELEFONE -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $cliente['telefone'] ?: '-'
                            );
                            ?>

                        </td>


                        <!-- E-MAIL -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $cliente['email'] ?: '-'
                            );
                            ?>

                        </td>


                        <!-- COMPRAS -->

                        <td>

                            <?php
                            echo (int) $cliente['compras'];
                            ?>

                        </td>


                        <!-- TOTAL -->

                        <td>

                            R$
                            <?php
                            echo number_format(
                                $cliente['total'],
                                2,
                                ',',
                                '.'
                            );
                            ?>

                        </td>


                        <!-- AÇÃO -->

                        <td>

                            <a
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                href="historicocliente.php?id=<?php echo (int) $cliente['id']; ?>"
                            >
                                HISTÓRICO
                            </a>

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
