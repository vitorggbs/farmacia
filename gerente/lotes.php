<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';
exigirLogin('gerente');
$farmaciaId=(int)$_SESSION['farmacia_id'];
$usuarioId=(int)$_SESSION['usuario_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $produtoId=(int)($_POST['produto_id']??0); $numero=trim($_POST['numero_lote']??''); $qtd=(int)($_POST['quantidade']??0); $validade=$_POST['validade']??'';
    if($produtoId>0 && $numero!=='' && $qtd>0 && preg_match('/^\d{4}-\d{2}-\d{2}$/',$validade)){
        mysqli_begin_transaction($conexao);
        try{
            $stmt=mysqli_prepare($conexao,'INSERT INTO lotes (farmacia_id,produto_id,numero_lote,quantidade,validade) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE quantidade=quantidade+VALUES(quantidade), validade=VALUES(validade)');
            mysqli_stmt_bind_param($stmt,'iisis',$farmaciaId,$produtoId,$numero,$qtd,$validade); mysqli_stmt_execute($stmt);
            $stmt=mysqli_prepare($conexao,'SELECT id FROM lotes WHERE produto_id=? AND numero_lote=? LIMIT 1'); mysqli_stmt_bind_param($stmt,'is',$produtoId,$numero); mysqli_stmt_execute($stmt); $loteId=(int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['id'];
            $stmt=mysqli_prepare($conexao,'UPDATE produtos SET quantidade=quantidade+? WHERE id=? AND farmacia_id=?'); mysqli_stmt_bind_param($stmt,'iii',$qtd,$produtoId,$farmaciaId); mysqli_stmt_execute($stmt);
            $tipo='entrada'; $obs='Cadastro/entrada manual do lote '.$numero;
            $stmt=mysqli_prepare($conexao,'INSERT INTO movimentacoes_estoque (farmacia_id,produto_id,usuario_id,lote_id,tipo,quantidade,observacao) VALUES (?,?,?,?,?,?,?)'); mysqli_stmt_bind_param($stmt,'iiiisis',$farmaciaId,$produtoId,$usuarioId,$loteId,$tipo,$qtd,$obs); mysqli_stmt_execute($stmt);
            registrarAuditoria($conexao,'cadastrar','lote',$loteId,'Lote '.$numero.' cadastrado/atualizado'); mysqli_commit($conexao); header('Location: lotes.php?ok=1'); exit;
        }catch(Throwable $e){ mysqli_rollback($conexao); header('Location: lotes.php?erro=1'); exit; }
    }
}
$produtos=mysqli_query($conexao,"SELECT id,nome FROM produtos WHERE farmacia_id=$farmaciaId AND ativo=1 ORDER BY nome");
$lotes=mysqli_query($conexao,"SELECT l.*,p.nome produto,DATEDIFF(l.validade,CURDATE()) dias FROM lotes l INNER JOIN produtos p ON p.id=l.produto_id WHERE l.farmacia_id=$farmaciaId ORDER BY l.validade,l.id");
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Lotes e validade'); ?></head><body><?php cabecalho('FarmaCerta - Gerente','gerente','lotes'); ?>
<main class="container py-4">
<section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">LOTES E VALIDADE</h2><p class="mb-0">Controle os lotes e veja rapidamente o que está vencido ou próximo do vencimento.</p></section>
<?php if(isset($_GET['ok'])){ ?><div class="alert alert-success">Lote registrado e estoque atualizado.</div><?php } if(isset($_GET['erro'])){ ?><div class="alert alert-danger">Não foi possível registrar o lote.</div><?php } ?>
<section class="card shadow-sm rounded-4 p-4 mb-4"><h3 class="h5 fw-bold">REGISTRAR LOTE MANUALMENTE</h3><form method="POST" class="row g-3"><div class="col-12 col-md-4"><label class="form-label">Produto</label><select name="produto_id" class="form-select" required><option value="">Selecione</option><?php while($p=mysqli_fetch_assoc($produtos)){ ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option><?php } ?></select></div><div class="col-12 col-md-3"><label class="form-label">Lote</label><input class="form-control" name="numero_lote" required></div><div class="col-6 col-md-2"><label class="form-label">Quantidade</label><input class="form-control" type="number" min="1" name="quantidade" required></div><div class="col-6 col-md-3"><label class="form-label">Validade</label><input class="form-control" type="date" name="validade" required></div><div class="col-12"><button class="btn btn-primary rounded-pill">REGISTRAR LOTE</button></div></form></section>
<section class="card shadow-sm rounded-4 p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Produto</th><th>Lote</th><th>Qtd.</th><th>Validade</th><th>Situação</th></tr></thead><tbody><?php while($l=mysqli_fetch_assoc($lotes)){ $dias=(int)$l['dias']; $badge=$dias<0?'text-bg-danger':($dias<=30?'text-bg-warning':'text-bg-success'); $texto=$dias<0?'Vencido':($dias<=30?'Vence em '.$dias.' dia(s)':'OK'); ?><tr><td><?php echo htmlspecialchars($l['produto']); ?></td><td><?php echo htmlspecialchars($l['numero_lote']); ?></td><td><?php echo (int)$l['quantidade']; ?></td><td><?php echo date('d/m/Y',strtotime($l['validade'])); ?></td><td><span class="badge <?php echo $badge; ?>"><?php echo $texto; ?></span></td></tr><?php } ?></tbody></table></div></section>
</main><?php recursosRodape(); ?></body></html>