<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('balconista');

$farmaciaId = (int) $_SESSION['farmacia_id'];

$mensagem = '';
$erro = '';

function e($valor)
{
    return htmlspecialchars(
        $valor ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatarCPF($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return $cpf;
    }

    return substr($cpf, 0, 3) . '.' .
           substr($cpf, 3, 3) . '.' .
           substr($cpf, 6, 3) . '-' .
           substr($cpf, 9, 2);
}

function formatarTelefone($telefone)
{
    $telefone = preg_replace('/\D/', '', $telefone);

    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' .
               substr($telefone, 2, 5) . '-' .
               substr($telefone, 7, 4);
    }

    if (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' .
               substr($telefone, 2, 4) . '-' .
               substr($telefone, 6, 4);
    }

    return $telefone;
}


/* =========================
   CADASTRO DE CLIENTE
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');

    $cpf = preg_replace(
        '/\D/',
        '',
        $_POST['cpf'] ?? ''
    );

    $telefone = preg_replace(
        '/\D/',
        '',
        $_POST['telefone'] ?? ''
    );

    $email = trim($_POST['email'] ?? '');


    /* =========================
       VALIDAÇÕES
       ========================= */

    if ($nome === '') {

        $erro = 'Informe o nome do cliente.';

    } elseif (
        $cpf !== '' &&
        strlen($cpf) !== 11
    ) {

        $erro = 'O CPF deve ter 11 números.';

    } elseif (
        $telefone !== '' &&
        !in_array(strlen($telefone), [10, 11], true)
    ) {

        $erro = 'O telefone deve ter 10 ou 11 números.';

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $erro = 'Informe um e-mail válido.';

    } else {


        /* =========================
           VERIFICA CPF
           ========================= */

        if ($cpf !== '') {

            $stmt = mysqli_prepare(
                $conexao,
                'SELECT id
                 FROM clientes
                 WHERE farmacia_id = ?
                 AND cpf = ?
                 LIMIT 1'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'is',
                $farmaciaId,
                $cpf
            );

            mysqli_stmt_execute($stmt);

            $existente = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            mysqli_stmt_close($stmt);

        } else {

            $existente = null;

        }


        /* =========================
           CADASTRA
           ========================= */

        if ($existente) {

            $erro = 'Já existe um cliente cadastrado com esse CPF.';

        } else {

            $cpfBanco = $cpf === '' ? null : $cpf;
            $telefoneBanco = $telefone === '' ? null : $telefone;
            $emailBanco = $email === '' ? null : $email;

            $stmt = mysqli_prepare(
                $conexao,
                'INSERT INTO clientes
                (
                    farmacia_id,
                    nome,
                    cpf,
                    telefone,
                    email
                )
                VALUES (?, ?, ?, ?, ?)'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'issss',
                $farmaciaId,
                $nome,
                $cpfBanco,
                $telefoneBanco,
                $emailBanco
            );

            if (mysqli_stmt_execute($stmt)) {

                $clienteId = mysqli_insert_id($conexao);

                registrarAuditoria(
                    $conexao,
                    'cadastrar',
                    'cliente',
                    $clienteId,
                    'Cliente ' . $nome . ' cadastrado pelo balconista.'
                );

                $mensagem = 'Cliente cadastrado com sucesso.';

            } else {

                $erro = 'Não foi possível cadastrar o cliente.';

            }

            mysqli_stmt_close($stmt);

        }

    }

}


/* =========================
   BUSCA DE CLIENTES
   ========================= */

$busca = trim($_GET['busca'] ?? '');

if ($busca !== '') {

    $buscaCPF = preg_replace(
        '/\D/',
        '',
        $busca
    );

    if (
        $buscaCPF !== '' &&
        strlen($buscaCPF) >= 3 &&
        strlen($buscaCPF) <= 11
    ) {

        $likeNome = '%' . $busca . '%';
        $likeCPF = '%' . $buscaCPF . '%';

        $stmt = mysqli_prepare(
            $conexao,
            'SELECT
                id,
                nome,
                cpf,
                telefone,
                email,
                criado_em
             FROM clientes
             WHERE farmacia_id = ?
             AND (
                 nome LIKE ?
                 OR cpf LIKE ?
             )
             ORDER BY nome'
        );

        mysqli_stmt_bind_param(
            $stmt,
            'iss',
            $farmaciaId,
            $likeNome,
            $likeCPF
        );

    } else {

        $like = '%' . $busca . '%';

        $stmt = mysqli_prepare(
            $conexao,
            'SELECT
                id,
                nome,
                cpf,
                telefone,
                email,
                criado_em
             FROM clientes
             WHERE farmacia_id = ?
             AND nome LIKE ?
             ORDER BY nome'
        );

        mysqli_stmt_bind_param(
            $stmt,
            'is',
            $farmaciaId,
            $like
        );

    }

} else {

    $stmt = mysqli_prepare(
        $conexao,
        'SELECT
            id,
            nome,
            cpf,
            telefone,
            email,
            criado_em
         FROM clientes
         WHERE farmacia_id = ?
         ORDER BY nome'
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

    <?php recursosCabeca('FarmaCerta - Clientes'); ?>

    <style>

        :root {
            --vermelho-farmacerta: #e52b38;
            --vermelho-farmacerta-hover: #c91f2d;
            --vermelho-farmacerta-claro: rgba(229, 43, 56, 0.25);
        }

        .btn-cadastrar-cliente,
        .btn-buscar-cliente {

            background-color: var(--vermelho-farmacerta) !important;
            border-color: var(--vermelho-farmacerta) !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;

        }

        .btn-cadastrar-cliente:hover,
        .btn-buscar-cliente:hover {

            background-color: var(--vermelho-farmacerta-hover) !important;
            border-color: var(--vermelho-farmacerta-hover) !important;
            color: #ffffff !important;

        }

        .btn-cadastrar-cliente:focus,
        .btn-cadastrar-cliente:focus-visible,
        .btn-cadastrar-cliente:active,
        .btn-buscar-cliente:focus,
        .btn-buscar-cliente:focus-visible,
        .btn-buscar-cliente:active {

            background-color: var(--vermelho-farmacerta-hover) !important;
            border-color: var(--vermelho-farmacerta-hover) !important;
            color: #ffffff !important;

            box-shadow:
                0 0 0 0.2rem var(--vermelho-farmacerta-claro) !important;

            outline: none !important;

        }

        .form-control:focus,
        .form-control:focus-visible {

            border-color: var(--vermelho-farmacerta) !important;

            outline: none !important;

            box-shadow:
                0 0 0 0.2rem var(--vermelho-farmacerta-claro) !important;

        }

        #cpf,
        #telefone {

            font-variant-numeric: tabular-nums;

        }

        #cpf::placeholder,
        #telefone::placeholder {

            color: #6c757d;
            opacity: 1;

        }

        .btn-historico {

            color: #e52b38 !important;
            border-color: #e52b38 !important;

        }

        .btn-historico:hover {

            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;

        }

    </style>

</head>


<body>

<?php cabecalho('Sistema de Gestão', 'balconista', 'clientes'); ?>


<main class="container py-4">


    <!-- =========================
         CABEÇALHO
         ========================= -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            CLIENTES
        </h2>

        <p class="mb-0">
            Cadastre os clientes da farmácia e consulte o histórico de compras.
        </p>

    </section>


    <!-- =========================
         MENSAGENS
         ========================= -->

    <?php if (isset($_GET['editado'])) { ?>

        <div class="alert alert-success">
            Cliente atualizado com sucesso.
        </div>

    <?php } ?>


    <?php if ($mensagem !== '') { ?>

        <div class="alert alert-success">
            <?php echo e($mensagem); ?>
        </div>

    <?php } ?>


    <?php if ($erro !== '') { ?>

        <div class="alert alert-danger">
            <?php echo e($erro); ?>
        </div>

    <?php } ?>


    <!-- =========================
         CADASTRAR CLIENTE
         ========================= -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h3 class="h5 fw-bold mb-3">
            CADASTRAR CLIENTE
        </h3>


        <form method="POST" class="row g-3">


            <!-- NOME -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="nome"
                >
                    Nome
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="150"
                    placeholder="Digite o nome completo"
                    autocomplete="name"
                    required
                >

            </div>


            <!-- CPF -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="cpf"
                >
                    CPF
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->

                <input
                    class="form-control"
                    type="text"
                    id="cpf"
                    name="cpf"
                    placeholder="000.000.000-00"
                    maxlength="14"
                    inputmode="numeric"
                    autocomplete="off"
                >

            </div>


            <!-- TELEFONE -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="telefone"
                >
                    Telefone
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->

                <input
                    class="form-control"
                    type="text"
                    id="telefone"
                    name="telefone"
                    placeholder="(00) 00000-0000"
                    maxlength="15"
                    inputmode="numeric"
                    autocomplete="tel"
                >

            </div>


            <!-- E-MAIL -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="email"
                >
                    E-mail
                </label>

                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    placeholder="Digite o seu e-mail"
                    autocomplete="email"
                >

            </div>


            <!-- BOTÃO -->

            <div class="col-12">

                <button
                    class="btn btn-cadastrar-cliente rounded-pill px-4"
                    type="submit"
                >
                    CADASTRAR CLIENTE
                </button>

            </div>

        </form>

    </section>


    <!-- =========================
         CLIENTES CADASTRADOS
         ========================= -->

    <section class="card shadow-sm rounded-4 p-4">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">


            <h3 class="h5 fw-bold mb-0">
                CLIENTES CADASTRADOS
            </h3>


            <!-- BUSCA -->

            <form
                class="d-flex gap-2"
                method="GET"
            >

                <input
                    class="form-control"
                    type="text"
                    name="busca"
                    value="<?php echo e($busca); ?>"
                    placeholder="Digite o nome ou CPF"
                >

                <button
                    class="btn btn-buscar-cliente rounded-pill px-4"
                    type="submit"
                >
                    BUSCAR
                </button>

            </form>

        </div>


        <!-- =========================
             TABELA
             ========================= -->

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <th>Ação</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!$clientes) { ?>

                    <tr>

                        <td
                            colspan="5"
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
                                <?php echo e($cliente['nome']); ?>
                            </strong>

                        </td>


                        <!-- CPF -->

                        <td>

                            <?php echo e(
                                $cliente['cpf']
                                    ? formatarCPF($cliente['cpf'])
                                    : '-'
                            ); ?>

                        </td>


                        <!-- TELEFONE -->

                        <td>

                            <?php echo e(
                                $cliente['telefone']
                                    ? formatarTelefone($cliente['telefone'])
                                    : '-'
                            ); ?>

                        </td>


                        <!-- E-MAIL -->

                        <td>

                            <?php echo e(
                                $cliente['email'] ?: '-'
                            ); ?>

                        </td>


                        <!-- AÇÕES -->

                        <td class="d-flex gap-2 flex-wrap">

                            <a
                                class="btn btn-sm btn-outline-primary rounded-pill btn-historico"
                                href="historicocliente.php?id=<?php echo (int) $cliente['id']; ?>"
                            >
                                HISTÓRICO
                            </a>


                            <a
                                class="btn btn-sm btn-outline-secondary rounded-pill"
                                href="editarcliente.php?id=<?php echo (int) $cliente['id']; ?>"
                            >
                                EDITAR
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


<script>

/* =========================
   MÁSCARA CPF
   ========================= */

document.addEventListener('DOMContentLoaded', function () {

    const campoCPF = document.getElementById('cpf');

    if (campoCPF) {

        campoCPF.addEventListener('input', function () {

            let valor = this.value.replace(/\D/g, '');

            valor = valor.substring(0, 11);


            if (valor.length > 9) {

                valor = valor.replace(
                    /^(\d{3})(\d{3})(\d{3})(\d{1,2}).*/,
                    '$1.$2.$3-$4'
                );

            } else if (valor.length > 6) {

                valor = valor.replace(
                    /^(\d{3})(\d{3})(\d{1,3}).*/,
                    '$1.$2.$3'
                );

            } else if (valor.length > 3) {

                valor = valor.replace(
                    /^(\d{3})(\d{1,3}).*/,
                    '$1.$2'
                );

            }

            this.value = valor;

        });

    }


    /* =========================
       MÁSCARA TELEFONE
       ========================= */

    const campoTelefone = document.getElementById('telefone');

    if (campoTelefone) {

        campoTelefone.addEventListener('input', function () {

            let valor = this.value.replace(/\D/g, '');

            valor = valor.substring(0, 11);


            if (valor.length > 10) {

                valor = valor.replace(
                    /^(\d{2})(\d{5})(\d{1,4}).*/,
                    '($1) $2-$3'
                );

            } else if (valor.length > 6) {

                valor = valor.replace(
                    /^(\d{2})(\d{4})(\d{1,4}).*/,
                    '($1) $2-$3'
                );

            } else if (valor.length > 2) {

                valor = valor.replace(
                    /^(\d{2})(\d{1,5}).*/,
                    '($1) $2'
                );

            } else if (valor.length > 0) {

                valor = '(' + valor;

            }

            this.value = valor;

        });

    }

});

</script>

</body>
</html>