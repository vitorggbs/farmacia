<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

$sql = "SELECT 
            f.id,
            f.nome,
            f.cnpj,
            f.telefone,
            f.endereco,
            f.ativo,
            COUNT(u.id) AS usuarios,
            COALESCE(SUM(u.cargo = 'gerente' AND u.ativo = 1), 0) AS gerentes
        FROM farmacias f
        LEFT JOIN usuarios u ON u.farmacia_id = f.id
        GROUP BY f.id, f.nome, f.cnpj, f.telefone, f.endereco, f.ativo
        ORDER BY f.id DESC";

$farmacias = mysqli_query($conexao, $sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php recursosCabeca('Farmácias'); ?>
</head>

<body>

<?php cabecalhoAdmin('FarmaCerta - Farmácias'); ?>

<main class="container py-4">

    <?php if (isset($_GET['ok'])) { ?>

        <div class="alert alert-success" role="alert">
            Operação realizada com sucesso.
        </div>

    <?php } ?>


    <?php if (isset($_GET['erro'])) { ?>

        <div class="alert alert-danger" role="alert">

            <?php

            echo $_GET['erro'] === 'login'
                ? 'Esse login de gerente já está sendo usado.'
                : 'Não foi possível realizar a operação.';

            ?>

        </div>

    <?php } ?>


    <!-- ==========================================
         FARMÁCIAS CADASTRADAS
    =========================================== -->

    <section class="card shadow-sm rounded-4 p-4 mb-4">

        <h2 class="h4 fw-bold mb-3">
            FARMÁCIAS CADASTRADAS
        </h2>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Nome</th>
                        <th>CNPJ</th>
                        <th>Telefone</th>
                        <th>Usuários</th>
                        <th>Gerentes</th>
                        <th>Status</th>
                        <th>Ações</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!$farmacias || mysqli_num_rows($farmacias) === 0) { ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center text-secondary py-4"
                        >
                            Nenhuma farmácia cadastrada.
                        </td>

                    </tr>

                <?php } else { ?>


                    <?php while ($farmacia = mysqli_fetch_assoc($farmacias)) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $farmacia['id']; ?>
                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $farmacia['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $farmacia['cnpj'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $farmacia['telefone'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </td>


                            <td>
                                <?php echo (int) $farmacia['usuarios']; ?>
                            </td>


                            <td>
                                <?php echo (int) $farmacia['gerentes']; ?>
                            </td>


                            <td>

                                <span
                                    class="badge rounded-pill <?php echo $farmacia['ativo'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"
                                >

                                    <?php

                                    echo $farmacia['ativo']
                                        ? 'Ativa'
                                        : 'Inativa';

                                    ?>

                                </span>

                            </td>


                            <td>

                                <div class="d-flex flex-wrap gap-2">

                                    <a
                                        class="btn btn-sm btn-primary rounded-pill"
                                        href="editarfarmacia.php?id=<?php echo (int) $farmacia['id']; ?>"
                                    >
                                        EDITAR
                                    </a>


                                    <a
                                        class="btn btn-sm rounded-pill <?php echo $farmacia['ativo'] ? 'btn-danger' : 'btn-primary'; ?>"
                                        href="alterarstatus.php?id=<?php echo (int) $farmacia['id']; ?>"
                                        onclick="return confirm('Deseja alterar o status desta farmácia?')"
                                    >

                                        <?php

                                        echo $farmacia['ativo']
                                            ? 'DESATIVAR'
                                            : 'ATIVAR';

                                        ?>

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ==========================================
         CADASTRAR NOVA FARMÁCIA
    =========================================== -->

    <section
        class="card card-brand rounded-4 p-4"
        id="cadastrar"
    >

        <h2 class="h3 fw-bold">
            CADASTRAR NOVA FARMÁCIA
        </h2>


        <p>
            Cadastre a farmácia e o primeiro gerente dela.
        </p>


        <form
            action="cadastrarfarmacia.php"
            method="POST"
            class="row g-3"
        >


            <!-- ==================================
                 NOME DA FARMÁCIA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="nome"
                >
                    NOME DA FARMÁCIA *
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Digite o nome da farmácia"
                    required
                >

            </div>



            <!-- ==================================
                 CNPJ
                 MANTIDO COMO ESTAVA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="cnpj"
                >
                    CNPJ
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="cnpj"
                    name="cnpj"
                    maxlength="18"
                    inputmode="numeric"
                    placeholder="00.000.000/0000-00"
                    oninput="formatarCNPJ(this)"
                >

            </div>



            <!-- ==================================
                 TELEFONE
                 MANTIDO COMO ESTAVA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="telefone"
                >
                    TELEFONE
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="telefone"
                    name="telefone"
                    maxlength="15"
                    inputmode="numeric"
                    placeholder="(00) 00000-0000"
                    oninput="formatarTelefone(this)"
                >

            </div>



            <!-- ==================================
                 ENDEREÇO
            =================================== -->

            <div class="col-12">

                <label
                    class="form-label fw-bold"
                    for="endereco"
                >
                    ENDEREÇO
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="endereco"
                    name="endereco"
                    placeholder="Digite o endereço da farmácia"
                >

            </div>



            <!-- ==================================
                 PRIMEIRO GERENTE
            =================================== -->

            <div class="col-12">

                <hr class="border-light opacity-50">


                <h3 class="h5 fw-bold">
                    PRIMEIRO GERENTE
                </h3>

            </div>



            <!-- ==================================
                 NOME DO GERENTE
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="gerente_nome"
                >
                    NOME DO GERENTE *
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="gerente_nome"
                    name="gerente_nome"
                    placeholder="Digite o nome do gerente"
                    required
                >

            </div>



            <!-- ==================================
                 CPF
                 MANTIDO COMO ESTAVA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="gerente_cpf"
                >
                    CPF
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="gerente_cpf"
                    name="gerente_cpf"
                    maxlength="14"
                    inputmode="numeric"
                    placeholder="000.000.000-00"
                    oninput="formatarCPF(this)"
                >

            </div>



            <!-- ==================================
                 LOGIN
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="gerente_login"
                >
                    LOGIN *
                </label>


                <input
                    class="form-control"
                    type="text"
                    id="gerente_login"
                    name="gerente_login"
                    placeholder="Digite o login do gerente"
                    required
                >

            </div>



            <!-- ==================================
                 SENHA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="gerente_senha"
                >
                    SENHA *
                </label>


                <input
                    class="form-control"
                    type="password"
                    id="gerente_senha"
                    name="gerente_senha"
                    minlength="6"
                    placeholder="Digite a senha do gerente"
                    required
                >

            </div>



            <!-- ==================================
                 BOTÃO
            =================================== -->

            <div class="col-12">

                <button
                    class="btn btn-outline-light rounded-pill fw-bold px-4"
                    type="submit"
                >
                    CADASTRAR FARMÁCIA
                </button>

            </div>

        </form>

    </section>

</main>


<?php recursosRodape(); ?>



<script>

/* ==========================================
   SOMENTE NÚMEROS
========================================== */

function somenteNumeros(valor) {

    return valor.replace(/\D/g, '');

}



/* ==========================================
   FORMATAÇÃO DO CPF
========================================== */

function formatarCPF(input) {

    let valor = somenteNumeros(input.value);

    valor = valor.substring(0, 11);


    if (valor.length > 9) {

        valor = valor.replace(
            /^(\d{3})(\d{3})(\d{3})(\d{0,2}).*/,
            '$1.$2.$3-$4'
        );

    } else if (valor.length > 6) {

        valor = valor.replace(
            /^(\d{3})(\d{3})(\d{0,3}).*/,
            '$1.$2.$3'
        );

    } else if (valor.length > 3) {

        valor = valor.replace(
            /^(\d{3})(\d{0,3}).*/,
            '$1.$2'
        );

    }


    input.value = valor;

}



/* ==========================================
   FORMATAÇÃO DO TELEFONE
========================================== */

function formatarTelefone(input) {

    let valor = somenteNumeros(input.value);

    valor = valor.substring(0, 11);


    if (valor.length > 10) {

        valor = valor.replace(
            /^(\d{2})(\d{5})(\d{0,4}).*/,
            '($1) $2-$3'
        );

    } else if (valor.length > 6) {

        valor = valor.replace(
            /^(\d{2})(\d{4})(\d{0,4}).*/,
            '($1) $2-$3'
        );

    } else if (valor.length > 2) {

        valor = valor.replace(
            /^(\d{2})(\d{0,5}).*/,
            '($1) $2'
        );

    }


    input.value = valor;

}



/* ==========================================
   FORMATAÇÃO DO CNPJ
========================================== */

function formatarCNPJ(input) {

    let valor = somenteNumeros(input.value);

    valor = valor.substring(0, 14);


    if (valor.length > 12) {

        valor = valor.replace(
            /^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/,
            '$1.$2.$3/$4-$5'
        );

    } else if (valor.length > 8) {

        valor = valor.replace(
            /^(\d{2})(\d{3})(\d{3})(\d{0,4}).*/,
            '$1.$2.$3/$4'
        );

    } else if (valor.length > 5) {

        valor = valor.replace(
            /^(\d{2})(\d{3})(\d{0,3}).*/,
            '$1.$2.$3'
        );

    } else if (valor.length > 2) {

        valor = valor.replace(
            /^(\d{2})(\d{0,3}).*/,
            '$1.$2'
        );

    }


    input.value = valor;

}

</script>

</body>
</html>
