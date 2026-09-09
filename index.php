<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php
    require_once __DIR__ . '/includes/recursos.php';
    recursosCabeca('FarmaCerta - Login');
    ?>

</head>

<body class="login-tela d-flex align-items-center py-4">

    <main class="container">
        <div class="row justify-content-center">

            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

                <section class="card card-brand rounded-4 p-4 p-md-5">

                    <!-- Título -->
                    <h1 class="text-center fw-bold display-6 mb-3">
                        FarmaCerta
                    </h1>

                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <img
                            src="assets/LOGO_2.png"
                            alt="Logo FarmaCerta"
                            width="120"
                            height="120"
                            class="img-fluid"
                        >
                    </div>

                    <!-- Mensagem de erro -->
                    <?php if (isset($_GET['erro'])) { ?>

                        <div class="alert alert-light text-danger fw-bold" role="alert">

                            <?php
                            echo $_GET['erro'] === 'acesso'
                                ? 'Você não tem permissão para acessar essa área.'
                                : 'Login, senha ou função inválidos.';
                            ?>

                        </div>

                    <?php } ?>

                    <!-- Formulário de login -->
                    <form action="login.php" method="POST">

                        <!-- Login -->
                        <div class="mb-3">

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
                                required
                                placeholder="Digite seu login"
                                autocomplete="username"
                            >

                        </div>

                        <!-- Senha -->
                        <div class="mb-3">

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
                                required
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                            >

                        </div>

                        <!-- Função -->
                        <div class="mb-4">

                            <label
                                class="form-label fw-bold"
                                for="cargo"
                            >
                                Entrar como
                            </label>

                            <select
                                class="form-select"
                                id="cargo"
                                name="cargo"
                                required
                            >
                                <option value="">
                                    Selecione uma função
                                </option>

                                <option value="administrador">
                                    Administrador
                                </option>

                                <option value="gerente">
                                    Gerente
                                </option>

                                <option value="balconista">
                                    Balconista
                                </option>
                            </select>

                        </div>

                        <!-- Separador -->
                        <hr class="border-light opacity-25">

                        <!-- Botão -->
                        <button
                            class="btn btn-outline-light w-100 rounded-pill fw-bold py-2"
                            type="submit"
                        >
                            ENTRAR
                        </button>

                    </form>

                </section>

            </div>

        </div>
    </main>

    <?php recursosRodape(); ?>

</body>
</html>
