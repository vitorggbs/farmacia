<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';

exigirLogin('balconista');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

$farmaciaId = (int) $_SESSION['farmacia_id'];
$carrinho = $_SESSION['carrinho'];
$produtosCarrinho = array();
$total = 0;

foreach ($carrinho as $produto_id => $quantidade) {
    $produto_id = (int) $produto_id;
    $quantidade = (int) $quantidade;

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT id, nome, preco, quantidade FROM produtos WHERE id = ? AND farmacia_id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'ii', $produto_id, $farmaciaId);
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('FarmaCerta - Carrinho'); ?>
</head>
<body>
    <?php cabecalho('Sistema de Gestão', 'balconista', 'carrinho'); ?>

    <main class="container py-4">
        <section class="card card-brand rounded-4 p-4">
            <h2 class="h3 fw-bold">CARRINHO</h2>

            <?php if (isset($_GET['erro'])) { ?>
                <div class="alert alert-light text-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
            <?php } ?>

            <div class="table-responsive bg-white rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor unitário</th>
                            <th>Subtotal</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($produtosCarrinho) > 0) { ?>
                        <?php foreach ($produtosCarrinho as $produto) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                <td>
                                    <form action="atualizarcarrinho.php" method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="produto_id" value="<?php echo (int) $produto['id']; ?>">
                                        <input class="form-control form-control-sm" type="number" name="quantidade" value="<?php echo (int) $produto['quantidade']; ?>" min="1" max="<?php echo (int) $produto['estoque']; ?>" required>
                                        <button class="btn btn-sm btn-primary rounded-pill" type="submit">ATUALIZAR</button>
                                    </form>
                                </td>
                                <td>R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format((float) $produto['subtotal'], 2, ',', '.'); ?></td>
                                <td>
                                    <form action="removercarrinho.php" method="POST">
                                        <input type="hidden" name="produto_id" value="<?php echo (int) $produto['id']; ?>">
                                        <button class="btn btn-sm btn-danger rounded-pill" type="submit">REMOVER</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5">O carrinho está vazio.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

            <p class="fs-4 fw-bold text-end mt-4 mb-0">
                VALOR TOTAL DA VENDA: R$ <?php echo number_format($total, 2, ',', '.'); ?>
            </p>

            <?php if (count($produtosCarrinho) > 0) { ?>
                <form method="POST" action="finalizarvenda.php" class="row g-3 mt-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold" for="nome-cliente">Nome do cliente</label>
                        <input class="form-control" id="nome-cliente" name="nome_cliente" type="text" placeholder="Nome da pessoa que comprou">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold" for="cpf-cliente">CPF do cliente (opcional)</label>
                        <input class="form-control" id="cpf-cliente" name="cpf_cliente" type="text" maxlength="14">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold" for="forma-pagamento">Forma de pagamento</label>
                        <select class="form-select" id="forma-pagamento" name="forma_pagamento" onchange="mostrarValorRecebido()" required>
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">Pix</option>
                            <option value="debito">Cartão de débito</option>
                            <option value="credito">Cartão de crédito</option>
                        </select>
                    </div>
                    <div class="col-12" id="campo-dinheiro" hidden>
                        <label class="form-label fw-bold" for="valor-recebido">Valor recebido</label>
                        <input class="form-control" id="valor-recebido" name="valor_recebido" type="number" min="<?php echo number_format($total, 2, '.', ''); ?>" step="0.01" placeholder="R$ 0,00" oninput="calcularTroco()">
                    </div>
                    <div class="col-12 fs-5 fw-bold">
                        VALOR RECEBIDO: R$ <span id="valor-mostrado">0,00</span><br>
                        TROCO: R$ <span id="troco-mostrado">0,00</span>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-light rounded-pill fw-bold" type="submit">FINALIZAR COMPRA</button>
                    </div>
                </form>
            <?php } ?>
        </section>
    </main>
    <?php recursosRodape(); ?>
    <script>
        var total = <?php echo json_encode((float) $total); ?>;

        function mostrarValorRecebido() {
            var forma = document.getElementById("forma-pagamento").value;
            var campo = document.getElementById("campo-dinheiro");
            var recebido = document.getElementById("valor-recebido");

            if (forma == "dinheiro") {
                campo.hidden = false;
                recebido.required = true;
                calcularTroco();
            } else {
                campo.hidden = true;
                recebido.required = false;
                recebido.value = "";
                document.getElementById("valor-mostrado").innerHTML = forma == "" ? "0,00" : total.toFixed(2).replace(".", ",");
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
<?php mysqli_close($conexao); ?>
