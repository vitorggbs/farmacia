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
            SUM(u.cargo = 'gerente' AND u.ativo = 1) AS gerentes
        FROM farmacias f
        LEFT JOIN usuarios u ON u.farmacia_id = f.id
        GROUP BY f.id
        ORDER BY f.id DESC";

$farmacias = mysqli_query($conexao, $sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Farmácias</title>

    <link rel="stylesheet" href="../style.css">

    <style>
        /* 
         * CORREÇÃO:
         * O título "PRIMEIRO GERENTE" estava vermelho
         * porque o CSS original aplicava --primary.
         */
        .card .separador-formulario h3 {
            color: #ffffff !important;
        }

        /* Mantém todos os textos dos campos em branco */
        .card label {
            color: #ffffff;
        }

        .card p {
            color: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<body>

    <?php cabecalhoAdmin('FarmaCerta - Farmácias'); ?>

    <main class="container">

        <?php if (isset($_GET['ok'])) { ?>

            <div class="aviso">
                Operação realizada com sucesso.
            </div>

        <?php } ?>


        <?php if (isset($_GET['erro'])) { ?>

            <div class="aviso aviso-vermelho">

                <?php

                if ($_GET['erro'] == 'login') {

                    echo 'Esse login de gerente já está sendo usado.';

                } else {

                    echo 'Não foi possível realizar a operação.';

                }

                ?>

            </div>

        <?php } ?>


        <!-- =====================================================
             LISTA DE FARMÁCIAS
        ====================================================== -->

        <section class="secao-branca">

            <h2>FARMÁCIAS CADASTRADAS</h2>

            <div class="tabela-wrapper">

                <table class="tabela">

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


                    <?php while ($farmacia = mysqli_fetch_assoc($farmacias)) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $farmacia['id']; ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $farmacia['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $farmacia['cnpj'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $farmacia['telefone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo (int) $farmacia['usuarios'];
                                ?>
                            </td>


                            <td>
                                <?php
                                echo (int) $farmacia['gerentes'];
                                ?>
                            </td>


                            <td>
                                <?php
                                echo $farmacia['ativo']
                                    ? 'Ativa'
                                    : 'Inativa';
                                ?>
                            </td>


                            <td>

                                <a
                                    class="acao"
                                    href="editarfarmacia.php?id=<?php echo (int) $farmacia['id']; ?>"
                                >
                                    Editar
                                </a>


                                <a
                                    class="acao <?php echo $farmacia['ativo'] ? 'excluir' : ''; ?>"
                                    href="alterarstatus.php?id=<?php echo (int) $farmacia['id']; ?>"
                                    onclick="return confirm('Deseja alterar o status desta farmácia?')"
                                >
                                    <?php
                                    echo $farmacia['ativo']
                                        ? 'Desativar'
                                        : 'Ativar';
                                    ?>
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                </table>

            </div>

        </section>


        <!-- =====================================================
             CADASTRAR NOVA FARMÁCIA
        ====================================================== -->

        <div class="card" id="cadastrar">

            <h2>CADASTRAR NOVA FARMÁCIA</h2>

            <p>
                Cadastre a farmácia e o primeiro gerente dela.
            </p>


            <form
                action="cadastrarfarmacia.php"
                method="POST"
                class="form-grid"
            >


                <!-- NOME DA FARMÁCIA -->

                <div>

                    <label for="nome">
                        NOME DA FARMÁCIA *
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        required
                    >

                </div>


                <!-- CNPJ -->

                <div>

                    <label for="cnpj">
                        CNPJ
                    </label>

                    <input
                        type="text"
                        id="cnpj"
                        name="cnpj"
                    >

                </div>


                <!-- TELEFONE -->

                <div>

                    <label for="telefone">
                        TELEFONE
                    </label>

                    <input
                        type="text"
                        id="telefone"
                        name="telefone"
                    >

                </div>


                <!-- ENDEREÇO -->

                <div class="campo-grande">

                    <label for="endereco">
                        ENDEREÇO
                    </label>

                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                    >

                </div>


                <!-- =================================================
                     PRIMEIRO GERENTE
                ================================================== -->

                <div class="campo-grande separador-formulario">

                    <h3>
                        PRIMEIRO GERENTE
                    </h3>

                </div>


                <!-- NOME DO GERENTE -->

                <div>

                    <label for="gerente_nome">
                        NOME DO GERENTE *
                    </label>

                    <input
                        type="text"
                        id="gerente_nome"
                        name="gerente_nome"
                        required
                    >

                </div>


                <!-- CPF -->

                <div>

                    <label for="gerente_cpf">
                        CPF
                    </label>

                    <input
                        type="text"
                        id="gerente_cpf"
                        name="gerente_cpf"
                    >

                </div>


                <!-- LOGIN -->

                <div>

                    <label for="gerente_login">
                        LOGIN *
                    </label>

                    <input
                        type="text"
                        id="gerente_login"
                        name="gerente_login"
                        required
                    >

                </div>


                <!-- SENHA -->

                <div>

                    <label for="gerente_senha">
                        SENHA *
                    </label>

                    <input
                        type="password"
                        id="gerente_senha"
                        name="gerente_senha"
                        minlength="6"
                        required
                    >

                </div>


                <!-- BOTÃO -->

                <div class="campo-grande">

                    <button type="submit">
                        CADASTRAR FARMÁCIA
                    </button>

                </div>

            </form>

        </div>

    </main>

</body>
</html>
