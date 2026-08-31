<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../../autenticacao.php';
require_once __DIR__ . '/../conexaoDB.php';

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

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Funcionários</title>

    <link
        rel="stylesheet"
        href="../../style.css"
    >

</head>

<body>

<header class="topo">

    <img
        src="../../assets/LOGO_1.png"
        width="200"
        alt="FarmaCerta"
    >

    <h1>Funcionários</h1>

    <div class="usuario-topo">

        <span>
            <?= e($_SESSION['usuario_nome']) ?>
        </span>

        <a
            class="logout"
            href="../../logout.php"
        >
            SAIR
        </a>

    </div>

</header>


<nav class="menu">

    <a href="../iniciogerente.php">
        INÍCIO
    </a>

    <a href="../produtosgerente.php">
        PRODUTOS
    </a>

    <a
        href="funcionarios.php"
        class="ativo"
    >
        FUNCIONÁRIOS
    </a>

    <a href="../historicorecibogerente.php">
        RECIBOS
    </a>

</nav>


<main class="container">


    <!-- =========================
         CADASTRO
         ========================= -->

    <div
        class="card"
        id="cadastrar"
    >

        <h2>
            CADASTRAR BALCONISTA
        </h2>


        <?php if (isset($_GET['ok'])): ?>

            <p>
                Funcionário cadastrado!
            </p>

        <?php endif; ?>


        <form
            action="cadastrarfuncionario.php"
            method="POST"
            id="formCadastro"
        >


            <label for="nome">
                Nome completo
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                required
            >


            <label for="cpf">
                CPF
            </label>

            <input
                type="text"
                id="cpf"
                name="cpf"
                maxlength="14"
                placeholder="000.000.000-00"
                inputmode="numeric"
                required
            >


            <label for="nascimento">
                Nascimento
            </label>

            <input
                type="date"
                id="nascimento"
                name="nascimento"
            >


            <label for="telefone">
                Telefone
            </label>

            <input
                type="text"
                id="telefone"
                name="telefone"
                maxlength="15"
                placeholder="(00) 00000-0000"
                inputmode="numeric"
            >


            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
            >


            <label for="endereco">
                Endereço
            </label>

            <input
                type="text"
                id="endereco"
                name="endereco"
            >


            <label for="admissao">
                Admissão
            </label>

            <input
                type="date"
                id="admissao"
                name="admissao"
            >


            <label for="salario">
                Salário
            </label>

            <input
                type="text"
                id="salario"
                name="salario"
                placeholder="R$ 0,00"
                inputmode="decimal"
            >


            <label for="horario">
                Horário
            </label>

            <input
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


            <label for="login">
                Login
            </label>

            <input
                type="text"
                id="login"
                name="login"
                required
            >


            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                minlength="6"
                required
            >


            <button type="submit">
                CADASTRAR
            </button>

        </form>

    </div>


    <!-- =========================
         LISTA
         ========================= -->

    <section class="secao-branca">

        <h2>
            BALCONISTAS
        </h2>


        <table class="tabela">

            <thead>

                <tr>

                    <th>
                        Nome
                    </th>

                    <th>
                        CPF
                    </th>

                    <th>
                        Contato
                    </th>

                    <th>
                        Login
                    </th>

                    <th>
                        Situação
                    </th>

                    <th>
                        Ação
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php while ($u = mysqli_fetch_assoc($lista)): ?>

                <tr>

                    <td>
                        <?= e($u['nome']) ?>
                    </td>

                    <td>
                        <?= e($u['cpf']) ?>
                    </td>

                    <td>
                        <?= e(
                            ($u['telefone'] ?: '-') .
                            ' / ' .
                            ($u['email'] ?: '-')
                        ) ?>
                    </td>

                    <td>
                        <?= e($u['login']) ?>
                    </td>

                    <td>
                        <?= $u['ativo']
                            ? 'Ativo'
                            : 'Inativo'
                        ?>
                    </td>

                    <td>

                        <form
                            action="alterarstatus.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $u['id'] ?>"
                            >

                            <button type="submit">

                                <?= $u['ativo']
                                    ? 'DESATIVAR'
                                    : 'ATIVAR'
                                ?>

                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </section>

</main>


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
        /[6-9]/.test(numero) ||
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
            (i === 1 || i === 5) &&
            valor[i - 1] === '2' &&
            !/[0-3]/.test(numero)
        ) {

            continue;

        }


        /* Minutos 00 até 59 */

        if (
            (i === 2 || i === 6) &&
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