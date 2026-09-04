<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';

exigirLogin('gerente');

require_once __DIR__ . '/conexaoDB.php';

$farmaciaId = (int) $_SESSION['farmacia_id'];
$nomeFarmacia = $_SESSION['farmacia_nome'] ?? 'Farmácia';

$sql = '
    SELECT *
    FROM produtos
    WHERE farmacia_id = ?
    AND ativo = 1
    ORDER BY nome
';

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

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
    <a
        href="iniciogerente.php"
        class="logo-link"
    >
        <img
            src="../assets/LOGO_1.png"
            alt="FarmaCerta"
            width="200"
        >
    </a>

    <h1>
        FarmaCerta - Gerente
    </h1>

    <div class="usuario-topo">

        <span>

            <?php echo htmlspecialchars($nomeFarmacia); ?>

            — Gerente:

            <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>

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

    <a href="iniciogerente.php">
        INÍCIO
    </a>

    <a
        href="produtosgerente.php"
        class="ativo"
    >
        PRODUTOS
    </a>

    <a href="movimentacoesestoque.php">
        MOVIMENTAÇÕES
    </a>

    <a href="funcionarios/funcionarios.php">
        FUNCIONÁRIOS
    </a>

    <a href="historicorecibogerente.php">
        RECIBOS
    </a>

</nav>


<main class="container">

    <div
        class="card"
        id="cadastrar"
    >

        <h2>
            CADASTRAR PRODUTO
        </h2>


        <?php if (isset($_GET['cadastro'])) { ?>

            <p>
                <strong>
                    Produto cadastrado com sucesso!
                </strong>
            </p>

        <?php } ?>


        <form
            action="cadastrargerente.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <label>
                Nome do produto
            </label>

            <input
                type="text"
                name="nomeProduto"
                required
            >


            <label>
                Preço
            </label>

            <input
                type="number"
                name="valor"
                step="0.01"
                min="0"
                required
            >


            <label>
                Quantidade
            </label>

            <input
                type="number"
                name="quantidade"
                min="0"
                required
            >


            <label>
                Estoque mínimo
            </label>

            <input
                type="number"
                name="estoque_minimo"
                min="0"
                value="5"
                required
            >


            <label>
                Prateleira
            </label>

            <input
                type="text"
                name="prateleira"
                placeholder="Ex.: A3"
                required
            >


            <label>
                Imagem
            </label>

            <input
                type="file"
                name="imagem"
                accept="image/*"
                required
            >


            <button type="submit">
                CADASTRAR PRODUTO
            </button>

        </form>

    </div>


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


        <table
            class="tabela"
            id="tabela-produtos"
        >

            <tr>

                <th>
                    Foto
                </th>

                <th>
                    Produto
                </th>

                <th>
                    Preço
                </th>

                <th>
                    Estoque
                </th>

                <th>
                    Prateleira
                </th>

                <th>
                    Ações
                </th>

            </tr>


            <?php while ($produto = mysqli_fetch_assoc($resultado)) { ?>

                <tr>

                    <td>

                        <?php if (!empty($produto['imagem'])) { ?>

                            <img
                                src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                width="60"
                                height="60"
                                style="object-fit: cover;"
                                alt="Produto"
                            >

                        <?php } ?>

                    </td>


                    <td>

                        <?php echo htmlspecialchars($produto['nome']); ?>

                    </td>


                    <td>

                        R$
                        <?php echo number_format(
                            $produto['preco'],
                            2,
                            ',',
                            '.'
                        ); ?>

                    </td>


                    <td>

                        <?php echo (int) $produto['quantidade']; ?>

                        <?php if ($produto['quantidade'] <= 0) { ?>

                            <br>

                            <span class="sem-estoque">
                                SEM ESTOQUE
                            </span>

                        <?php } elseif (
                            $produto['quantidade']
                            <=
                            $produto['estoque_minimo']
                        ) { ?>

                            <br>

                            <span class="sem-estoque">
                                ESTOQUE BAIXO
                            </span>

                        <?php } ?>

                    </td>


                    <td>

                        <?php echo htmlspecialchars(
                            $produto['prateleira']
                        ); ?>

                    </td>


                    <td>

                        <details>

                            <summary>
                                EDITAR
                            </summary>


                            <form
                                action="editarproduto.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="produto_id"
                                    value="<?php echo $produto['id']; ?>"
                                >


                                <input
                                    name="nome"
                                    value="<?php echo htmlspecialchars(
                                        $produto['nome']
                                    ); ?>"
                                    required
                                >


                                <input
                                    type="number"
                                    step="0.01"
                                    name="preco"
                                    value="<?php echo $produto['preco']; ?>"
                                    required
                                >


                                <input
                                    type="number"
                                    name="estoque_minimo"
                                    value="<?php echo $produto['estoque_minimo']; ?>"
                                    required
                                >


                                <input
                                    name="prateleira"
                                    value="<?php echo htmlspecialchars(
                                        $produto['prateleira']
                                    ); ?>"
                                    required
                                >


                                <button>
                                    SALVAR
                                </button>

                            </form>

                        </details>


                        <form
                            action="reporproduto.php"
                            method="POST"
                            onsubmit="return confirmarReposicao()"
                        >

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?php echo $produto['id']; ?>"
                            >


                            <input
                                type="number"
                                name="quantidade"
                                min="1"
                                placeholder="Quantidade"
                                required
                            >


                            <button>
                                REPOR
                            </button>

                        </form>


                        <form
                            action="excluirproduto.php"
                            method="POST"
                            onsubmit="return confirm('Excluir produto?')"
                        >

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?php echo $produto['id']; ?>"
                            >


                            <button>
                                EXCLUIR
                            </button>

                        </form>

                    </td>

                </tr>

            <?php } ?>

        </table>

    </div>

</main>


<script>

function buscarProduto()
{
    var busca =
        document
            .getElementById('busca')
            .value
            .toLowerCase();

    var linhas =
        document.querySelectorAll(
            '#tabela-produtos tr'
        );

    for (
        var i = 1;
        i < linhas.length;
        i++
    ) {

        linhas[i].style.display =
            linhas[i]
                .textContent
                .toLowerCase()
                .includes(busca)
                ? ''
                : 'none';

    }
}


function confirmarReposicao()
{
    var primeiraConfirmacao =
        confirm(
            'Confirma a reposição?'
        );

    if (!primeiraConfirmacao) {
        return false;
    }

    return confirm(
        'Tem certeza da quantidade?'
    );
}

</script>


</body>

</html>

<?php

mysqli_close($conexao);

?>
