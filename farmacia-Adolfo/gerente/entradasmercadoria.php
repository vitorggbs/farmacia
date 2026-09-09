<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];


/* =========================
   REGISTRO DE ENTRADA
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fornecedorId = (int) ($_POST['fornecedor_id'] ?? 0);
    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 0);

    $custoTexto = trim($_POST['custo_unitario'] ?? '0');

    // Aceita valores como 12,50 ou 1.250,50
    $custoTexto = str_replace('R$', '', $custoTexto);
    $custoTexto = str_replace('.', '', $custoTexto);
    $custoTexto = str_replace(',', '.', $custoTexto);

    $custo = (float) $custoTexto;

    $nota = trim($_POST['numero_nota'] ?? '');
    $numeroLote = trim($_POST['numero_lote'] ?? '');
    $validade = $_POST['validade'] ?? '';


    /* =========================
       VALIDAÇÃO
       ========================= */

    if (
        $fornecedorId < 1 ||
        $produtoId < 1 ||
        $quantidade < 1 ||
        $custo < 0 ||
        $numeroLote === '' ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validade)
    ) {

        header('Location: entradasmercadoria.php?erro=dados');
        exit;
    }


    mysqli_begin_transaction($conexao);

    try {


        /* =========================
           CRIA OU ATUALIZA LOTE
           ========================= */

        $stmt = mysqli_prepare(
            $conexao,
            'INSERT INTO lotes
            (
                farmacia_id,
                produto_id,
                numero_lote,
                quantidade,
                validade
            )
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
            $numeroLote,
            $quantidade,
            $validade
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);


        /* =========================
           BUSCA ID DO LOTE
           ========================= */

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
            $numeroLote
        );

        mysqli_stmt_execute($stmt);

        $resultadoLote = mysqli_stmt_get_result($stmt);
        $lote = mysqli_fetch_assoc($resultadoLote);

        mysqli_stmt_close($stmt);

        $loteId = (int) ($lote['id'] ?? 0);

        if ($loteId < 1) {
            throw new Exception('Lote não encontrado.');
        }


        /* =========================
           REGISTRA ENTRADA
           ========================= */

        $stmt = mysqli_prepare(
            $conexao,
            'INSERT INTO entradas_mercadoria
            (
                farmacia_id,
                fornecedor_id,
                produto_id,
                usuario_id,
                lote_id,
                quantidade,
                custo_unitario,
                numero_nota
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        mysqli_stmt_bind_param(
            $stmt,
            'iiiiiids',
            $farmaciaId,
            $fornecedorId,
            $produtoId,
            $usuarioId,
            $loteId,
            $quantidade,
            $custo,
            $nota
        );

        mysqli_stmt_execute($stmt);

        $entradaId = mysqli_insert_id($conexao);

        mysqli_stmt_close($stmt);


        /* =========================
           ATUALIZA ESTOQUE
           ========================= */

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
            $quantidade,
            $produtoId,
            $farmaciaId
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) < 1) {
            mysqli_stmt_close($stmt);
            throw new Exception('Produto não encontrado.');
        }

        mysqli_stmt_close($stmt);


        /* =========================
           MOVIMENTAÇÃO
           ========================= */

        $tipo = 'entrada';

        $obs =
            'Entrada #' .
            $entradaId .
            ' - lote ' .
            $numeroLote .
            ($nota !== '' ? ' - nota ' . $nota : '');

        $stmt = mysqli_prepare(
            $conexao,
            'INSERT INTO movimentacoes_estoque
            (
                farmacia_id,
                produto_id,
                usuario_id,
                lote_id,
                tipo,
                quantidade,
                observacao
            )
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
            $quantidade,
            $obs
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        /* =========================
           AUDITORIA
           ========================= */

        registrarAuditoria(
            $conexao,
            'entrada',
            'entrada_mercadoria',
            $entradaId,
            'Entrada de ' .
            $quantidade .
            ' unidade(s), lote ' .
            $numeroLote
        );


        mysqli_commit($conexao);

        header('Location: entradasmercadoria.php?ok=1');
        exit;


    } catch (Throwable $e) {

        mysqli_rollback($conexao);

        header('Location: entradasmercadoria.php?erro=banco');
        exit;
    }
}


/* =========================
   FORNECEDORES
   ========================= */

$fornecedores = mysqli_query(
    $conexao,
    "SELECT id, nome
     FROM fornecedores
     WHERE farmacia_id = $farmaciaId
     AND ativo = 1
     ORDER BY nome"
);


/* =========================
   PRODUTOS
   ========================= */

$produtos = mysqli_query(
    $conexao,
    "SELECT id, nome
     FROM produtos
     WHERE farmacia_id = $farmaciaId
     AND ativo = 1
     ORDER BY nome"
);


/* =========================
   HISTÓRICO
   ========================= */

$historico = mysqli_query(
    $conexao,
    "SELECT
        e.*,
        f.nome AS fornecedor,
        p.nome AS produto,
        u.nome AS usuario,
        l.numero_lote,
        l.validade
     FROM entradas_mercadoria e
     INNER JOIN fornecedores f
        ON f.id = e.fornecedor_id
     INNER JOIN produtos p
        ON p.id = e.produto_id
     INNER JOIN usuarios u
        ON u.id = e.usuario_id
     LEFT JOIN lotes l
        ON l.id = e.lote_id
     WHERE e.farmacia_id = $farmaciaId
     ORDER BY e.id DESC
     LIMIT 50"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Entrada de mercadoria'); ?>

    <style>

        :root {
            --vermelho-farmacerta: #e52b38;
            --vermelho-farmacerta-hover: #c91f2d;
            --vermelho-farmacerta-claro: rgba(229, 43, 56, 0.25);
        }


        /* =========================
           BOTÃO REGISTRAR ENTRADA
           ========================= */

        .btn-registrar-entrada {
            background-color: var(--vermelho-farmacerta) !important;
            border-color: var(--vermelho-farmacerta) !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-registrar-entrada:hover {
            background-color: var(--vermelho-farmacerta-hover) !important;
            border-color: var(--vermelho-farmacerta-hover) !important;
            color: #ffffff !important;
        }

        .btn-registrar-entrada:focus,
        .btn-registrar-entrada:focus-visible {
            background-color: var(--vermelho-farmacerta-hover) !important;
            border-color: var(--vermelho-farmacerta-hover) !important;
            color: #ffffff !important;

            outline: 2px solid var(--vermelho-farmacerta) !important;
            outline-offset: 1px !important;

            box-shadow:
                0 0 0 0.2rem var(--vermelho-farmacerta-claro) !important;
        }

        .btn-registrar-entrada:active {
            background-color: var(--vermelho-farmacerta-hover) !important;
            border-color: var(--vermelho-farmacerta-hover) !important;
            color: #ffffff !important;
        }


        /* =========================
           CAMPOS
           ========================= */

        .form-control:focus,
        .form-control:focus-visible,
        .form-select:focus,
        .form-select:focus-visible {

            border-color: var(--vermelho-farmacerta) !important;

            outline: 2px solid var(--vermelho-farmacerta) !important;

            outline-offset: -1px !important;

            box-shadow:
                0 0 0 0.2rem var(--vermelho-farmacerta-claro) !important;
        }


        .form-select:hover,
        .form-control:hover {
            border-color: #d94a54;
        }


        .form-control::selection,
        .form-select::selection {
            background-color: var(--vermelho-farmacerta);
            color: #ffffff;
        }


        .form-control,
        .form-select {

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                outline 0.2s ease;

        }


        .form-label {
            font-weight: 600;
        }


        input:focus,
        select:focus,
        textarea:focus,
        button:focus {
            -webkit-tap-highlight-color: transparent;
        }


        .form-select option:checked {
            background-color: var(--vermelho-farmacerta);
            color: #ffffff;
        }

    </style>

</head>


<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'entradas'); ?>


<main class="container py-4">


    <!-- =========================
         CABEÇALHO
         ========================= -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            ENTRADA DE MERCADORIA
        </h2>

        <p class="mb-0">
            A entrada atualiza o estoque e também o lote/validade.
        </p>

    </section>


    <!-- =========================
         MENSAGENS
         ========================= -->

    <?php if (isset($_GET['ok'])) { ?>

        <div class="alert alert-success">
            Entrada registrada.
        </div>

    <?php } ?>


    <?php if (isset($_GET['erro'])) { ?>

        <div class="alert alert-danger">
            Verifique os dados da entrada.
        </div>

    <?php } ?>


    <!-- =========================
         FORMULÁRIO
         ========================= -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h3 class="h5 fw-bold mb-3">
            REGISTRAR ENTRADA
        </h3>


        <form method="POST" class="row g-3">


            <!-- FORNECEDOR -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="fornecedor_id"
                >
                    Fornecedor
                </label>

                <select
                    class="form-select"
                    id="fornecedor_id"
                    name="fornecedor_id"
                    required
                >

                    <option value="">
                        Selecione o fornecedor
                    </option>

                    <?php while ($f = mysqli_fetch_assoc($fornecedores)) { ?>

                        <option value="<?php echo (int) $f['id']; ?>">

                            <?php
                            echo htmlspecialchars(
                                $f['nome'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- PRODUTO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="produto_id"
                >
                    Produto
                </label>

                <select
                    class="form-select"
                    id="produto_id"
                    name="produto_id"
                    required
                >

                    <option value="">
                        Selecione o produto
                    </option>

                    <?php while ($p = mysqli_fetch_assoc($produtos)) { ?>

                        <option value="<?php echo (int) $p['id']; ?>">

                            <?php
                            echo htmlspecialchars(
                                $p['nome'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- QUANTIDADE -->

            <div class="col-6 col-md-3">

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
                    inputmode="numeric"
                    required
                >

            </div>


            <!-- CUSTO -->

            <div class="col-6 col-md-3">

                <label
                    class="form-label fw-bold"
                    for="custo_unitario"
                >
                    Custo unitário
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="custo_unitario"
                    name="custo_unitario"
                    placeholder="Digite o custo unitário"
                    inputmode="decimal"
                    required
                >

            </div>


            <!-- LOTE -->

            <div class="col-6 col-md-3">

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


            <!-- NOTA -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="numero_nota"
                >
                    Número da nota do fornecedor
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="numero_nota"
                    name="numero_nota"
                    maxlength="50"
                    placeholder="Digite o número da nota"
                >

            </div>


            <!-- BOTÃO -->

            <div class="col-12">

                <button
                    class="btn btn-registrar-entrada rounded-pill px-4"
                    type="submit"
                >
                    REGISTRAR ENTRADA
                </button>

            </div>

        </form>

    </section>


    <!-- =========================
         HISTÓRICO
         ========================= -->

    <section class="card shadow-sm rounded-4 p-3">

        <h3 class="h5 fw-bold mb-3">
            ÚLTIMAS ENTRADAS
        </h3>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Data</th>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Qtd.</th>
                        <th>Custo</th>
                        <th>Responsável</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($historico) === 0) { ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center text-secondary py-4"
                        >
                            Nenhuma entrada registrada.
                        </td>

                    </tr>

                <?php } ?>


                <?php while ($e = mysqli_fetch_assoc($historico)) { ?>

                    <tr>

                        <td>
                            <?php
                            echo date(
                                'd/m/Y H:i',
                                strtotime($e['criado_em'])
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $e['fornecedor'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $e['produto'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $e['numero_lote'] ?: '-',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo $e['validade']
                                ? date(
                                    'd/m/Y',
                                    strtotime($e['validade'])
                                )
                                : '-';
                            ?>
                        </td>


                        <td>
                            <?php
                            echo (int) $e['quantidade'];
                            ?>
                        </td>


                        <td>
                            R$
                            <?php
                            echo number_format(
                                $e['custo_unitario'],
                                2,
                                ',',
                                '.'
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $e['usuario'],
                                ENT_QUOTES,
                                'UTF-8'
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


<script>

/* =========================
   CUSTO UNITÁRIO
   ========================= */

const custo = document.getElementById('custo_unitario');

custo.addEventListener('input', () => {

    let valor = custo.value
        .replace(/\D/g, '');

    if (!valor) {
        custo.value = '';
        return;
    }

    let [reais, centavos] = (Number(valor) / 100)
        .toFixed(2)
        .split('.');

    reais = reais.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        '.'
    );

    custo.value = `R$ ${reais},${centavos}`;

});

</script>


</body>
</html>