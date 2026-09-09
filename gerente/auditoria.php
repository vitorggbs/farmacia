<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
exigirLogin('gerente');
$farmaciaId = (int) $_SESSION['farmacia_id'];
$sql = "SELECT a.*, COALESCE(u.nome, adm.nome, 'Sistema') responsavel
        FROM auditoria a
        LEFT JOIN usuarios u ON u.id = a.usuario_id
        LEFT JOIN administradores adm ON adm.id = a.admin_id
        WHERE a.farmacia_id = ? ORDER BY a.id DESC LIMIT 200";
$stmt = mysqli_prepare($conexao,$sql); mysqli_stmt_bind_param($stmt,'i',$farmaciaId); mysqli_stmt_execute($stmt); $logs=mysqli_fetch_all(mysqli_stmt_get_result($stmt),MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Auditoria'); ?></head><body><?php cabecalho('FarmaCerta - Gerente','gerente','auditoria'); ?><main class="container py-4"><section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">AUDITORIA</h2><p class="mb-0">Registro das principais ações realizadas no sistema.</p></section><section class="card shadow-sm rounded-4 p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Data</th><th>Responsável</th><th>Perfil</th><th>Ação</th><th>Item</th><th>Descrição</th></tr></thead><tbody><?php if(!$logs){ ?><tr><td colspan="6" class="text-center text-secondary py-4">Nenhum registro.</td></tr><?php } foreach($logs as $l){ ?><tr><td><?php echo date('d/m/Y H:i',strtotime($l['criado_em'])); ?></td><td><?php echo htmlspecialchars($l['responsavel']); ?></td><td><?php echo htmlspecialchars($l['perfil']); ?></td><td><span class="badge text-bg-light"><?php echo strtoupper(htmlspecialchars($l['acao'])); ?></span></td><td><?php echo htmlspecialchars($l['entidade']); ?><?php echo $l['entidade_id'] ? ' #'.(int)$l['entidade_id'] : ''; ?></td><td><?php echo htmlspecialchars($l['descricao'] ?: '-'); ?></td></tr><?php } ?></tbody></table></div></section></main><?php recursosRodape(); ?></body></html>
