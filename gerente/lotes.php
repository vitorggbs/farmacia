<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];


/* =========================
   REGISTRAR LOTE
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $numero = trim($_POST['numero_lote'] ?? '');
    $qtd = (int) ($_POST['quantidade'] ?? 0);
    $validade = trim($_POST['validade'] ?? '');

    /*
     * A data chega no formato AAAA-MM-DD,
     * depois de ser convertida pelo JavaScript.
     */

    if (
        $produtoId > 0 &&
        $numero !== '' &&
        $qtd > 0 &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $validade)
    ) {

        mysqli_begin_transaction($conexao);

        try {

            /* =========================
               INSERIR OU ATUALIZAR LOTE
               ========================= */

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


            /* =========================
               BUSCAR ID DO LOTE
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
                $numero
            );

            mysqli_stmt_execute($stmt);

            $resultadoLote = mysqli_stmt_get_result($stmt);
            $lote = mysqli_fetch_assoc($resultadoLote);

            mysqli_stmt_close($stmt);

            if (!$lote) {
                throw new Exception(
                    'Lote não encontrado após o cadastro.'
                );
            }

            $loteId = (int) $lote['id'];


            /* =========================
               ATUALIZAR ESTOQUE
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
                $qtd,
                $produtoId,
                $farmaciaId
            );

            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) < 1) {
                mysqli_stmt_close($stmt);

                throw new Exception(
                    'Produto não encontrado.'
                );
            }

            mysqli_stmt_close($stmt);


            /* =========================
               REGISTRAR MOVIMENTAÇÃO
               ========================= */

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


            /* =========================
               AUDITORIA
               ========================= */

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


/* =========================
   PRODUTOS ATIVOS
   ========================= */

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


/* =========================
   LOTES CADASTRADOS
   ========================= */

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

        .form-control:focus,
        .form-select:focus {

            border-color: #e52b38 !important;

            box-shadow:
                0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;

            outline: none !important;

        }

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

    <!-- CABEÇALHO -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            LOTES E VALIDADE
        </h2>

        <p class="mb-0">
            Controle os lotes e veja rapidamente o que está vencido ou próximo do vencimento.
        </p>

    </section>


    <!-- MENSAGEM DE SUCESSO -->

    <?php if (isset($_GET['ok'])): ?>

        <div class="alert alert-success">
            Lote registrado e estoque atualizado.
        </div>

    <?php endif; ?>


    <!-- MENSAGEM DE ERRO -->

    <?php if (isset($_GET['erro'])): ?>

        <div class="alert alert-danger">
            Não foi possível registrar o lote.
        </div>

    <?php endif; ?>


    <!-- REGISTRAR LOTE -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h3 class="h5 fw-bold mb-3">
            REGISTRAR LOTE MANUALMENTE
        </h3>

        <form
            method="POST"
            id="formLote"
            class="row g-3"
        >

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

                    <?php while ($p = mysqli_fetch_assoc($produtos)): ?>

                        <option
                            value="<?= (int) $p['id'] ?>"
                        >
                            <?= htmlspecialchars(
                                $p['nome'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- NÚMERO DO LOTE -->

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
                    name="quantidade"
                    min="1"
                    step="1"
                    placeholder="Quantidade"
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
                    type="text"
                    id="validade"
                    name="validade"
                    maxlength="10"
                    placeholder="DD/MM/AAAA"
                    inputmode="numeric"
                    autocomplete="off"
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


    <!-- LISTA DE LOTES -->

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

                <?php if (mysqli_num_rows($lotes) === 0): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-secondary py-4"
                        >
                            Nenhum lote cadastrado.
                        </td>

                    </tr>

                <?php endif; ?>


                <?php while ($l = mysqli_fetch_assoc($lotes)): ?>

                    <?php

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

                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $l['produto'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $l['numero_lote'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $l['quantidade'] ?>
                        </td>

                        <td>
                            <?= date(
                                'd/m/Y',
                                strtotime($l['validade'])
                            ) ?>
                        </td>

                        <td>

                            <span class="badge <?= $badge ?>">
                                <?= htmlspecialchars(
                                    $texto,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php recursosRodape(); ?>


<script>

/* =========================
   ELEMENTOS
   ========================= */

const formulario = document.getElementById('formLote');
const validade = document.getElementById('validade');


/* =========================
   MÁSCARA DA VALIDADE
   PADRÃO: DD/MM/AAAA

   4 → 04
   5 → 05
   6 → 06
   7 → 07
   8 → 08
   9 → 09
   ========================= */

validade.addEventListener('input', () => {

    let valor = validade.value
        .replace(/\D/g, '')
        .slice(0, 8);


    /*
     * Dia:
     * transforma 4 até 9 em 04 até 09.
     */

    if (
        valor.length === 1 &&
        /[4-9]/.test(valor)
    ) {
        valor = '0' + valor;
    }


    /*
     * Limita o dia entre 01 e 31.
     */

    if (valor.length >= 2) {

        let dia = Number(
            valor.substring(0, 2)
        );

        if (dia < 1) {
            dia = 1;
        }

        if (dia > 31) {
            dia = 31;
        }

        valor =
            String(dia).padStart(2, '0') +
            valor.substring(2);
    }


    /*
     * Mês:
     * transforma 4 até 9 em 04 até 09.
     */

    if (valor.length === 3) {

        const dia = valor.substring(0, 2);

        let mes = valor.substring(2, 3);

        if (/[4-9]/.test(mes)) {
            mes = '0' + mes;
        }

        valor = dia + mes;
    }


    /*
     * Limita o mês entre 01 e 12.
     */

    if (valor.length >= 4) {

        let mes = Number(
            valor.substring(2, 4)
        );

        if (mes < 1) {
            mes = 1;
        }

        if (mes > 12) {
            mes = 12;
        }

        valor =
            valor.substring(0, 2) +
            String(mes).padStart(2, '0') +
            valor.substring(4);
    }


    /*
     * Formata para DD/MM/AAAA.
     */

    if (valor.length > 4) {

        valor = valor.replace(
            /(\d{2})(\d{2})(\d{1,4})/,
            '$1/$2/$3'
        );

    } else if (valor.length > 2) {

        valor = valor.replace(
            /(\d{2})(\d{1,2})/,
            '$1/$2'
        );
    }

    validade.value = valor;

});


/* =========================
   VALIDAR DATA
   ========================= */

function dataValida(valor)
{
    const partes = valor.split('/');

    if (partes.length !== 3) {
        return false;
    }

    const diaTexto = partes[0];
    const mesTexto = partes[1];
    const anoTexto = partes[2];

    if (
        diaTexto.length !== 2 ||
        mesTexto.length !== 2 ||
        anoTexto.length !== 4
    ) {
        return false;
    }

    const dia = Number(diaTexto);
    const mes = Number(mesTexto);
    const ano = Number(anoTexto);

    if (
        dia < 1 ||
        dia > 31 ||
        mes < 1 ||
        mes > 12 ||
        ano < 1000 ||
        ano > 9999
    ) {
        return false;
    }

    const data = new Date(
        ano,
        mes - 1,
        dia
    );

    return (
        data.getFullYear() === ano &&
        data.getMonth() === mes - 1 &&
        data.getDate() === dia
    );
}


/* =========================
   CONVERTER DATA PARA MYSQL
   DD/MM/AAAA → AAAA-MM-DD
   ========================= */

function converterDataParaMySQL(valor)
{
    const partes = valor.split('/');

    return (
        partes[2] +
        '-' +
        partes[1] +
        '-' +
        partes[0]
    );
}


/* =========================
   ENVIO DO FORMULÁRIO
   ========================= */

formulario.addEventListener('submit', event => {

    if (!dataValida(validade.value)) {

        event.preventDefault();

        alert(
            'Informe uma validade válida no formato DD/MM/AAAA.'
        );

        validade.focus();

        return;
    }


    /*
     * Converte a data brasileira para o formato
     * aceito pelo MySQL.
     */

    validade.value =
        converterDataParaMySQL(validade.value);

});

</script>

</body>
</html>