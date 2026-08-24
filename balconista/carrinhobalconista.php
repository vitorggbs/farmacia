<?php
$total = 33.90;
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
    <h1> Sistema de Gestão</h1>
    <div class="usuario-topo">
        <span>Farmácia: farmacia1</span>
        <a class="logout" href="../index.html">SAIR</a>
    </div>
</header>

<nav class="menu">
    <a href="iniciobalconista.html" class="">INÍCIO</a>
<a href="produtosbalconista.html" class="">PRODUTOS</a>
<a href="carrinhobalconista.php" class="ativo">CARRINHO (2)</a>
<a href="historicorecibobalconista.html" class="">HISTÓRICO</a>
</nav>

<main class="container">

<div class="card">
    <h2>CARRINHO</h2>

    <table class="tabela">
        <tr>
            <th>Produto</th>
            <th>Quantidade</th>
            <th>Valor unitário</th>
            <th>Subtotal</th>
            <th>Ação</th>
        </tr>

        <tr>
            <td>Dipirona</td>
            <td>2</td>
            <td>R$ 12,50</td>
            <td>R$ 25,00</td>
            <td><a class="acao excluir" href="#">REMOVER</a></td>
        </tr>

        <tr>
            <td>Álcool 70%</td>
            <td>1</td>
            <td>R$ 8,90</td>
            <td>R$ 8,90</td>
            <td><a class="acao excluir" href="#">REMOVER</a></td>
        </tr>
    </table>

    <div class="total" style="color: white;">
        VALOR TOTAL DA VENDA: R$ <?php echo number_format($total, 2, ',', '.'); ?>
    </div>

    <form method="POST" action="recibobalconista.php" class="dados-pagamento" style="margin-top: 24px;">
        <label for="nome-cliente">Nome do cliente</label>
        <input id="nome-cliente" name="nome_cliente" type="text"
               placeholder="Nome da pessoa que comprou">

        <label for="forma-pagamento">Forma de pagamento</label>
        <select id="forma-pagamento" name="forma_pagamento" onchange="mostrarValorRecebido()" required>
            <option value="">Selecione a forma de pagamento</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="pix">Pix</option>
            <option value="debito">Cartão de débito</option>
            <option value="credito">Cartão de crédito</option>
        </select>

        <div id="campo-dinheiro" style="display: none;">
            <label for="valor-recebido">Valor recebido</label>
            <input id="valor-recebido" name="valor_recebido" type="number"
                   min="33.90" step="0.01" placeholder="R$ 0,00" oninput="calcularTroco()">
        </div>

        <div class="total" style="color: white; margin-top: 18px;">
            VALOR RECEBIDO: R$ <span id="valor-mostrado">0,00</span>
            <br>
            TROCO: R$ <span id="troco-mostrado">0,00</span>
        </div>

        <br>
        <button class="botao" type="submit">FINALIZAR COMPRA</button>
    </form>
</div>

</main>

<script>
    var total = 33.90;

    function mostrarValorRecebido() {
        var forma = document.getElementById("forma-pagamento").value;
        var campo = document.getElementById("campo-dinheiro");

        if (forma == "dinheiro") {
            campo.style.display = "block";
            document.getElementById("valor-recebido").required = true;
            calcularTroco();
        } else {
            campo.style.display = "none";
            document.getElementById("valor-recebido").required = false;
            document.getElementById("valor-recebido").value = "";

            if (forma == "") {
                document.getElementById("valor-mostrado").innerHTML = "0,00";
            } else {
                document.getElementById("valor-mostrado").innerHTML = "33,90";
            }

            document.getElementById("troco-mostrado").innerHTML = "0,00";
        }
    }

    function calcularTroco() {
        var recebido = document.getElementById("valor-recebido").value;

        if (recebido == "") {
            recebido = 0;
        }

        recebido = parseFloat(recebido);
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
