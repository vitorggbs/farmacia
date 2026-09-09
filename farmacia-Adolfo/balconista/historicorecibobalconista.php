<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('balconista');

$usuarioId = (int) $_SESSION['usuario_id'];
$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = 'SELECT * FROM vendas
        WHERE usuario_id = ? AND farmacia_id = ?
        ORDER BY id DESC';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $usuarioId, $farmaciaId);
mysqli_stmt_execute($stmt);
$vendas = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Histórico'); ?>
</head>
<body>
    <?php cabecalho('FarmaCerta - Balconista', 'balconista', 'historico'); ?>

    <main class="container py-4">
        <section class="card shadow-sm rounded-4 p-4">
            <h2 class="h4 fw-bold">MEUS RECIBOS</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Pagamento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($v = mysqli_fetch_assoc($vendas)) { ?>
                        <tr>
                            <td>#<?php echo $v['id']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['data_venda'])); ?></td>
                            <td><?php echo htmlspecialchars($v['cliente'] ?: 'Nao informado'); ?></td>
                            <td><?php echo htmlspecialchars($v['forma_pagamento']); ?></td>
                            <td>R$ <?php echo number_format($v['valor_total'], 2, ',', '.'); ?></td>
                            <td><span class="badge <?php echo ($v['status'] ?? 'concluida') === 'cancelada' ? 'text-bg-danger' : 'text-bg-success'; ?>"><?php echo strtoupper($v['status'] ?? 'concluida'); ?></span></td>
                            <td>
                                <a class="btn btn-sm btn-primary rounded-pill" href="recibobalconista.php?id=<?php echo $v['id']; ?>">ABRIR</a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php recursosRodape(); ?>
</body>
</html>
