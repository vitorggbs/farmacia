<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/conexaoDB.php';

$sql = "SELECT id, nome, descricao, preco, quantidade, estoque_minimo, imagem
        FROM produtos
        ORDER BY nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar produtos: " . mysqli_error($conexao));
}
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
    <img src="../assets/LOGO_1.png" alt="foto da empresa" width="200" height="auto">
    <h1>FarmaCerta - Gerente</h1>

    <div class="usuario-topo">
        <span>Gerente: farmacia1</span>
        <a class="logout" href="../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="iniciogerente.html">INÍCIO</a>
    <a href="produtosgerente.php" class="ativo">PRODUTOS</a>
    <a href="cadastrargerente.html">CADASTRAR PRODUTO</a>
    <a href="historicorecibogerente.html">RECIBOS</a>
</nav>

<main class="container">
    <div class="card">

        <div class="cabecalho-card">
            <h2>PRODUTOS CADASTRADOS</h2>

            <input
                class="busca-produtos"
                type="text"
                id="busca"
                placeholder="Buscar produto..."
                onkeyup="buscarProduto()"
            >
        </div>

        <table class="tabela" id="tabela-produtos">
            <tr>
                <th>Foto</th>
                <th>Produto</th>
                <th>Descrição</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Estoque mínimo</th>
            </tr>

            <?php if (mysqli_num_rows($resultado) > 0) { ?>

                <?php while ($produto = mysqli_fetch_assoc($resultado)) { ?>

                    <tr>
                        <td>
                            <?php if (!empty($produto['imagem'])) { ?>
                                <img
                                    src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                    alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                    width="70"
                                    height="70"
                                    style="object-fit: cover;"
                                >
                            <?php } else { ?>
                                <div class="foto-exemplo"></div>
                            <?php } ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($produto['nome']); ?>
                        </td>

                        <td>
                            <?php
                            if ($produto['descricao'] != '') {
                                echo htmlspecialchars($produto['descricao']);
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>

                        <td>
                            R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?>
                        </td>

                        <td>
                            <?php if ($produto['quantidade'] <= 0) { ?>
                                <span class="sem-estoque">
                                    0<br>
                                    SEM ESTOQUE
                                </span>
                            <?php } elseif ($produto['quantidade'] <= $produto['estoque_minimo']) { ?>
                                <?php echo (int) $produto['quantidade']; ?><br>
                                ESTOQUE BAIXO
                            <?php } else { ?>
                                <?php echo (int) $produto['quantidade']; ?>
                            <?php } ?>
                        </td>

                        <td>
                            <?php echo (int) $produto['estoque_minimo']; ?>
                        </td>
                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="6">Nenhum produto cadastrado.</td>
                </tr>

            <?php } ?>
        </table>
    </div>
</main>

<script>
function buscarProduto() {
    var busca = document.getElementById("busca").value.toLowerCase();
    var tabela = document.getElementById("tabela-produtos");
    var linhas = tabela.getElementsByTagName("tr");

    for (var i = 1; i < linhas.length; i++) {
        var nome = linhas[i].getElementsByTagName("td")[1];

        if (nome) {
            var texto = nome.textContent.toLowerCase();

            if (texto.indexOf(busca) > -1) {
                linhas[i].style.display = "";
            } else {
                linhas[i].style.display = "none";
            }
        }
    }
}
</script>

</body>
</html>

<?php
mysqli_close($conexao);
?>
