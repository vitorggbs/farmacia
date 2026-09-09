<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../../autenticacao.php';
require_once __DIR__ . '/../conexaoDB.php';
require_once __DIR__ . '/../../cabecalho.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];


/* =========================
   BUSCAR BALCONISTAS
   ========================= */

$sql = "
    SELECT
        id,
        nome,
        cpf,
        telefone,
        email,
        login,
        ativo
    FROM usuarios
    WHERE cargo = 'balconista'
    AND farmacia_id = $farmaciaId
    ORDER BY nome ASC
";

$lista = mysqli_query($conexao, $sql);


/* =========================
   ESCAPAR HTML
   ========================= */

function e($valor)
{
    return htmlspecialchars(
        $valor ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Funcionários'); ?>
</head>

<body>

<?php cabecalho('Funcionários', 'gerente', 'funcionarios'); ?>

<main class="container py-4">

    <section class="card card-brand rounded-4 p-4 mb-4" id="cadastrar">

        <h2 class="h3 fw-bold">CADASTRAR BALCONISTA</h2>

        <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-light text-success fw-bold">
                Funcionário cadastrado!
            </div>
        <?php endif; ?>

        <form
            action="cadastrarfuncionario.php"
            method="POST"
            id="formCadastro"
            class="row g-3"
        >

            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="nome">
                    Nome completo
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Digite o seu nome completo"
                    required
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="cpf">
                    CPF
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->
                <input
                    class="form-control"
                    type="text"
                    id="cpf"
                    name="cpf"
                    maxlength="14"
                    placeholder="000.000.000-00"
                    inputmode="numeric"
                    required
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="nascimento">
                    Nascimento
                </label>

                <input
                    class="form-control"
                    type="date"
                    id="nascimento"
                    name="nascimento"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="telefone">
                    Telefone
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->
                <input
                    class="form-control"
                    type="text"
                    id="telefone"
                    name="telefone"
                    maxlength="15"
                    placeholder="(00) 00000-0000"
                    inputmode="numeric"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="email">
                    E-mail
                </label>

                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Digite o seu e-mail"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="endereco">
                    Endereço
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="endereco"
                    name="endereco"
                    placeholder="Digite o seu endereço"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="admissao">
                    Admissão
                </label>

                <input
                    class="form-control"
                    type="date"
                    id="admissao"
                    name="admissao"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="salario">
                    Salário
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->
                <input
                    class="form-control"
                    type="text"
                    id="salario"
                    name="salario"
                    placeholder="R$ 0,00"
                    inputmode="decimal"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="horario">
                    Horário
                </label>

                <!-- MANTIDO DO CÓDIGO ORIGINAL -->
                <input
                    class="form-control"
                    type="text"
                    id="horario"
                    maxlength="14"
                    placeholder="08:00 às 17:00"
                    autocomplete="off"
                    inputmode="numeric"
                >

                <input
                    type="hidden"
                    id="horarioValor"
                    name="horario"
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="login">
                    Login
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="login"
                    name="login"
                    placeholder="Digite o seu login"
                    required
                >
            </div>


            <div class="col-12 col-md-6">
                <label class="form-label fw-bold" for="senha">
                    Senha
                </label>

                <input
                    class="form-control"
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Digite a sua senha"
                    minlength="6"
                    required
                >
            </div>


            <div class="col-12">
                <button
                    class="btn btn-outline-light rounded-pill fw-bold"
                    type="submit"
                >
                    CADASTRAR
                </button>
            </div>

        </form>

    </section>


    <section class="card shadow-sm rounded-4 p-4">

        <h2 class="h4 fw-bold">BALCONISTAS</h2>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Contato</th>
                        <th>Login</th>
                        <th>Situação</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($u = mysqli_fetch_assoc($lista)): ?>

                    <tr>

                        <td><?= e($u['nome']) ?></td>

                        <td><?= e($u['cpf']) ?></td>

                        <td>
                            <?= e(($u['telefone'] ?: '-') . ' / ' . ($u['email'] ?: '-')) ?>
                        </td>

                        <td><?= e($u['login']) ?></td>

                        <td>
                            <span class="badge rounded-pill <?= $u['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>

                        <td>

                            <form action="alterarstatus.php" method="POST">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $u['id'] ?>"
                                >

                                <button
                                    class="btn btn-sm <?= $u['ativo'] ? 'btn-danger' : 'btn-primary' ?> rounded-pill"
                                    type="submit"
                                >
                                    <?= $u['ativo'] ? 'DESATIVAR' : 'ATIVAR' ?>
                                </button>

                            </form>

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

const $ = id =>
    document.getElementById(id);

const cpf = $('cpf');
const telefone = $('telefone');
const salario = $('salario');
const horario = $('horario');
const horarioValor = $('horarioValor');
const formulario = $('formCadastro');

let hora = '';


/* =========================
   NÚMEROS
   ========================= */

const numeros = (valor, limite) =>
    valor.replace(/\D/g, '').slice(0, limite);


/* =========================
   CPF
   ========================= */

cpf.addEventListener('input', () => {

    let v = numeros(cpf.value, 11);

    if (v.length > 9) {

        v = v.replace(
            /(\d{3})(\d{3})(\d{3})(\d{1,2})/,
            '$1.$2.$3-$4'
        );

    } else if (v.length > 6) {

        v = v.replace(
            /(\d{3})(\d{3})(\d{1,3})/,
            '$1.$2.$3'
        );

    } else if (v.length > 3) {

        v = v.replace(
            /(\d{3})(\d{1,3})/,
            '$1.$2'
        );

    }

    cpf.value = v;

});


/* =========================
   TELEFONE
   ========================= */

telefone.addEventListener('input', () => {

    let v = numeros(
        telefone.value,
        11
    );

    if (v.length > 7) {

        v = v.replace(
            /(\d{2})(\d{5})(\d{1,4})/,
            '($1) $2-$3'
        );

    } else if (v.length > 2) {

        v = v.replace(
            /(\d{2})(\d+)/,
            '($1) $2'
        );

    } else if (v) {

        v = `(${v}`;

    }

    telefone.value = v;

});


/* =========================
   SALÁRIO
   ========================= */

salario.addEventListener('input', () => {

    const v =
        numeros(salario.value, 15);

    if (!v) {

        salario.value = '';

        return;

    }

    let [reais, centavos] =
        (Number(v) / 100)
        .toFixed(2)
        .split('.');

    reais = reais.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        '.'
    );

    salario.value =
        `R$ ${reais},${centavos}`;

});


/* =========================
   HORÁRIO
   ========================= */

horario.addEventListener('input', () => {

    let v =
        numeros(horario.value, 8);

    [0, 2, 4, 6].forEach(posicao => {

        v = corrigirInicio(
            v,
            posicao
        );

    });

    hora = validarHorario(v);

    horario.value =
        formatarHorario(hora);

    horarioValor.value =
        horario.value;

});


function corrigirInicio(valor, posicao)
{

    if (
        valor.length !== posicao + 1
    ) {

        return valor;

    }

    const numero = valor[posicao];

    const minuto =
        posicao === 2 ||
        posicao === 6;

    const horaCampo =
        posicao === 0 ||
        posicao === 4;


    if (

        /[6-9]/.test(numero)

        ||

        (
            horaCampo &&
            /[3-5]/.test(numero)
        )

    ) {

        return (

            valor.slice(0, posicao) +

            '0' +

            numero +

            valor.slice(posicao + 1)

        );

    }

    return valor;

}


/* =========================
   VALIDAR HORÁRIO
   ========================= */

function validarHorario(valor)
{

    let resultado = '';

    for (
        let i = 0;
        i < valor.length;
        i++
    ) {

        const numero = valor[i];


        /* 20 até 23 */

        if (

            (i === 1 || i === 5)

            &&

            valor[i - 1] === '2'

            &&

            !/[0-3]/.test(numero)

        ) {

            continue;

        }


        /* Minutos 00 até 59 */

        if (

            (i === 2 || i === 6)

            &&

            !/[0-5]/.test(numero)

        ) {

            continue;

        }

        resultado += numero;

    }

    return resultado.slice(0, 8);

}


/* =========================
   FORMATAR HORÁRIO
   ========================= */

function formatarHorario(valor)
{

    if (valor.length <= 2) {

        return valor;

    }

    if (valor.length <= 4) {

        return (

            `${valor.slice(0, 2)}:` +

            valor.slice(2)

        );

    }

    let texto =

        `${valor.slice(0, 2)}:` +

        `${valor.slice(2, 4)} às ` +

        valor.slice(4, 6);

    if (valor.length > 6) {

        texto +=

            `:${valor.slice(6, 8)}`;

    }

    return texto;

}


/* =========================
   ENVIO
   ========================= */

formulario.addEventListener(
    'submit',
    event => {

        horarioValor.value =
            formatarHorario(hora);


        if (

            hora &&

            hora.length !== 8

        ) {

            event.preventDefault();

            horario.focus();

            return;

        }


        if (hora.length === 8) {

            const inicio =

                Number(hora.slice(0, 2)) * 60 +

                Number(hora.slice(2, 4));


            const fim =

                Number(hora.slice(4, 6)) * 60 +

                Number(hora.slice(6, 8));


            if (fim <= inicio) {

                event.preventDefault();

                horario.focus();

                return;

            }

        }


        salario.value =

            salario.value

                .replace('R$', '')

                .replace(/\./g, '')

                .replace(',', '.')

                .trim();

    }
);

</script>

</body>
</html>