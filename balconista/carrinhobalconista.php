<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

$carrinho = $_SESSION['carrinho'];
$produtosCarrinho = array();
$total = 0;

foreach ($carrinho as $produto_id => $quantidade) {
    $produto_id = (int) $produto_id;
    $quantidade = (int) $quantidade;

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT id, nome, preco, quantidade FROM produtos WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $produto_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $nome, $preco, $estoque);

    if (mysqli_stmt_fetch($stmt)) {
        $subtotal = (float) $preco * $quantidade;
        $total += $subtotal;

        $produtosCarrinho[] = array(
            'id' => $id,
            'nome' => $nome,
            'preco' => $preco,
            'quantidade' => $quantidade,
            'estoque' => $estoque,
            'subtotal' => $subtotal
        );
    }

    mysqli_stmt_close($stmt);
}

$quantidadeCarrinho = array_sum($carrinho);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Carrinho</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" type="image/png" href="../assets/LOGO_2.png">
</head>
<body>

<header class="topo">
    <img src="../assets/LOGO_1.png" alt="foto da empresa" width="200" height="auto">
    <h1>Sistema de Gestão</h1>
    <div class="usuario-topo">
        <span>Farmácia: farmacia1</span>
        <a class="logout" href="../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="iniciobalconista.html">INÍCIO</a>
    <a href="produtosbalconista.php">PRODUTOS</a>
    <a href="carrinhobalconista.php" class="ativo">CARRINHO (<?php echo $quantidadeCarrinho; ?>)</a>
    <a href="historicorecibobalconista.html">HISTÓRICO</a>
</nav>

<main class="container">
<div class="card">
    <h2>CARRINHO</h2>

    <?php if (isset($_GET['erro'])) { ?>
        <p><?php echo htmlspecialchars($_GET['erro']); ?></p>
    <?php } ?>

    <table class="tabela">
        <tr>
            <th>Produto</th>
            <th>Quantidade</th>
            <th>Valor unitário</th>
            <th>Subtotal</th>
            <th>Ação</th>
        </tr>

        <?php if (count($produtosCarrinho) > 0) { ?>

            <?php foreach ($produtosCarrinho as $produto) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>

                    <td>
                        <form action="atualizarcarrinho.php" method="POST">
                            <input type="hidden" name="produto_id" value="<?php echo (int) $produto['id']; ?>">
                            <input
                                type="number"
                                name="quantidade"
                                value="<?php echo (int) $produto['quantidade']; ?>"
                                min="1"
                                max="<?php echo (int) $produto['estoque']; ?>"
                                required
                            >
                            <button type="submit">ATUALIZAR</button>
                        </form>
                    </td>

                    <td>R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format((float) $produto['subtotal'], 2, ',', '.'); ?></td>

                    <td>
                        <form action="removercarrinho.php" method="POST">
                            <input type="hidden" name="produto_id" value="<?php echo (int) $produto['id']; ?>">
                            <button class="acao excluir" type="submit">REMOVER</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>

        <?php } else { ?>
            <tr>
                <td colspan="5">O carrinho está vazio.</td>
            </tr>
        <?php } ?>
    </table>

    <div class="total" style="color: white;">
        VALOR TOTAL DA VENDA: R$ <?php echo number_format($total, 2, ',', '.'); ?>
    </div>

    <?php if (count($produtosCarrinho) > 0) { ?>
        <form method="POST" action="finalizarvenda.php" class="dados-pagamento" style="margin-top: 24px;">

            <label for="nome-cliente">Nome do cliente</label>
            <input
                id="nome-cliente"
                name="nome_cliente"
                type="text"
                placeholder="Nome da pessoa que comprou"
            >

            <label for="forma-pagamento">Forma de pagamento</label>
            <select
                id="forma-pagamento"
                name="forma_pagamento"
                onchange="mostrarValorRecebido()"
                required
            >
                <option value="">Selecione a forma de pagamento</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="pix">Pix</option>
                <option value="debito">Cartão de débito</option>
                <option value="credito">Cartão de crédito</option>
            </select>

            <div id="campo-dinheiro" style="display: none;">
                <label for="valor-recebido">Valor recebido</label>
                <input
                    id="valor-recebido"
                    name="valor_recebido"
                    type="number"
                    min="<?php echo number_format($total, 2, '.', ''); ?>"
                    step="0.01"
                    placeholder="R$ 0,00"
                    oninput="calcularTroco()"
                >
            </div>

            <div class="total" style="color: white; margin-top: 18px;">
                VALOR RECEBIDO: R$ <span id="valor-mostrado">0,00</span>
                <br>
                TROCO: R$ <span id="troco-mostrado">0,00</span>
            </div>

            <br>
            <button class="botao" type="submit">FINALIZAR COMPRA</button>
        </form>
    <?php } ?>
</div>
</main>

<script>
var total = <?php echo json_encode((float) $total); ?>;

function mostrarValorRecebido() {
    var forma = document.getElementById("forma-pagamento").value;
    var campo = document.getElementById("campo-dinheiro");
    var recebido = document.getElementById("valor-recebido");

    if (forma == "dinheiro") {
        campo.style.display = "block";
        recebido.required = true;
        calcularTroco();
    } else {
        campo.style.display = "none";
        recebido.required = false;
        recebido.value = "";

        if (forma == "") {
            document.getElementById("valor-mostrado").innerHTML = "0,00";
        } else {
            document.getElementById("valor-mostrado").innerHTML = total.toFixed(2).replace(".", ",");
        }

        document.getElementById("troco-mostrado").innerHTML = "0,00";
    }
}

function calcularTroco() {
    var recebido = parseFloat(document.getElementById("valor-recebido").value || 0);
    var troco = recebido - total;

    if (troco < 0) {
        troco = 0;
    }

    document.getElementById("valor-mostrado").innerHTML = recebido.toFixed(2).replace(".", ",");
    document.getElementById("troco-mostrado").innerHTML = troco.toFixed(2).replace(".", ",");
}
</script>

</body>
</html>
<?php
mysqli_close($conexao);
?>
