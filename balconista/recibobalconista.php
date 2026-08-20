<?php
$total = 33.90;
$cliente = "Não informado";
$formaPagamento = "Não informada";
$valorRecebido = $total;
$troco = 0;

if (isset($_POST["nome_cliente"])) {
    if ($_POST["nome_cliente"] != "") {
        $cliente = $_POST["nome_cliente"];
    }
}

if (isset($_POST["forma_pagamento"])) {
    $forma = $_POST["forma_pagamento"];

    if ($forma == "dinheiro") {
        $formaPagamento = "Dinheiro";
        $valorRecebido = floatval($_POST["valor_recebido"]);
        $troco = $valorRecebido - $total;
    }

    if ($forma == "pix") {
        $formaPagamento = "Pix";
    }

    if ($forma == "debito") {
        $formaPagamento = "Cartão de débito";
    }

    if ($forma == "credito") {
        $formaPagamento = "Cartão de crédito";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Recibo</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" type="image/png" href="../assets/LOGO_2.png">
</head>
<body>

<header class="topo">
    <img src="../assets/LOGO_1.png" alt="foto da empresa" width="200" height="auto">
    <h1> Sistema de Gestão</h1>
    <div class="usuario-topo">
        <span>Farmácia: farmacia1</span>
        <a class="logout" href="../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="iniciobalconista.html" class="">INÍCIO</a>
<a href="produtosbalconista.html" class="">PRODUTOS</a>
<a href="cadastrarbalconista.html" class="">CADASTRAR</a>
<a href="carrinhobalconista.php" class="">CARRINHO (2)</a>
<a href="historicorecibobalconista.html" class="">HISTÓRICO</a>
</nav>

<main class="container">

<div class="card recibo">
    <h2>RECIBO DA COMPRA</h2>

    <p><strong>Farmácia:</strong> farmacia1</p>
    <p><strong>Cliente:</strong> <?php echo $cliente; ?></p>
    <p><strong>Data:</strong> <?php echo date("d/m/Y"); ?></p>

    <table class="tabela">
        <tr>
            <th>Quantidade</th>
            <th>Produto</th>
            <th>Valor unitário</th>
            <th>Subtotal</th>
        </tr>
        <tr>
            <td>2</td>
            <td>Dipirona</td>
            <td>R$ 12,50</td>
            <td>R$ 25,00</td>
        </tr>
        <tr>
            <td>1</td>
            <td>Álcool 70%</td>
            <td>R$ 8,90</td>
            <td>R$ 8,90</td>
        </tr>
    </table>

    <div class="total" style="color: white;">
        <p><strong>Forma de pagamento utilizada:</strong> <?php echo $formaPagamento; ?></p>
        <p><strong>Valor total:</strong> R$ <?php echo number_format($total, 2, ',', '.'); ?></p>
        <p><strong>Valor recebido:</strong> R$ <?php echo number_format($valorRecebido, 2, ',', '.'); ?></p>

        <?php if ($troco > 0) { ?>
            <p><strong>Troco:</strong> R$ <?php echo number_format($troco, 2, ',', '.'); ?></p>
        <?php } else { ?>
            <p><strong>Troco:</strong> Não houve troco</p>
        <?php } ?>
    </div>

    <br>
    <button>IMPRIMIR RECIBO</button>
</div>

</main>

</body>
</html>