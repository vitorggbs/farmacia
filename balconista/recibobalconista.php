<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';

exigirLogin();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';

$farmaciaId = (int) $_SESSION['farmacia_id'];
$venda_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($venda_id <= 0) {
    die('Venda não informada.');
}

$sqlVenda = 'SELECT
                valor_total,
                forma_pagamento,
                data_venda,
                cliente,
                valor_recebido,
                troco,
                usuario_id
             FROM vendas
             WHERE id = ? AND farmacia_id = ?';

$stmtVenda = mysqli_prepare($conexao, $sqlVenda);

mysqli_stmt_bind_param($stmtVenda, 'ii', $venda_id, $farmaciaId);
mysqli_stmt_execute($stmtVenda);
mysqli_stmt_bind_result(
    $stmtVenda,
    $total,
    $formaPagamento,
    $dataVenda,
    $cliente,
    $valorRecebido,
    $troco,
    $vendedorId
);

if (!mysqli_stmt_fetch($stmtVenda)) {
    mysqli_stmt_close($stmtVenda);
    mysqli_close($conexao);
    die('Venda não encontrada.');
}

mysqli_stmt_close($stmtVenda);

$usuarioDiferente = (int) $vendedorId !== (int) $_SESSION['usuario_id'];

if ($_SESSION['cargo'] === 'balconista' && $usuarioDiferente) {
    die('Você não pode abrir este recibo.');
}

$stmtItens = mysqli_prepare(
    $conexao,
    "SELECT i.quantidade, p.nome, i.preco_unitario, i.subtotal
     FROM itens_venda i
     INNER JOIN produtos p ON p.id = i.produto_id
     WHERE i.venda_id = ? AND p.farmacia_id = ?
     ORDER BY i.id ASC"
);

mysqli_stmt_bind_param($stmtItens, 'ii', $venda_id, $farmaciaId);
mysqli_stmt_execute($stmtItens);
mysqli_stmt_bind_result($stmtItens, $quantidade, $nomeProduto, $precoUnitario, $subtotal);

$itens = array();
while (mysqli_stmt_fetch($stmtItens)) {
    $itens[] = array(
        'quantidade' => $quantidade,
        'nome' => $nomeProduto,
        'preco' => $precoUnitario,
        'subtotal' => $subtotal
    );
}

mysqli_stmt_close($stmtItens);
mysqli_close($conexao);

$cliente = $cliente ?: 'Não informado';
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
            <h1>Sistema de Gestão</h1>

            <div class="usuario-topo">
                <span><?php echo htmlspecialchars($nomeFarmacia); ?></span>
                <a class="logout" href="../logout.php">SAIR</a>
            </div>
        </header>

        <nav class="menu">
            <a href="iniciobalconista.php">INÍCIO</a>
            <a href="produtosbalconista.php">PRODUTOS</a>
            <a href="carrinhobalconista.php">CARRINHO</a>
            <a href="historicorecibobalconista.php">HISTÓRICO</a>
        </nav>

        <main class="container">
            <div class="card recibo">
                <h2>RECIBO DA COMPRA #<?php echo $venda_id; ?></h2>

                <p><strong>Farmácia:</strong> <?php echo htmlspecialchars($nomeFarmacia); ?></p>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente); ?></p>
                <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($dataVenda)); ?></p>

                <table class="tabela">
                    <tr>
                        <th>Quantidade</th>
                        <th>Produto</th>
                        <th>Valor unitário</th>
                        <th>Subtotal</th>
                    </tr>

                    <?php foreach ($itens as $item) { ?>
                        <tr>
                            <td><?php echo (int) $item['quantidade']; ?></td>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td>R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format((float) $item['subtotal'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <div class="total" style="color: white;">
                    <p>
                        <strong>Forma de pagamento utilizada:</strong>
                        <?php echo htmlspecialchars($formaPagamento); ?>
                    </p>
                    <p><strong>Valor total:</strong> R$ <?php echo number_format((float) $total, 2, ',', '.'); ?></p>
                    <p>
                        <strong>Valor recebido:</strong>
                        R$ <?php echo number_format((float) $valorRecebido, 2, ',', '.'); ?>
                    </p>

                    <?php if ($troco > 0) { ?>
                        <p><strong>Troco:</strong> R$ <?php echo number_format((float) $troco, 2, ',', '.'); ?></p>
                    <?php } else { ?>
                        <p><strong>Troco:</strong> Não houve troco</p>
                    <?php } ?>
                </div>

                <br>
                <button onclick="window.print()">IMPRIMIR RECIBO</button>
            </div>
        </main>

    </body>
</html>
