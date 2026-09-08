<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';
require_once __DIR__ . '/../includes/permissoes.php';
exigirLogin('gerente');
exigirPermissaoGerente('cancelar_venda');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];
$vendaId = (int) ($_POST['venda_id'] ?? $_GET['id'] ?? 0);

if ($vendaId < 1) {
    header('Location: historicorecibogerente.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');
    if ($motivo === '') {
        header('Location: cancelarvenda.php?id=' . $vendaId . '&erro=motivo');
        exit;
    }

    mysqli_begin_transaction($conexao);
    try {
        $stmt = mysqli_prepare($conexao, "SELECT status FROM vendas WHERE id = ? AND farmacia_id = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'ii', $vendaId, $farmaciaId);
        mysqli_stmt_execute($stmt);
        $venda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$venda || $venda['status'] !== 'concluida') {
            throw new Exception('Venda inválida');
        }

        $stmt = mysqli_prepare($conexao, 'SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $vendaId);
        mysqli_stmt_execute($stmt);
        $itens = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

        foreach ($itens as $item) {
            $produtoId = (int) $item['produto_id'];
            $quantidade = (int) $item['quantidade'];

            $stmt = mysqli_prepare($conexao, 'UPDATE produtos SET quantidade = quantidade + ? WHERE id = ? AND farmacia_id = ?');
            mysqli_stmt_bind_param($stmt, 'iii', $quantidade, $produtoId, $farmaciaId);
            mysqli_stmt_execute($stmt);

            $stmtLotes = mysqli_prepare($conexao, 'SELECT ivl.lote_id, ivl.quantidade FROM itens_venda_lotes ivl INNER JOIN itens_venda iv ON iv.id = ivl.item_venda_id WHERE iv.venda_id = ? AND iv.produto_id = ?');
            mysqli_stmt_bind_param($stmtLotes, 'ii', $vendaId, $produtoId);
            mysqli_stmt_execute($stmtLotes);
            $lotesEstorno = mysqli_stmt_get_result($stmtLotes);
            while ($lote = mysqli_fetch_assoc($lotesEstorno)) {
                $stmtL = mysqli_prepare($conexao, 'UPDATE lotes SET quantidade = quantidade + ? WHERE id = ?');
                mysqli_stmt_bind_param($stmtL, 'ii', $lote['quantidade'], $lote['lote_id']);
                mysqli_stmt_execute($stmtL);
            }

            $tipo = 'entrada';
            $observacao = 'Estorno da venda #' . $vendaId;
            $stmt = mysqli_prepare($conexao, 'INSERT INTO movimentacoes_estoque (farmacia_id, produto_id, usuario_id, tipo, quantidade, observacao) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iiisis', $farmaciaId, $produtoId, $usuarioId, $tipo, $quantidade, $observacao);
            mysqli_stmt_execute($stmt);
        }

        $stmt = mysqli_prepare($conexao, "UPDATE vendas SET status = 'cancelada', cancelada_em = NOW(), cancelada_por = ?, motivo_cancelamento = ? WHERE id = ? AND farmacia_id = ?");
        mysqli_stmt_bind_param($stmt, 'isii', $usuarioId, $motivo, $vendaId, $farmaciaId);
        mysqli_stmt_execute($stmt);

        registrarAuditoria($conexao, 'cancelar', 'venda', $vendaId, 'Venda cancelada. Motivo: ' . $motivo);
        mysqli_commit($conexao);
        header('Location: historicorecibogerente.php?cancelada=1');
        exit;
    } catch (Throwable $e) {
        mysqli_rollback($conexao);
        header('Location: historicorecibogerente.php?erro=cancelamento');
        exit;
    }
}

$stmt = mysqli_prepare($conexao, "SELECT v.id, v.valor_total, v.data_venda, v.cliente, v.status, u.nome usuario
    FROM vendas v INNER JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.id = ? AND v.farmacia_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $vendaId, $farmaciaId);
mysqli_stmt_execute($stmt);
$venda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$venda || $venda['status'] !== 'concluida') {
    header('Location: historicorecibogerente.php?erro=cancelamento');
    exit;
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Cancelar venda'); ?></head><body>
<?php cabecalho('FarmaCerta - Gerente','gerente','recibos'); ?>
<main class="container py-4"><section class="card shadow-sm rounded-4 p-4 mx-auto" style="max-width:700px"><h2 class="h3 fw-bold text-danger">CANCELAR VENDA #<?php echo $vendaId; ?></h2><p><strong>Data:</strong> <?php echo date('d/m/Y H:i',strtotime($venda['data_venda'])); ?></p><p><strong>Cliente:</strong> <?php echo htmlspecialchars($venda['cliente'] ?: 'Não informado'); ?></p><p><strong>Balconista:</strong> <?php echo htmlspecialchars($venda['usuario']); ?></p><p><strong>Valor:</strong> R$ <?php echo number_format($venda['valor_total'],2,',','.'); ?></p><div class="alert alert-warning">Ao confirmar, todos os itens desta venda voltarão para o estoque. A venda continuará registrada como cancelada.</div><?php if(isset($_GET['erro'])){ ?><div class="alert alert-danger">Informe o motivo do cancelamento.</div><?php } ?><form method="POST"><input type="hidden" name="venda_id" value="<?php echo $vendaId; ?>"><label class="form-label fw-bold">Motivo</label><textarea class="form-control mb-3" name="motivo" rows="4" required></textarea><div class="d-flex gap-2"><a class="btn btn-secondary rounded-pill" href="historicorecibogerente.php">VOLTAR</a><button class="btn btn-danger rounded-pill">CONFIRMAR CANCELAMENTO</button></div></form></section></main><?php recursosRodape(); ?></body></html>
