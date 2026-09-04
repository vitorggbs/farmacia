<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';

exigirLogin('balconista');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

$quantidadeCarrinho = array_sum($_SESSION['carrinho']);

$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = 'SELECT id, nome, preco, quantidade, imagem
        FROM produtos
        WHERE farmacia_id = ? AND ativo = 1
        ORDER BY nome ASC';

$stmt = mysqli_prepare($conexao, $sql);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado) {
    die("Erro ao buscar produtos: " . mysqli_error($conexao));
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>FarmaCerta - Produtos</title>

        <link
            rel="stylesheet"
            href="../style.css"
        >

        <link
            rel="icon"
            type="image/png"
            href="../assets/LOGO_2.png"
        >

    </head>

    <body>

        <header class="topo">

            <!-- LOGO CLICÁVEL -->
            <a href="iniciobalconista.php" class="logo-link">

                <img
                    src="../assets/LOGO_1.png"
                    alt="Logo FarmaCerta"
                    width="200"
                    height="auto"
                >

            </a>

            <h1>Sistema de Gestão</h1>

            <div class="usuario-topo">

                <span>

                    <?php echo htmlspecialchars($nomeFarmacia); ?>

                    — Balconista:

                    <strong>
                        <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                    </strong>

                </span>

                <a
                    class="logout"
                    href="../logout.php"
                >
                    SAIR
                </a>

            </div>

        </header>

        <nav class="menu">

            <a href="iniciobalconista.php">
                INÍCIO
            </a>

            <a
                href="produtosbalconista.php"
                class="ativo"
            >
                PRODUTOS
            </a>

            <a href="carrinhobalconista.php">
                CARRINHO (<?php echo $quantidadeCarrinho; ?>)
            </a>

            <a href="historicorecibobalconista.php">
                HISTÓRICO
            </a>

        </nav>

        <main class="container">

            <div class="card">

                <div class="cabecalho-card">

                    <h2>
                        PRODUTOS CADASTRADOS
                    </h2>

                    <input
                        class="busca-produtos"
                        type="text"
                        id="busca"
                        placeholder="Buscar produto..."
                        onkeyup="buscarProduto()"
                    >

                </div>

                <?php if (isset($_GET['ok'])) { ?>

                    <p>
                        Produto adicionado ao carrinho.
                    </p>

                <?php } ?>

                <?php if (isset($_GET['erro'])) { ?>

                    <p>
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </p>

                <?php } ?>

                <table
                    class="tabela"
                    id="tabela-produtos"
                >

                    <tr>

                        <th>Foto</th>
                        <th>Produto</th>
                        <th>Valor</th>
                        <th>Estoque</th>
                        <th>Comprar</th>

                    </tr>

                    <?php if (mysqli_num_rows($resultado) > 0) { ?>

                        <?php while ($produto = mysqli_fetch_assoc($resultado)) { ?>

                            <tr>

                                <td>

                                    <?php if (!empty($produto['imagem'])) { ?>

                                        <img
                                            src="../gerente/uploads/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                            width="70"
                                            height="70"
                                            style="object-fit: cover;"
                                            alt="Produto"
                                        >

                                    <?php } else { ?>

                                        <div class="foto-exemplo"></div>

                                    <?php } ?>

                                </td>

                                <td>
                                    <?php echo htmlspecialchars($produto['nome']); ?>
                                </td>

                                <td>

                                    R$
                                    <?php echo number_format(
                                        (float) $produto['preco'],
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                </td>

                                <td>

                                    <?php if ((int) $produto['quantidade'] <= 0) { ?>

                                        <span class="sem-estoque">
                                            0
                                            <br>
                                            SEM ESTOQUE
                                        </span>

                                    <?php } else { ?>

                                        <?php echo (int) $produto['quantidade']; ?>

                                    <?php } ?>

                                </td>

                                <td>

                                    <?php if ((int) $produto['quantidade'] > 0) { ?>

                                        <form
                                            action="adicionarcarrinho.php"
                                            method="POST"
                                        >

                                            <input
                                                type="hidden"
                                                name="produto_id"
                                                value="<?php echo (int) $produto['id']; ?>"
                                            >

                                            <input
                                                type="number"
                                                name="quantidade"
                                                value="1"
                                                min="1"
                                                max="<?php echo (int) $produto['quantidade']; ?>"
                                                required
                                            >

                                            <button type="submit">
                                                ADICIONAR
                                            </button>

                                        </form>

                                    <?php } else { ?>

                                        <button disabled>
                                            SEM ESTOQUE
                                        </button>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>

                            <td colspan="5">
                                Nenhum produto cadastrado.
                            </td>

                        </tr>

                    <?php } ?>

                </table>

            </div>

        </main>

        <script>

            function buscarProduto() {

                var busca =
                    document
                        .getElementById("busca")
                        .value
                        .toLowerCase();

                var linhas =
                    document
                        .getElementById("tabela-produtos")
                        .getElementsByTagName("tr");

                for (var i = 1; i < linhas.length; i++) {

                    var colunaNome =
                        linhas[i].getElementsByTagName("td")[1];

                    if (colunaNome) {

                        var nome =
                            colunaNome.textContent.toLowerCase();

                        linhas[i].style.display =
                            nome.indexOf(busca) > -1
                                ? ""
                                : "none";
                    }
                }
            }

        </script>

    </body>

</html>

<?php

mysqli_close($conexao);

?>