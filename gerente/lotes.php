<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];

/*
|--------------------------------------------------------------------------
| Registrar lote
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $numero = trim($_POST['numero_lote'] ?? '');
    $qtd = (int) ($_POST['quantidade'] ?? 0);
    $validade = $_POST['validade'] ?? '';

    if (
        $produtoId > 0 &&
        $numero !== '' &&
        $qtd > 0 &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $validade)
    ) {

        mysqli_begin_transaction($conexao);

        try {

            /*
            |--------------------------------------------------------------------------
            | Insere ou atualiza o lote
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $conexao,
                'INSERT INTO lotes
                (farmacia_id, produto_id, numero_lote, quantidade, validade)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    quantidade = quantidade + VALUES(quantidade),
                    validade = VALUES(validade)'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'iisis',
                $farmaciaId,
                $produtoId,
                $numero,
                $qtd,
                $validade
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            /*
            |--------------------------------------------------------------------------
            | Busca o ID do lote
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $conexao,
                'SELECT id
                 FROM lotes
                 WHERE farmacia_id = ?
                 AND produto_id = ?
                 AND numero_lote = ?
                 LIMIT 1'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'iis',
                $farmaciaId,
                $produtoId,
                $numero
            );

            mysqli_stmt_execute($stmt);

            $resultadoLote = mysqli_stmt_get_result($stmt);
            $lote = mysqli_fetch_assoc($resultadoLote);

            mysqli_stmt_close($stmt);

            if (!$lote) {
                throw new Exception('Lote não encontrado após o cadastro.');
            }

            $loteId = (int) $lote['id'];

            /*
            |--------------------------------------------------------------------------
            | Atualiza estoque do produto
            |--------------------------------------------------------------------------
            */

            $stmt = mysqli_prepare(
                $conexao,
                'UPDATE produtos
                 SET quantidade = quantidade + ?
                 WHERE id = ?
                 AND farmacia_id = ?'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'iii',
                $qtd,
                $produtoId,
                $farmaciaId
            );

            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) < 1) {
                mysqli_stmt_close($stmt);
                throw new Exception('Produto não encontrado.');
            }

            mysqli_stmt_close($stmt);

            /*
            |--------------------------------------------------------------------------
            | Registra movimentação de estoque
            |--------------------------------------------------------------------------
            */

            $tipo = 'entrada';
            $obs = 'Cadastro/entrada manual do lote ' . $numero;

            $stmt = mysqli_prepare(
                $conexao,
                'INSERT INTO movimentacoes_estoque
                (farmacia_id, produto_id, usuario_id, lote_id, tipo, quantidade, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'iiiisis',
                $farmaciaId,
                $produtoId,
                $usuarioId,
                $loteId,
                $tipo,
                $qtd,
                $obs
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            registrarAuditoria(
                $conexao,
                'cadastrar',
                'lote',
                $loteId,
                'Lote ' . $numero . ' cadastrado/atualizado'
            );

            mysqli_commit($conexao);

            header('Location: lotes.php?ok=1');
            exit;

        } catch (Throwable $e) {

            mysqli_rollback($conexao);

            header('Location: lotes.php?erro=1');
            exit;
        }
    }

    header('Location: lotes.php?erro=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Produtos ativos
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    'SELECT id, nome
     FROM produtos
     WHERE farmacia_id = ?
     AND ativo = 1
     ORDER BY nome'
);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$produtos = mysqli_stmt_get_result($stmt);

/*
|--------------------------------------------------------------------------
| Lotes cadastrados
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    'SELECT
        l.*,
        p.nome AS produto,
        DATEDIFF(l.validade, CURDATE()) AS dias
     FROM lotes l
     INNER JOIN produtos p
        ON p.id = l.produto_id
     WHERE l.farmacia_id = ?
     ORDER BY l.validade, l.id'
);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$lotes = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Lotes e validade'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | COR VERMELHA DOS CAMPOS AO SELECIONAR
        |--------------------------------------------------------------------------
        */

        .form-control:focus,
        .form-select:focus {

            border-color: #e52b38 !important;

            box-shadow:
                0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;

            outline: none !important;

        }


        /*
        |--------------------------------------------------------------------------
        | SELECT - SETA E BORDA VERMELHA
        |--------------------------------------------------------------------------
        */

        .form-select:focus {

            border-color: #e52b38 !important;

            box-shadow:
                0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;

        }


        /*
        |--------------------------------------------------------------------------
        | INPUT DATE - BORDA VERMELHA
        |--------------------------------------------------------------------------
        */

        input[type="date"]:focus {

            border-color: #e52b38 !important;

            box-shadow:
                0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;

            outline: none !important;

        }


        /*
        |--------------------------------------------------------------------------
        | BOTÃO REGISTRAR LOTE
        |--------------------------------------------------------------------------
        */

        .btn-registrar-lote {

            background-color: #e52b38 !important;

            border-color: #e52b38 !important;

            color: #ffffff !important;

            font-weight: 600;

            transition: all 0.2s ease;

        }


        .btn-registrar-lote:hover {

            background-color: #c91f2d !important;

            border-color: #c91f2d !important;

            color: #ffffff !important;

        }


        .btn-registrar-lote:focus {

            background-color: #c91f2d !important;

            border-color: #c91f2d !important;

            color: #ffffff !important;

            box-shadow:
                0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;

        }


        .btn-registrar-lote:active {

            background-color: #b71c29 !important;

            border-color: #b71c29 !important;

            color: #ffffff !important;

        }

    </style>

</head>

<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'lotes'); ?>


<main class="container py-4">


    <!-- =========================================================
         CABEÇALHO
    ========================================================== -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            LOTES E VALIDADE
        </h2>

        <p class="mb-0">
            Controle os lotes e veja rapidamente o que está vencido ou próximo do vencimento.
        </p>

    </section>



    <!-- =========================================================
         MENSAGENS
    ========================================================== -->

    <?php if (isset($_GET['ok'])) { ?>

        <div class="alert alert-success">
            Lote registrado e estoque atualizado.
        </div>

    <?php } ?>


    <?php if (isset($_GET['erro'])) { ?>

        <div class="alert alert-danger">
            Não foi possível registrar o lote.
        </div>

    <?php } ?>



    <!-- =========================================================
         REGISTRAR LOTE
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h3 class="h5 fw-bold mb-3">
            REGISTRAR LOTE MANUALMENTE
        </h3>


        <form method="POST" class="row g-3">


            <!-- PRODUTO -->

            <div class="col-12 col-md-4">

                <label
                    class="form-label fw-bold"
                    for="produto_id"
                >
                    Produto
                </label>


                <select
                    name="produto_id"
                    id="produto_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Selecione
                    </option>


                    <?php while ($p = mysqli_fetch_assoc($produtos)) { ?>

                        <option
                            value="<?php echo (int) $p['id']; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $p['nome']
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>

            </div>



            <!-- LOTE -->

            <div class="col-12 col-md-3">

                <label
                    class="form-label fw-bold"
                    for="numero_lote"
                >
                    Lote
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="numero_lote"
                    name="numero_lote"
                    maxlength="50"
                    placeholder="Digite o número do lote"
                    required
                >

            </div>



            <!-- QUANTIDADE -->

            <div class="col-6 col-md-2">

                <label
                    class="form-label fw-bold"
                    for="quantidade"
                >
                    Quantidade
                </label>


                <input
                    class="form-control"
                    type="number"
                    id="quantidade"
                    min="1"
                    name="quantidade"
                    placeholder="Digite a quantidade"
                    required
                >

            </div>



            <!-- VALIDADE -->

            <div class="col-6 col-md-3">

                <label
                    class="form-label fw-bold"
                    for="validade"
                >
                    Validade
                </label>


                <input
                    class="form-control"
                    type="date"
                    id="validade"
                    name="validade"
                    required
                >

            </div>



            <!-- BOTÃO -->

            <div class="col-12">

                <button
                    class="btn btn-registrar-lote rounded-pill px-4"
                    type="submit"
                >
                    REGISTRAR LOTE
                </button>

            </div>

        </form>

    </section>



    <!-- =========================================================
         LISTA DE LOTES
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-3">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>
                            Produto
                        </th>

                        <th>
                            Lote
                        </th>

                        <th>
                            Qtd.
                        </th>

                        <th>
                            Validade
                        </th>

                        <th>
                            Situação
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($lotes) === 0) { ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-secondary py-4"
                        >
                            Nenhum lote cadastrado.
                        </td>

                    </tr>

                <?php } ?>


                <?php while ($l = mysqli_fetch_assoc($lotes)) {

                    $dias = (int) $l['dias'];

                    if ($dias < 0) {

                        $badge = 'text-bg-danger';
                        $texto = 'Vencido';

                    } elseif ($dias <= 30) {

                        $badge = 'text-bg-warning';
                        $texto = 'Vence em ' . $dias . ' dia(s)';

                    } else {

                        $badge = 'text-bg-success';
                        $texto = 'OK';

                    }

                ?>

                    <tr>


                        <!-- PRODUTO -->

                        <td>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $l['produto']
                                );

                                ?>

                            </strong>

                        </td>



                        <!-- LOTE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $l['numero_lote']
                            );

                            ?>

                        </td>



                        <!-- QUANTIDADE -->

                        <td>

                            <?php

                            echo (int) $l['quantidade'];

                            ?>

                        </td>



                        <!-- VALIDADE -->

                        <td>

                            <?php

                            echo date(
                                'd/m/Y',
                                strtotime($l['validade'])
                            );

                            ?>

                        </td>



                        <!-- SITUAÇÃO -->

                        <td>

                            <span class="badge <?php echo $badge; ?>">

                                <?php

                                echo htmlspecialchars(
                                    $texto
                                );

                                ?>

                            </span>

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
