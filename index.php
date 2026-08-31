<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>FarmaCerta - Login</title>
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="assets/LOGO_2.png">
    </head>
    <body class="login-body">
        <?php if (isset($_GET['erro'])) { ?>
            <div class="aviso-erro-login" role="alert">
                Login ou senha incorretos.
            </div>
        <?php } ?>

        <main class="login-card">
            <h1>FarmaCerta</h1>
            <div class="logo-farmacia">
                <img src="assets/LOGO_2.png" alt="FarmaCerta">
            </div>
            <form action="login.php" method="POST">
                <label>LOGIN</label>
                <input type="text" name="login" required>
                <label>SENHA</label>
                <input type="password" name="senha" required>
                <label>ENTRAR COMO</label>
                <select name="cargo" required>
                    <option value="">Selecione uma função</option>
                    <option value="administrador">Administrador</option>
                    <option value="gerente">Gerente</option>
                    <option value="balconista">Balconista</option>
                </select>
                <button type="submit">ENTRAR</button>
            </form>
        </main>
    </body>
</html>
