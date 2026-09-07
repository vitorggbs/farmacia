<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('balconista');

$usuarioId = (int) $_SESSION['usuario_id'];
$farmaciaId = (int) $_SESSION['farmacia_id'];
$nomeFarmacia = $_SESSION['farmacia_nome'] ?? 'Farmácia';

$sql = 'SELECT COUNT(*) AS vendas,
        COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE usuario_id = ?
        AND farmacia_id = ?
        AND DATE(data_venda) = CURDATE()';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $usuarioId, $farmaciaId);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$res = mysqli_fetch_assoc($resultado);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Balconista'); ?>
</head>
<body>
    <?php cabecalho('FarmaCerta - Balconista', 'balconista', 'inicio'); ?>
    <main class="container py-4">
        <section class="card card-brand rounded-4 p-4">
            <h2 class="h3 fw-bold">RESUMO DO SEU DIA</h2>
            <p><strong><?php echo htmlspecialchars($nomeFarmacia); ?></strong></p>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <article class="card text-center p-3">
                        <h3 class="h4 text-primary"><?php echo $res['vendas']; ?></h3>
                        <p class="text-secondary mb-0">Vendas realizadas</p>
                    </article>
                </div>
                <div class="col-12 col-md-6">
                    <article class="card text-center p-3">
                        <h3 class="h5 text-primary">R$ <?php echo number_format($res['total'], 2, ',', '.'); ?></h3>
                        <p class="text-secondary mb-0">Total vendido</p>
                    </article>
                </div>
            </div>
            <a class="btn btn-outline-light rounded-pill fw-bold" href="produtosbalconista.php">INICIAR VENDA</a>
        </section>
    </main>
    <?php recursosRodape(); ?>
</body>
</html>
