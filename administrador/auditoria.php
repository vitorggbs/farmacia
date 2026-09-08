<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';
exigirAdministrador();

$sql = "SELECT a.*, f.nome farmacia, COALESCE(u.nome, adm.nome, 'Sistema') responsavel
        FROM auditoria a
        LEFT JOIN farmacias f ON f.id = a.farmacia_id
        LEFT JOIN usuarios u ON u.id = a.usuario_id
        LEFT JOIN administradores adm ON adm.id = a.admin_id
        ORDER BY a.id DESC LIMIT 300";
$logs = mysqli_query($conexao, $sql);
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Auditoria do sistema'); ?></head><body>
<?php cabecalhoAdmin('FarmaCerta - Administrador'); ?>
<main class="container py-4"><section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">AUDITORIA DO SISTEMA</h2><p class="mb-0">Acompanhe as principais ações registradas pelas farmácias.</p></section><section class="card shadow-sm rounded-4 p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Data</th><th>Farmácia</th><th>Responsável</th><th>Perfil</th><th>Ação</th><th>Item</th><th>Descrição</th></tr></thead><tbody><?php while($l=mysqli_fetch_assoc($logs)){ ?><tr><td><?php echo date('d/m/Y H:i',strtotime($l['criado_em'])); ?></td><td><?php echo htmlspecialchars($l['farmacia'] ?: '-'); ?></td><td><?php echo htmlspecialchars($l['responsavel']); ?></td><td><?php echo htmlspecialchars($l['perfil']); ?></td><td><?php echo strtoupper(htmlspecialchars($l['acao'])); ?></td><td><?php echo htmlspecialchars($l['entidade']); ?><?php echo $l['entidade_id'] ? ' #'.(int)$l['entidade_id'] : ''; ?></td><td><?php echo htmlspecialchars($l['descricao'] ?: '-'); ?></td></tr><?php } ?></tbody></table></div></section></main><?php recursosRodape(); ?></body></html>
