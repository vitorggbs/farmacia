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
| Função para formatar CNPJ
|--------------------------------------------------------------------------
*/

function formatarCNPJ($cnpj)
{
    $cnpj = preg_replace('/\D/', '', $cnpj);

    if (strlen($cnpj) !== 14) {
        return $cnpj;
    }

    return substr($cnpj, 0, 2) . '.' .
           substr($cnpj, 2, 3) . '.' .
           substr($cnpj, 5, 3) . '/' .
           substr($cnpj, 8, 4) . '-' .
           substr($cnpj, 12, 2);
}


/*
|--------------------------------------------------------------------------
| Função para formatar telefone
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Cadastro de fornecedor
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');

    // Remove pontos, barras, hífen e qualquer caractere que não seja número.
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');

    // Remove tudo que não for número do telefone.
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    $email = trim($_POST['email'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    if ($nome === '') {

        header('Location: fornecedores.php?erro=nome');
        exit;

    } elseif ($cnpj !== '' && strlen($cnpj) !== 14) {

        header('Location: fornecedores.php?erro=cnpj');
        exit;

    } elseif ($telefone !== '' && !in_array(strlen($telefone), [10, 11], true)) {

        header('Location: fornecedores.php?erro=telefone');
        exit;

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        header('Location: fornecedores.php?erro=email');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Verifica se o CNPJ já está cadastrado
    |--------------------------------------------------------------------------
    */

    if ($cnpj !== '') {

        $stmt = mysqli_prepare(
            $conexao,
            'SELECT id
             FROM fornecedores
             WHERE farmacia_id = ?
             AND cnpj = ?
             LIMIT 1'
        );

        mysqli_stmt_bind_param(
            $stmt,
            'is',
            $farmaciaId,
            $cnpj
        );

        mysqli_stmt_execute($stmt);

        $existente = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

    } else {

        $existente = null;
    }


    /*
    |--------------------------------------------------------------------------
    | Se CNPJ já existir
    |--------------------------------------------------------------------------
    */

    if ($existente) {

        header('Location: fornecedores.php?erro=cnpj_existente');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Valores que serão gravados no banco
    |--------------------------------------------------------------------------
    */

    $cnpjBanco = $cnpj === '' ? null : $cnpj;
    $telefoneBanco = $telefone === '' ? null : $telefone;
    $emailBanco = $email === '' ? null : $email;
    $enderecoBanco = $endereco === '' ? null : $endereco;


    /*
    |--------------------------------------------------------------------------
    | Insere fornecedor
    |--------------------------------------------------------------------------
    */

    $sql = 'INSERT INTO fornecedores
            (farmacia_id, nome, cnpj, telefone, email, endereco)
            VALUES (?, ?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'isssss',
        $farmaciaId,
        $nome,
        $cnpjBanco,
        $telefoneBanco,
        $emailBanco,
        $enderecoBanco
    );


    if (mysqli_stmt_execute($stmt)) {

        $id = mysqli_insert_id($conexao);

        registrarAuditoria(
            $conexao,
            'cadastrar',
            'fornecedor',
            $id,
            'Fornecedor ' . $nome . ' cadastrado'
        );

        header('Location: fornecedores.php?ok=1');
        exit;

    } else {

        header('Location: fornecedores.php?erro=cadastro');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Ativar / Desativar fornecedor
|--------------------------------------------------------------------------
*/

if (isset($_GET['status'], $_GET['id'])) {

    $id = (int) $_GET['id'];

    $ativo = $_GET['status'] === 'ativar' ? 1 : 0;

    $stmt = mysqli_prepare(
        $conexao,
        'UPDATE fornecedores
         SET ativo = ?
         WHERE id = ?
         AND farmacia_id = ?'
    );

    mysqli_stmt_bind_param(
        $stmt,
        'iii',
        $ativo,
        $id,
        $farmaciaId
    );

    mysqli_stmt_execute($stmt);

    registrarAuditoria(
        $conexao,
        $ativo ? 'ativar' : 'desativar',
        'fornecedor',
        $id,
        'Status do fornecedor alterado'
    );

    header('Location: fornecedores.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Lista de fornecedores
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conexao,
    'SELECT *
     FROM fornecedores
     WHERE farmacia_id = ?
     ORDER BY nome'
);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$fornecedores = mysqli_fetch_all(
    $resultado,
    MYSQLI_ASSOC
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Fornecedores'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | COR PRINCIPAL VERMELHA
        |--------------------------------------------------------------------------
        */

        :root {
            --cor-vermelha: #e52b38;
            --cor-vermelha-hover: #c91f2d;
            --cor-vermelha-clara: rgba(229, 43, 56, 0.25);
        }


        /*
        |--------------------------------------------------------------------------
        | Botão Cadastrar - Vermelho
        |--------------------------------------------------------------------------
        */

        .btn-cadastrar-fornecedor {
            background-color: var(--cor-vermelha) !important;
            border-color: var(--cor-vermelha) !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-cadastrar-fornecedor:hover {
            background-color: var(--cor-vermelha-hover) !important;
            border-color: var(--cor-vermelha-hover) !important;
            color: #ffffff !important;
        }

        .btn-cadastrar-fornecedor:focus,
        .btn-cadastrar-fornecedor:active {
            background-color: var(--cor-vermelha-hover) !important;
            border-color: var(--cor-vermelha-hover) !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem var(--cor-vermelha-clara) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Botão Histórico - Vermelho
        |--------------------------------------------------------------------------
        */

        .btn-historico-fornecedor {
            color: var(--cor-vermelha) !important;
            border-color: var(--cor-vermelha) !important;
            background-color: transparent !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-historico-fornecedor:hover {
            color: #ffffff !important;
            background-color: var(--cor-vermelha) !important;
            border-color: var(--cor-vermelha) !important;
        }

        .btn-historico-fornecedor:focus,
        .btn-historico-fornecedor:active {
            color: #ffffff !important;
            background-color: var(--cor-vermelha-hover) !important;
            border-color: var(--cor-vermelha-hover) !important;
            box-shadow: 0 0 0 0.2rem var(--cor-vermelha-clara) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Foco dos campos - Vermelho
        |
        | Isso remove o contorno azul que aparecia ao clicar
        | nos campos.
        |--------------------------------------------------------------------------
        */

        .form-control:focus,
        .form-select:focus {
            border-color: var(--cor-vermelha) !important;
            box-shadow: 0 0 0 0.2rem var(--cor-vermelha-clara) !important;
            outline: none !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Foco específico do CNPJ e telefone
        |--------------------------------------------------------------------------
        */

        #cnpj:focus,
        #telefone:focus {
            border-color: var(--cor-vermelha) !important;
            box-shadow: 0 0 0 0.2rem var(--cor-vermelha-clara) !important;
            outline: none !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Campos CNPJ e telefone
        |--------------------------------------------------------------------------
        */

        #cnpj,
        #telefone {
            font-variant-numeric: tabular-nums;
        }


        /*
        |--------------------------------------------------------------------------
        | Cor do texto dos labels
        |--------------------------------------------------------------------------
        */

        .form-label {
            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | Botão Editar - mantém aparência neutra
        |--------------------------------------------------------------------------
        */

        .btn-editar-fornecedor {
            transition: all 0.2s ease;
        }

    </style>

</head>


<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'fornecedores'); ?>


<main class="container py-4">


    <!-- =========================================================
         CABEÇALHO DA PÁGINA
    ========================================================== -->

    <section class="card card-brand rounded-4 p-4 mb-4">

        <h2 class="h3 fw-bold">
            FORNECEDORES
        </h2>

        <p class="mb-0">
            Cadastre os fornecedores utilizados pela sua farmácia.
        </p>

    </section>


    <!-- =========================================================
         MENSAGENS
    ========================================================== -->

    <?php if (isset($_GET['editado'])) { ?>

        <div class="alert alert-success">
            Fornecedor atualizado com sucesso.
        </div>

    <?php } ?>


    <?php if (isset($_GET['ok'])) { ?>

        <div class="alert alert-success">
            Fornecedor cadastrado com sucesso.
        </div>

    <?php } ?>


    <?php if (isset($_GET['erro'])) { ?>

        <?php if ($_GET['erro'] === 'nome') { ?>

            <div class="alert alert-danger">
                Informe o nome do fornecedor.
            </div>

        <?php } elseif ($_GET['erro'] === 'cnpj') { ?>

            <div class="alert alert-danger">
                O CNPJ deve ter 14 números.
            </div>

        <?php } elseif ($_GET['erro'] === 'cnpj_existente') { ?>

            <div class="alert alert-danger">
                Já existe um fornecedor cadastrado com esse CNPJ.
            </div>

        <?php } elseif ($_GET['erro'] === 'telefone') { ?>

            <div class="alert alert-danger">
                O telefone deve ter 10 ou 11 números.
            </div>

        <?php } elseif ($_GET['erro'] === 'email') { ?>

            <div class="alert alert-danger">
                Informe um e-mail válido.
            </div>

        <?php } elseif ($_GET['erro'] === 'cadastro') { ?>

            <div class="alert alert-danger">
                Não foi possível cadastrar o fornecedor.
            </div>

        <?php } else { ?>

            <div class="alert alert-danger">
                Não foi possível realizar a operação.
            </div>

        <?php } ?>

    <?php } ?>


    <!-- =========================================================
         NOVO FORNECEDOR
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h3 class="h5 fw-bold mb-3">
            NOVO FORNECEDOR
        </h3>


        <form method="POST" class="row g-3">


            <!-- NOME -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label"
                    for="nome"
                >
                    Nome *
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="150"
                    autocomplete="organization"
                    required
                >

            </div>


            <!-- CNPJ -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label"
                    for="cnpj"
                >
                    CNPJ
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="cnpj"
                    name="cnpj"
                    placeholder="00.000.000/0000-00"
                    maxlength="18"
                    inputmode="numeric"
                    autocomplete="off"
                >

            </div>


            <!-- TELEFONE -->

            <div class="col-12 col-md-4">

                <label
                    class="form-label"
                    for="telefone"
                >
                    Telefone
                </label>

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

            <div class="col-12 col-md-4">

                <label
                    class="form-label"
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
                    autocomplete="email"
                >

            </div>


            <!-- ENDEREÇO -->

            <div class="col-12 col-md-4">

                <label
                    class="form-label"
                    for="endereco"
                >
                    Endereço
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="endereco"
                    name="endereco"
                    maxlength="255"
                    autocomplete="street-address"
                >

            </div>


            <!-- BOTÃO CADASTRAR -->

            <div class="col-12">

                <button
                    class="btn btn-cadastrar-fornecedor rounded-pill px-4"
                    type="submit"
                >
                    CADASTRAR
                </button>

            </div>

        </form>

    </section>


    <!-- =========================================================
         FORNECEDORES CADASTRADOS
    ========================================================== -->

    <section class="card shadow-sm rounded-4 p-3">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">


                <!-- CABEÇALHO -->

                <thead>

                    <tr>

                        <th>
                            Nome
                        </th>

                        <th>
                            CNPJ
                        </th>

                        <th>
                            Telefone
                        </th>

                        <th>
                            E-mail
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Ação
                        </th>

                    </tr>

                </thead>


                <!-- CORPO -->

                <tbody>


                <?php if (!$fornecedores) { ?>

                    <tr>

                        <td
                            colspan="6"
                            class="text-center text-secondary py-4"
                        >
                            Nenhum fornecedor cadastrado.
                        </td>

                    </tr>

                <?php } ?>


                <?php foreach ($fornecedores as $f) { ?>

                    <tr>


                        <!-- NOME -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $f['nome']
                                );
                                ?>
                            </strong>

                        </td>


                        <!-- CNPJ -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                !empty($f['cnpj'])
                                    ? formatarCNPJ($f['cnpj'])
                                    : '-'
                            );

                            ?>

                        </td>


                        <!-- TELEFONE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                !empty($f['telefone'])
                                    ? formatarTelefone($f['telefone'])
                                    : '-'
                            );

                            ?>

                        </td>


                        <!-- E-MAIL -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $f['email'] ?: '-'
                            );

                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span
                                class="badge <?php echo $f['ativo'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"
                            >

                                <?php
                                echo $f['ativo']
                                    ? 'Ativo'
                                    : 'Inativo';
                                ?>

                            </span>

                        </td>


                        <!-- AÇÕES -->

                        <td class="d-flex gap-2 flex-wrap">


                            <!-- EDITAR -->

                            <a
                                class="btn btn-sm btn-outline-secondary btn-editar-fornecedor rounded-pill"
                                href="editarfornecedor.php?id=<?php echo (int) $f['id']; ?>"
                            >
                                EDITAR
                            </a>


                            <!-- HISTÓRICO -->

                            <a
                                class="btn btn-sm btn-historico-fornecedor rounded-pill"
                                href="historicofornecedor.php?id=<?php echo (int) $f['id']; ?>"
                            >
                                HISTÓRICO
                            </a>


                            <!-- ATIVAR / DESATIVAR -->

                            <a
                                class="btn btn-sm btn-outline-danger rounded-pill"
                                href="fornecedores.php?id=<?php echo (int) $f['id']; ?>&status=<?php echo $f['ativo'] ? 'desativar' : 'ativar'; ?>"
                            >

                                <?php
                                echo $f['ativo']
                                    ? 'DESATIVAR'
                                    : 'ATIVAR';
                                ?>

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


<!-- =========================================================
     MÁSCARA CNPJ E TELEFONE
========================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {


    /*
    |--------------------------------------------------------------------------
    | CNPJ
    |--------------------------------------------------------------------------
    */

    const campoCNPJ = document.getElementById('cnpj');

    if (campoCNPJ) {

        campoCNPJ.addEventListener('input', function () {

            // Mantém somente números
            let valor = this.value.replace(/\D/g, '');

            // Limita a 14 números
            valor = valor.substring(0, 14);


            /*
            |--------------------------------------------------------------------------
            | Aplica máscara:
            | 00.000.000/0000-00
            |--------------------------------------------------------------------------
            */

            if (valor.length > 12) {

                valor = valor.replace(
                    /^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2}).*/,
                    '$1.$2.$3/$4-$5'
                );

            } else if (valor.length > 8) {

                valor = valor.replace(
                    /^(\d{2})(\d{3})(\d{3})(\d{1,4}).*/,
                    '$1.$2.$3/$4'
                );

            } else if (valor.length > 5) {

                valor = valor.replace(
                    /^(\d{2})(\d{3})(\d{1,3}).*/,
                    '$1.$2.$3'
                );

            } else if (valor.length > 2) {

                valor = valor.replace(
                    /^(\d{2})(\d{1,3}).*/,
                    '$1.$2'
                );

            }

            this.value = valor;

        });

    }


    /*
    |--------------------------------------------------------------------------
    | TELEFONE
    |--------------------------------------------------------------------------
    */

    const campoTelefone = document.getElementById('telefone');

    if (campoTelefone) {

        campoTelefone.addEventListener('input', function () {

            // Mantém somente números
            let valor = this.value.replace(/\D/g, '');

            // Limita a 11 números
            valor = valor.substring(0, 11);


            /*
            |--------------------------------------------------------------------------
            | Celular com 11 números
            | (00) 00000-0000
            |--------------------------------------------------------------------------
            */

            if (valor.length > 10) {

                valor = valor.replace(
                    /^(\d{2})(\d{5})(\d{1,4}).*/,
                    '($1) $2-$3'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Telefone fixo com 10 números
            | (00) 0000-0000
            |--------------------------------------------------------------------------
            */

            else if (valor.length > 6) {

                valor = valor.replace(
                    /^(\d{2})(\d{4})(\d{1,4}).*/,
                    '($1) $2-$3'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Depois do DDD
            |--------------------------------------------------------------------------
            */

            else if (valor.length > 2) {

                valor = valor.replace(
                    /^(\d{2})(\d{1,5}).*/,
                    '($1) $2'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Somente DDD
            |--------------------------------------------------------------------------
            */

            else if (valor.length > 0) {

                valor = '(' + valor;

            }

            this.value = valor;

        });

    }

});

</script>


</body>
</html>
