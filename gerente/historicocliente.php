<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');
$farmaciaId = (int) $_SESSION['farmacia_id'];
$clienteId = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conexao, 'SELECT id, nome, cpf, telefone, email, criado_em FROM clientes WHERE id = ? AND farmacia_id = ?');
mysqli_stmt_bind_param($stmt, 'ii', $clienteId, $farmaciaId);
mysqli_stmt_execute($stmt);
$cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$cliente) { header('Location: clientes.php'); exit; }

$sqlVendas = "SELECT v.id, v.data_venda, v.valor_total, v.forma_pagamento, v.status
              FROM vendas v
              WHERE v.farmacia_id = ?
                AND ((? <> '' AND v.cpf_cliente = ?) OR ((? = '' OR ? IS NULL) AND v.cliente = ?))
              ORDER BY v.data_venda DESC";
$cpf = $cliente['cpf'] ?? '';
$stmt = mysqli_prepare($conexao, $sqlVendas);
mysqli_stmt_bind_param($stmt, 'isssss', $farmaciaId, $cpf, $cpf, $cpf, $cpf, $cliente['nome']);
mysqli_stmt_execute($stmt);
$vendas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$sqlProdutos = "SELECT p.nome, SUM(iv.quantidade) AS quantidade_comprada, SUM(iv.subtotal) AS valor_total
                FROM vendas v
                INNER JOIN itens_venda iv ON iv.venda_id = v.id
                INNER JOIN produtos p ON p.id = iv.produto_id
                WHERE v.farmacia_id = ?
                  AND v.status = 'concluida'
                  AND ((? <> '' AND v.cpf_cliente = ?) OR ((? = '' OR ? IS NULL) AND v.cliente = ?))
                GROUP BY p.id, p.nome
                ORDER BY quantidade_comprada DESC, valor_total DESC
                LIMIT 10";
$stmt = mysqli_prepare($conexao, $sqlProdutos);
mysqli_stmt_bind_param($stmt, 'isssss', $farmaciaId, $cpf, $cpf, $cpf, $cpf, $cliente['nome']);
mysqli_stmt_execute($stmt);
$produtos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$totalCompras = 0;
$valorComprado = 0;
foreach ($vendas as $venda) {
    if ($venda['status'] === 'concluida') {
        $totalCompras++;
        $valorComprado += (float) $venda['valor_total'];
    }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Histórico do Cliente'); ?></head><body>
<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'clientes'); ?>
<main class="container py-4">
<section class="card card-brand rounded-4 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div><h2 class="h3 fw-bold mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h2><p class="mb-0">CPF: <?php echo htmlspecialchars($cliente['cpf'] ?: 'Não informado'); ?></p></div>
        <a class="btn btn-outline-light rounded-pill" href="clientes.php">VOLTAR</a>
    </div>
</section>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card shadow-sm rounded-4 p-4 h-100"><span class="text-secondary">Compras concluídas</span><strong class="fs-2"><?php echo $totalCompras; ?></strong></div></div>
    <div class="col-md-6"><div class="card shadow-sm rounded-4 p-4 h-100"><span class="text-secondary">Total comprado</span><strong class="fs-2">R$ <?php echo number_format($valorComprado, 2, ',', '.'); ?></strong></div></div>
</div>
<section class="card shadow-sm rounded-4 p-4 mb-4">
    <h3 class="h5 fw-bold mb-3">PRODUTOS MAIS COMPRADOS</h3>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Posição</th><th>Produto</th><th>Unidades compradas</th><th>Valor gasto</th></tr></thead><tbody>
    <?php if (!$produtos) { ?><tr><td colspan="4" class="text-center text-secondary py-4">Este cliente ainda não possui compras concluídas.</td></tr><?php } ?>
    <?php foreach ($produtos as $i => $produto) { ?><tr><td>#<?php echo $i + 1; ?></td><td><strong><?php echo htmlspecialchars($produto['nome']); ?></strong></td><td><?php echo (int) $produto['quantidade_comprada']; ?></td><td>R$ <?php echo number_format($produto['valor_total'], 2, ',', '.'); ?></td></tr><?php } ?>
    </tbody></table></div>
</section>
<section class="card shadow-sm rounded-4 p-4">
    <h3 class="h5 fw-bold mb-3">HISTÓRICO DE COMPRAS</h3>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Venda</th><th>Data</th><th>Pagamento</th><th>Valor</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$vendas) { ?><tr><td colspan="6" class="text-center text-secondary py-4">Nenhuma compra encontrada.</td></tr><?php } ?>
    <?php foreach ($vendas as $venda) { ?><tr><td>#<?php echo (int)$venda['id']; ?></td><td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td><td><?php echo htmlspecialchars(ucfirst($venda['forma_pagamento'])); ?></td><td>R$ <?php echo number_format($venda['valor_total'],2,',','.'); ?></td><td><span class="badge <?php echo $venda['status']==='cancelada'?'text-bg-danger':'text-bg-success'; ?>"><?php echo strtoupper($venda['status']); ?></span></td><td><a class="btn btn-sm btn-outline-primary rounded-pill" href="../balconista/recibobalconista.php?id=<?php echo (int)$venda['id']; ?>">VER RECIBO</a></td></tr><?php } ?>
    </tbody></table></div>
</section>
</main><?php recursosRodape(); ?></body></html>
