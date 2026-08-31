<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = 'SELECT COUNT(*) AS vendas,
        COALESCE(SUM(valor_total), 0) AS total,
        COALESCE(AVG(valor_total), 0) AS media
        FROM vendas
        WHERE farmacia_id = ?
        AND DATE(data_venda) = CURDATE()';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$res = mysqli_fetch_assoc($resultado);

$sql = 'SELECT v.id, v.data_venda, v.forma_pagamento,
        v.valor_total, u.nome
        FROM vendas v
        INNER JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.farmacia_id = ?
        ORDER BY v.id DESC
        LIMIT 10';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$ultimas = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gerente</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php cabecalho('FarmaCerta - Gerente','gerente','inicio'); ?>
    <main class="container">
        <div class="card">
            <h2>RESUMO DO DIA</h2>
            <p><strong><?php echo htmlspecialchars($nomeFarmacia); ?></strong></p>
            <div class="painel-inicio">
                <div class="bloco">
                    <h3>R$ <?php echo number_format($res['total'], 2, ',', '.'); ?></h3>
                    <p>Faturamento</p>
                </div>
                <div class="bloco">
                    <h3><?php echo $res['vendas']; ?></h3>
                    <p>Vendas</p>
                </div>
                <div class="bloco">
                    <h3><?php echo $res['vendas']; ?></h3>
                    <p>Recibos</p>
                </div>
                <div class="bloco">
                    <h3>R$ <?php echo number_format($res['media'], 2, ',', '.'); ?></h3>
                    <p>Media por venda</p>
                </div>
            </div>
        </div>

        <section class="secao-branca">
            <h2>ULTIMAS VENDAS</h2>
            <table class="tabela">
                <tr>
                    <th>Data</th>
                    <th>Recibo</th>
                    <th>Balconista</th>
                    <th>Pagamento</th>
                    <th>Valor</th>
                </tr>
                <?php while ($v = mysqli_fetch_assoc($ultimas)) { ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($v['data_venda'])); ?></td>
                        <td>#<?php echo $v['id']; ?></td>
                        <td><?php echo htmlspecialchars($v['nome']); ?></td>
                        <td><?php echo htmlspecialchars($v['forma_pagamento']); ?></td>
                        <td>R$ <?php echo number_format($v['valor_total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </main>
</body>
</html>
