<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
exigirLogin('gerente');

$periodo = $_GET['periodo'] ?? 'dia';
$pagamento = $_GET['pagamento'] ?? '';
$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = "SELECT v.*, u.nome AS usuario
        FROM vendas v INNER JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.farmacia_id = ?";
$tipos='i'; $params=array($farmaciaId);
if($periodo==='dia') $sql .= ' AND DATE(v.data_venda)=CURDATE()';
$formas=array('dinheiro','pix','debito','credito');
if(in_array($pagamento,$formas,true)){ $sql.=' AND v.forma_pagamento=?'; $tipos.='s'; $params[]=$pagamento; }
$sql.=' ORDER BY v.id DESC';
$stmt=mysqli_prepare($conexao,$sql);
mysqli_stmt_bind_param($stmt,$tipos,...$params);
mysqli_stmt_execute($stmt);
$vendas=mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Recibos'); ?></head><body>
<?php cabecalho('FarmaCerta - Gerente','gerente','recibos'); ?>
<main class="container py-4">
<section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">RECIBOS E VENDAS</h2><form method="GET" class="row g-3 align-items-end"><div class="col-12 col-md-4"><label class="form-label fw-bold">Período</label><select class="form-select" name="periodo"><option value="dia">Hoje</option><option value="todos" <?php echo $periodo==='todos'?'selected':''; ?>>Todos</option></select></div><div class="col-12 col-md-4"><label class="form-label fw-bold">Pagamento</label><select class="form-select" name="pagamento"><option value="">Todos</option><?php foreach($formas as $forma){ ?><option value="<?php echo $forma; ?>" <?php echo $pagamento===$forma?'selected':''; ?>><?php echo ucfirst($forma); ?></option><?php } ?></select></div><div class="col-12 col-md-4"><button class="btn btn-outline-light rounded-pill fw-bold">FILTRAR</button></div></form></section>
<?php if(isset($_GET['cancelada'])){ ?><div class="alert alert-success">Venda cancelada e produtos devolvidos ao estoque.</div><?php } ?>
<?php if(isset($_GET['erro'])){ ?><div class="alert alert-danger">Não foi possível cancelar a venda.</div><?php } ?>
<section class="card shadow-sm rounded-4 p-3"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Número</th><th>Data</th><th>Cliente</th><th>Balconista</th><th>Pagamento</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php while($v=mysqli_fetch_assoc($vendas)){ ?><tr><td>#<?php echo $v['id']; ?></td><td><?php echo date('d/m/Y H:i',strtotime($v['data_venda'])); ?></td><td><?php echo htmlspecialchars($v['cliente']?:'Não informado'); ?></td><td><?php echo htmlspecialchars($v['usuario']); ?></td><td><?php echo htmlspecialchars($v['forma_pagamento']); ?></td><td>R$ <?php echo number_format($v['valor_total'],2,',','.'); ?></td><td><span class="badge <?php echo $v['status']==='cancelada'?'text-bg-danger':'text-bg-success'; ?>"><?php echo strtoupper($v['status']); ?></span></td><td class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-primary rounded-pill" href="../balconista/recibobalconista.php?id=<?php echo $v['id']; ?>">ABRIR</a><?php if($v['status']==='concluida'){ ?><a class="btn btn-sm btn-outline-danger rounded-pill" href="cancelarvenda.php?id=<?php echo $v['id']; ?>">CANCELAR</a><?php } ?></td></tr><?php } ?>
</tbody></table></div></section></main><?php recursosRodape(); ?></body></html>
