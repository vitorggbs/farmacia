<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';
exigirLogin('gerente');
$farmaciaId=(int)$_SESSION['farmacia_id'];

$busca=trim($_GET['busca']??'');
if($busca!==''){
    $like='%'.$busca.'%';
    $stmt=mysqli_prepare($conexao,"SELECT c.*, COUNT(v.id) compras, COALESCE(SUM(CASE WHEN v.status='concluida' THEN v.valor_total ELSE 0 END),0) total FROM clientes c LEFT JOIN vendas v ON v.farmacia_id=c.farmacia_id AND ((c.cpf IS NOT NULL AND c.cpf<>'' AND v.cpf_cliente=c.cpf) OR (v.cliente=c.nome)) WHERE c.farmacia_id=? AND (c.nome LIKE ? OR c.cpf LIKE ?) GROUP BY c.id ORDER BY c.nome");
    mysqli_stmt_bind_param($stmt,'iss',$farmaciaId,$like,$like);
} else {
    $stmt=mysqli_prepare($conexao,"SELECT c.*, COUNT(v.id) compras, COALESCE(SUM(CASE WHEN v.status='concluida' THEN v.valor_total ELSE 0 END),0) total FROM clientes c LEFT JOIN vendas v ON v.farmacia_id=c.farmacia_id AND ((c.cpf IS NOT NULL AND c.cpf<>'' AND v.cpf_cliente=c.cpf) OR (v.cliente=c.nome)) WHERE c.farmacia_id=? GROUP BY c.id ORDER BY c.nome");
    mysqli_stmt_bind_param($stmt,'i',$farmaciaId);
}
mysqli_stmt_execute($stmt); $clientes=mysqli_fetch_all(mysqli_stmt_get_result($stmt),MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Clientes'); ?></head><body><?php cabecalho('FarmaCerta - Gerente','gerente','clientes'); ?><main class="container py-4">
<section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">CLIENTES</h2><p class="mb-0">Visualize os clientes cadastrados pelos balconistas e acompanhe o histórico de compras.</p></section>
<section class="card shadow-sm rounded-4 p-3"><form class="row g-2 mb-3"><div class="col"><input class="form-control" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por nome ou CPF"></div><div class="col-auto"><button class="btn btn-outline-primary rounded-pill">BUSCAR</button></div></form><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nome</th><th>CPF</th><th>Telefone</th><th>E-mail</th><th>Compras</th><th>Total comprado</th><th>Ação</th></tr></thead><tbody><?php if(!$clientes){ ?><tr><td colspan="7" class="text-center text-secondary py-4">Nenhum cliente cadastrado.</td></tr><?php } foreach($clientes as $c){ ?><tr><td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td><td><?php echo htmlspecialchars($c['cpf']?:'-'); ?></td><td><?php echo htmlspecialchars($c['telefone']?:'-'); ?></td><td><?php echo htmlspecialchars($c['email']?:'-'); ?></td><td><?php echo (int)$c['compras']; ?></td><td>R$ <?php echo number_format($c['total'],2,',','.'); ?></td><td><a class="btn btn-sm btn-outline-primary rounded-pill" href="historicocliente.php?id=<?php echo (int)$c['id']; ?>">HISTÓRICO</a></td></tr><?php } ?></tbody></table></div></section>
</main><?php recursosRodape(); ?></body></html>
