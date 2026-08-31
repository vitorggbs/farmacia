<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

$sql = "SELECT f.id, f.nome, f.cnpj, f.telefone, f.endereco, f.ativo,
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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Farmácias</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php cabecalhoAdmin('FarmaCerta - Farmácias'); ?>

    <main class="container">
        <?php if (isset($_GET['ok'])) { ?>
            <div class="aviso">Operação realizada com sucesso.</div>
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
                            <td><?php echo $farmacia['id']; ?></td>
                            <td><?php echo htmlspecialchars($farmacia['nome']); ?></td>
                            <td><?php echo htmlspecialchars($farmacia['cnpj']); ?></td>
                            <td><?php echo htmlspecialchars($farmacia['telefone']); ?></td>
                            <td><?php echo $farmacia['usuarios']; ?></td>
                            <td><?php echo (int) $farmacia['gerentes']; ?></td>
                            <td><?php echo $farmacia['ativo'] ? 'Ativa' : 'Inativa'; ?></td>
                            <td>
                                <a class="acao" href="editarfarmacia.php?id=<?php echo $farmacia['id']; ?>">Editar</a>
                                <a class="acao <?php echo $farmacia['ativo'] ? 'excluir' : ''; ?>"
                                   href="alterarstatus.php?id=<?php echo $farmacia['id']; ?>"
                                   onclick="return confirm('Deseja alterar o status desta farmácia?')">
                                    <?php echo $farmacia['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </section>

        <div class="card" id="cadastrar">
            <h2>CADASTRAR NOVA FARMÁCIA</h2>
            <p>Cadastre a farmácia e o primeiro gerente dela.</p>

            <form action="cadastrarfarmacia.php" method="POST" class="form-grid">
                <div>
                    <label>NOME DA FARMÁCIA *</label>
                    <input type="text" name="nome" required>
                </div>

                <div>
                    <label>CNPJ</label>
                    <input type="text" name="cnpj">
                </div>

                <div>
                    <label>TELEFONE</label>
                    <input type="text" name="telefone">
                </div>

                <div class="campo-grande">
                    <label>ENDEREÇO</label>
                    <input type="text" name="endereco">
                </div>

                <div class="campo-grande separador-formulario">
                    <h3>PRIMEIRO GERENTE</h3>
                </div>

                <div>
                    <label>NOME DO GERENTE *</label>
                    <input type="text" name="gerente_nome" required>
                </div>

                <div>
                    <label>CPF</label>
                    <input type="text" name="gerente_cpf">
                </div>

                <div>
                    <label>LOGIN *</label>
                    <input type="text" name="gerente_login" required>
                </div>

                <div>
                    <label>SENHA *</label>
                    <input type="password" name="gerente_senha" minlength="6" required>
                </div>

                <div class="campo-grande">
                    <button type="submit">CADASTRAR FARMÁCIA</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
