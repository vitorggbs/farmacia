<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('balconista');

$usuarioId = (int) $_SESSION['usuario_id'];
$farmaciaId = (int) $_SESSION['farmacia_id'];

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Balconista</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php cabecalho('FarmaCerta - Balconista','balconista','inicio'); ?>
    <main class="container">
        <div class="card">
            <h2>RESUMO DO SEU DIA</h2>
            <p><strong><?php echo htmlspecialchars($nomeFarmacia); ?></strong></p>
            <div class="painel-inicio">
                <div class="bloco">
                    <h3><?php echo $res['vendas']; ?></h3>
                    <p>Vendas realizadas</p>
                </div>
                <div class="bloco">
                    <h3>R$ <?php echo number_format($res['total'], 2, ',', '.'); ?></h3>
                    <p>Total vendido</p>
                </div>
            </div>
            <a class="botao" href="produtosbalconista.php">INICIAR VENDA</a>
        </div>
    </main>
</body>
</html>
