<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';

exigirLogin();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

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
                usuario_id,
                status,
                motivo_cancelamento
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
    $vendedorId,
    $statusVenda,
    $motivoCancelamento
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
$nomeFarmacia = $_SESSION['farmacia_nome'] ?? 'Farmácia';
$pasta = ($_SESSION['cargo'] ?? '') === 'gerente' ? 'gerente' : 'balconista';
$paginaAtiva = $pasta === 'gerente' ? 'recibos' : 'historico';
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <?php recursosCabeca('FarmaCerta - Recibo'); ?>
    </head>
    <body>
        <?php cabecalho('Sistema de Gestão', $pasta, $paginaAtiva); ?>

        <main class="container py-4">
            <section class="card card-brand rounded-4 p-4 mx-auto" style="max-width:700px;">
                <h2 class="h3 fw-bold text-center">RECIBO DA COMPRA #<?php echo $venda_id; ?></h2>

                <p><strong>Farmácia:</strong> <?php echo htmlspecialchars($nomeFarmacia); ?></p>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente); ?></p>
                <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($dataVenda)); ?></p>
                <?php if ($statusVenda === 'cancelada') { ?>
                    <div class="alert alert-danger"><strong>VENDA CANCELADA</strong><?php if ($motivoCancelamento) { ?> — <?php echo htmlspecialchars($motivoCancelamento); ?><?php } ?></div>
                <?php } ?>

                <div class="table-responsive bg-white rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Quantidade</th>
                        <th>Produto</th>
                        <th>Valor unitário</th>
                        <th>Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itens as $item) { ?>
                        <tr>
                            <td><?php echo (int) $item['quantidade']; ?></td>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td>R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format((float) $item['subtotal'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                </div>

                <div class="mt-4">
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

                <button class="btn btn-outline-light rounded-pill fw-bold" onclick="window.print()">IMPRIMIR RECIBO</button>
            </section>
        </main>
        <?php recursosRodape(); ?>
    </body>
</html>
