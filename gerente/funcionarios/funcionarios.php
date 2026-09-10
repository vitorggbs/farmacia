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

            <!-- NOME -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="nome"
                >
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


            <!-- CPF -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="cpf"
                >
                    CPF
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="cpf"
                    name="cpf"
                    maxlength="14"
                    placeholder="000.000.000-00"
                    inputmode="numeric"
                    autocomplete="off"
                    required
                >

            </div>


            <!-- NASCIMENTO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="nascimento"
                >
                    Nascimento
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="nascimento"
                    name="nascimento"
                    maxlength="10"
                    placeholder="DD/MM/AAAA"
                    inputmode="numeric"
                    autocomplete="off"
                    required
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

                <input
                    class="form-control"
                    type="text"
                    id="telefone"
                    name="telefone"
                    maxlength="15"
                    placeholder="(00) 00000-0000"
                    inputmode="numeric"
                    autocomplete="off"
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
                    placeholder="Digite o seu e-mail"
                >

            </div>


            <!-- ENDEREÇO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="endereco"
                >
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


            <!-- ADMISSÃO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="admissao"
                >
                    Admissão
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="admissao"
                    name="admissao"
                    maxlength="10"
                    placeholder="DD/MM/AAAA"
                    inputmode="numeric"
                    autocomplete="off"
                    required
                >

            </div>


            <!-- SALÁRIO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="salario"
                >
                    Salário
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="salario"
                    name="salario"
                    placeholder="R$ 0,00"
                    inputmode="decimal"
                    autocomplete="off"
                >

            </div>


            <!-- HORÁRIO -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="horario"
                >
                    Horário
                </label>

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


            <!-- LOGIN -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="login"
                >
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


            <!-- SENHA -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="senha"
                >
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


            <!-- BOTÃO -->

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


    <!-- LISTAGEM DOS BALCONISTAS -->

    <section class="card shadow-sm rounded-4 p-4">

        <h2 class="h4 fw-bold">
            BALCONISTAS
        </h2>

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

                            <span
                                class="badge rounded-pill <?= $u['ativo']
                                    ? 'text-bg-success'
                                    : 'text-bg-secondary' ?>"
                            >
                                <?= $u['ativo']
                                    ? 'Ativo'
                                    : 'Inativo' ?>
                            </span>

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

                                <button
                                    class="btn btn-sm <?= $u['ativo']
                                        ? 'btn-danger'
                                        : 'btn-primary' ?> rounded-pill"
                                    type="submit"
                                >
                                    <?= $u['ativo']
                                        ? 'DESATIVAR'
                                        : 'ATIVAR' ?>
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

const $ = id => document.getElementById(id);

const cpf = $('cpf');
const nascimento = $('nascimento');
const admissao = $('admissao');
const telefone = $('telefone');
const salario = $('salario');
const horario = $('horario');
const horarioValor = $('horarioValor');
const formulario = $('formCadastro');

let hora = '';


/* =========================
   SOMENTE NÚMEROS
   ========================= */

function somenteNumeros(valor, limite)
{
    return valor
        .replace(/\D/g, '')
        .slice(0, limite);
}


/* =========================
   MÁSCARA DE DATA
   =========================

   Exemplos:

   4       → 04
   5       → 05
   6       → 06
   7       → 07
   8       → 08
   9       → 09

   Formato final:

   DD/MM/AAAA
   ========================= */

function aplicarMascaraData(campo)
{
    campo.addEventListener('input', () => {

        let valor = campo.value
            .replace(/\D/g, '')
            .slice(0, 8);


        /*
         * Se o primeiro número digitado for
         * de 4 até 9, acrescenta o zero.
         *
         * Exemplo:
         * 4 → 04
         * 9 → 09
         */

        if (
            valor.length === 1 &&
            /[4-9]/.test(valor)
        ) {
            valor = '0' + valor;
        }


        /*
         * Limitar o dia entre 01 e 31.
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
         * Quando o usuário digitar o terceiro
         * número, ele será o primeiro número
         * do mês.
         *
         * Exemplo:
         * 044 → 04/04
         * 049 → 04/09
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
         * Limitar o mês entre 01 e 12.
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
         * Formatar como DD/MM/AAAA.
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

        campo.value = valor;

    });
}


/* Aplicar a máscara nos campos de data */

aplicarMascaraData(nascimento);
aplicarMascaraData(admissao);


/* =========================
   VALIDAR DATA COMPLETA
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


    /*
     * Exigir:
     * Dia com 2 dígitos;
     * Mês com 2 dígitos;
     * Ano com 4 dígitos.
     */

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


    /*
     * Verificar os limites básicos.
     */

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


    /*
     * Verificar se a data realmente existe.
     *
     * Exemplos recusados:
     * 31/02/2024
     * 31/04/2024
     * 29/02/2023
     */

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
   CPF
   ========================= */

cpf.addEventListener('input', () => {

    let valor = somenteNumeros(
        cpf.value,
        11
    );

    if (valor.length > 9) {

        valor = valor.replace(
            /(\d{3})(\d{3})(\d{3})(\d{1,2})/,
            '$1.$2.$3-$4'
        );

    } else if (valor.length > 6) {

        valor = valor.replace(
            /(\d{3})(\d{3})(\d{1,3})/,
            '$1.$2.$3'
        );

    } else if (valor.length > 3) {

        valor = valor.replace(
            /(\d{3})(\d{1,3})/,
            '$1.$2'
        );
    }

    cpf.value = valor;

});


/* =========================
   TELEFONE
   ========================= */

telefone.addEventListener('input', () => {

    let valor = somenteNumeros(
        telefone.value,
        11
    );

    if (valor.length > 7) {

        valor = valor.replace(
            /(\d{2})(\d{5})(\d{1,4})/,
            '($1) $2-$3'
        );

    } else if (valor.length > 2) {

        valor = valor.replace(
            /(\d{2})(\d+)/,
            '($1) $2'
        );

    } else if (valor) {

        valor = `(${valor}`;

    }

    telefone.value = valor;

});


/* =========================
   SALÁRIO
   ========================= */

salario.addEventListener('input', () => {

    const valor = somenteNumeros(
        salario.value,
        15
    );

    if (!valor) {

        salario.value = '';

        return;
    }

    let [reais, centavos] =
        (Number(valor) / 100)
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

    let valor = somenteNumeros(
        horario.value,
        8
    );

    [0, 2, 4, 6].forEach(posicao => {

        valor = corrigirInicio(
            valor,
            posicao
        );

    });

    hora = validarHorario(valor);

    horario.value = formatarHorario(hora);

    horarioValor.value = horario.value;

});


function corrigirInicio(valor, posicao)
{
    if (valor.length !== posicao + 1) {
        return valor;
    }

    const numero = valor[posicao];

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


        /*
         * Horas de 20 até 23.
         */

        if (
            (i === 1 || i === 5) &&
            valor[i - 1] === '2' &&
            !/[0-3]/.test(numero)
        ) {
            continue;
        }


        /*
         * Minutos de 00 até 59.
         */

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
   ENVIO DO FORMULÁRIO
   ========================= */

formulario.addEventListener('submit', event => {


    /*
     * Validar nascimento.
     */

    if (!dataValida(nascimento.value)) {

        event.preventDefault();

        alert(
            'Informe uma data de nascimento válida no formato DD/MM/AAAA.'
        );

        nascimento.focus();

        return;
    }


    /*
     * Validar admissão.
     */

    if (!dataValida(admissao.value)) {

        event.preventDefault();

        alert(
            'Informe uma data de admissão válida no formato DD/MM/AAAA.'
        );

        admissao.focus();

        return;
    }


    /*
     * Converter as datas para o formato
     * aceito pelo MySQL.
     */

    nascimento.value =
        converterDataParaMySQL(nascimento.value);

    admissao.value =
        converterDataParaMySQL(admissao.value);


    /*
     * Atualizar o horário oculto.
     */

    horarioValor.value =
        formatarHorario(hora);


    /*
     * Verificar se o horário está completo.
     */

    if (
        hora &&
        hora.length !== 8
    ) {

        event.preventDefault();

        alert(
            'Digite o horário completo no formato 08:00 às 17:00.'
        );

        horario.focus();

        return;
    }


    /*
     * Verificar se o horário final é maior
     * que o horário inicial.
     */

    if (hora.length === 8) {

        const inicio =
            Number(hora.slice(0, 2)) * 60 +
            Number(hora.slice(2, 4));

        const fim =
            Number(hora.slice(4, 6)) * 60 +
            Number(hora.slice(6, 8));

        if (fim <= inicio) {

            event.preventDefault();

            alert(
                'O horário final deve ser maior que o horário inicial.'
            );

            horario.focus();

            return;
        }
    }


    /*
     * Converter salário para formato decimal.
     */

    salario.value =
        salario.value
            .replace('R$', '')
            .replace(/\./g, '')
            .replace(',', '.')
            .trim();

});

</script>

</body>
</html>