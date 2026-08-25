<?php
require_once __DIR__ . '/../conexaoDB.php';

$resultado = mysqli_query($conexao, "SELECT * FROM funcionarios ORDER BY nome_completo");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Funcionários</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="icon" type="image/png" href="../../assets/LOGO_2.png">
</head>
<body>

<header class="topo">
    <img src="../../assets/LOGO_1.png" alt="FarmaCerta" width="200">
    <h1>FarmaCerta - Gerente</h1>
    <div class="usuario-topo">
        <span>Gerente: farmacia1</span>
        <a class="logout" href="../../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="../iniciogerente.html">INÍCIO</a>
    <a href="../produtosgerente.php">PRODUTOS</a>
    <a href="funcionarios.php" class="ativo">FUNCIONÁRIOS</a>
    <a href="../historicorecibogerente.html">RECIBOS</a>
</nav>

<main class="container">

    <div class="card" id="cadastrar">
        <h2>CADASTRAR FUNCIONÁRIO</h2>

        <?php if (isset($_GET['cadastro'])) { ?>
            <p><strong>Funcionário cadastrado com sucesso!</strong></p>
        <?php } ?>

        <form action="cadastrarfuncionario.php" method="POST">
            <label>Nome completo</label>
            <input type="text" name="nome_completo" required>

            <label>CPF</label>
            <input type="text" name="cpf" maxlength="14" required>

            <label>Data de nascimento</label>
            <input type="date" name="data_nascimento" required>

            <label>Telefone</label>
            <input type="text" name="telefone" required>

            <label>E-mail</label>
            <input type="email" name="email" required>

            <label>Endereço</label>
            <input type="text" name="endereco" required>

            <label>Cargo / função</label>
            <select name="cargo" required>
                <option value="Caixa">Caixa</option>
            </select>

            <label>Data de admissão</label>
            <input type="date" name="data_admissao" required>

            <label>Salário</label>
            <input type="number" name="salario" step="0.01" min="0">

            <label>Horário de trabalho</label>
            <input type="text" name="horario" placeholder="Ex.: 08:00 às 17:00" required>

            <label>Escala de trabalho</label>
            <select name="escala" required>
                <option value="6x1">6x1</option>
            </select>

            <label>Login</label>
            <input type="text" name="login" required>

            <label>Senha</label>
            <input type="password" name="senha" minlength="6" required>

            <label>Permissão</label>
            <select name="permissao" required>
                <option value="balconista">Balconista</option>
            </select>

            <button type="submit">CADASTRAR FUNCIONÁRIO</button>
        </form>
    </div>

    <div class="card">
        <div class="cabecalho-card">
            <h2>FUNCIONÁRIOS CADASTRADOS</h2>
            <input class="busca-produtos" type="text" id="busca" placeholder="Buscar funcionário..." onkeyup="buscarFuncionario()">
        </div>

        <div class="tabela-wrapper">
            <table class="tabela" id="tabela-funcionarios">
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Nascimento</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Endereço</th>
                    <th>Cargo</th>
                    <th>Admissão</th>
                    <th>Salário</th>
                    <th>Horário</th>
                    <th>Escala</th>
                    <th>Login</th>
                    <th>Senha</th>
                    <th>Permissão</th>
                </tr>

                <?php while ($f = mysqli_fetch_assoc($resultado)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['nome_completo']); ?></td>
                    <td><?php echo htmlspecialchars($f['cpf']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($f['data_nascimento'])); ?></td>
                    <td><?php echo htmlspecialchars($f['telefone']); ?></td>
                    <td><?php echo htmlspecialchars($f['email']); ?></td>
                    <td><?php echo htmlspecialchars($f['endereco']); ?></td>
                    <td><?php echo htmlspecialchars($f['cargo']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($f['data_admissao'])); ?></td>
                    <td><?php echo $f['salario'] !== null ? 'R$ ' . number_format($f['salario'], 2, ',', '.') : '-'; ?></td>
                    <?php
                    $partes = explode(' / ', $f['horario_escala'], 2);
                    $horario = $partes[0];
                    $escala = isset($partes[1]) ? $partes[1] : '6x1';
                    ?>
                    <td><?php echo htmlspecialchars($horario); ?></td>
                    <td><?php echo htmlspecialchars($escala); ?></td>
                    <td><?php echo htmlspecialchars($f['login']); ?></td>
                    <td><?php echo htmlspecialchars($f['senha']); ?></td>
                    <td><?php echo htmlspecialchars($f['permissao']); ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>

</main>

<script>
function buscarFuncionario() {
    var busca = document.getElementById('busca').value.toLowerCase();
    var linhas = document.querySelectorAll('#tabela-funcionarios tr');

    for (var i = 1; i < linhas.length; i++) {
        linhas[i].style.display = linhas[i].textContent.toLowerCase().includes(busca) ? '' : 'none';
    }
}
</script>

</body>
</html>
<?php mysqli_close($conexao);
?>
