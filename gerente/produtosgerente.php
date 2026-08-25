<?php
require_once __DIR__ . '/conexaoDB.php';

$resultado = mysqli_query($conexao, "SELECT * FROM produtos ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Produtos</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" type="image/png" href="../assets/LOGO_2.png">
</head>
<body>

<header class="topo">
    <img src="../assets/LOGO_1.png" alt="FarmaCerta" width="200">
    <h1>FarmaCerta - Gerente</h1>
    <div class="usuario-topo">
        <span>Gerente: farmacia1</span>
        <a class="logout" href="../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="iniciogerente.html">INÍCIO</a>
    <a href="produtosgerente.php" class="ativo">PRODUTOS</a>
    <a href="funcionarios/funcionarios.php">FUNCIONÁRIOS</a>
    <a href="historicorecibogerente.html">RECIBOS</a>
</nav>

<main class="container">

    <div class="card" id="cadastrar">
        <h2>CADASTRAR PRODUTO</h2>

        <?php if (isset($_GET['cadastro'])) { ?>
            <p><strong>Produto cadastrado com sucesso!</strong></p>
        <?php } ?>

        <form action="cadastrargerente.php" method="POST" enctype="multipart/form-data">
            <label>Nome do produto</label>
            <input type="text" name="nomeProduto" required>

            <label>Preço</label>
            <input type="number" name="valor" step="0.01" min="0" required>

            <label>Quantidade</label>
            <input type="number" name="quantidade" min="0" required>

            <label>Prateleira</label>
            <input type="text" name="prateleira" placeholder="Ex.: A3" required>

            <label>Imagem</label>
            <input type="file" name="imagem" accept="image/*" required>

            <button type="submit">CADASTRAR PRODUTO</button>
        </form>
    </div>

    <div class="card">
        <div class="cabecalho-card">
            <h2>PRODUTOS CADASTRADOS</h2>
            <input class="busca-produtos" type="text" id="busca" placeholder="Buscar produto..." onkeyup="buscarProduto()">
        </div>

        <table class="tabela" id="tabela-produtos">
            <tr>
                <th>Foto</th>
                <th>Produto</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Prateleira</th>
            </tr>

            <?php while ($produto = mysqli_fetch_assoc($resultado)) { ?>
            <tr>
                <td>
                    <?php if (!empty($produto['imagem'])) { ?>
                        <img src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>" width="60" height="60" style="object-fit:cover;">
                    <?php } ?>
                </td>
                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                <td>
                    <?php echo (int)$produto['quantidade']; ?>
                    <?php if ($produto['quantidade'] <= 0) { ?><br><span class="sem-estoque">SEM ESTOQUE</span><?php } ?>
                </td>
                <td><?php echo isset($produto['prateleira']) ? htmlspecialchars($produto['prateleira']) : '-'; ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

</main>

<script>
function buscarProduto() {
    var busca = document.getElementById('busca').value.toLowerCase();
    var linhas = document.querySelectorAll('#tabela-produtos tr');

    for (var i = 1; i < linhas.length; i++) {
        linhas[i].style.display = linhas[i].textContent.toLowerCase().includes(busca) ? '' : 'none';
    }
}
</script>

</body>
</html>
<?php mysqli_close($conexao); ?>
