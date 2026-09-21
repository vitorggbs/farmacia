<?php
require_once __DIR__ . '/../includes/seguranca.php';
require_once __DIR__.'/../autenticacao.php';
require_once __DIR__.'/conexaoDB.php';
require_once __DIR__.'/../cabecalho.php';
require_once __DIR__.'/../includes/auditoria.php';
exigirLogin('gerente');
$farmaciaId=(int)$_SESSION['farmacia_id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['alterar_status'], $_POST['id'])) {
        $id=(int)$_POST['id'];
        $ativo=$_POST['alterar_status']==='ativar'?1:0;
        $stmt=mysqli_prepare($conexao,'UPDATE categorias SET ativo=? WHERE id=? AND farmacia_id=?');
        mysqli_stmt_bind_param($stmt,'iii',$ativo,$id,$farmaciaId);
        mysqli_stmt_execute($stmt);
        registrarAuditoria($conexao,'alterar_status','categoria',$id,'Status de categoria atualizado');
        header('Location: categorias.php'); exit;
    }
    $nome=trim($_POST['nome']??'');
    if ($nome!=='' && mb_strlen($nome)<=80) {
        $stmt=mysqli_prepare($conexao,'INSERT INTO categorias (farmacia_id,nome) VALUES (?,?)');
        mysqli_stmt_bind_param($stmt,'is',$farmaciaId,$nome);
        if(mysqli_stmt_execute($stmt)) {
            registrarAuditoria($conexao,'cadastrar','categoria',mysqli_insert_id($conexao),'Categoria cadastrada');
            header('Location: categorias.php?ok=1'); exit;
        }
    }
    header('Location: categorias.php?erro=1'); exit;
}
$stmt=mysqli_prepare($conexao,'SELECT c.*,COUNT(p.id) produtos FROM categorias c LEFT JOIN produtos p ON p.categoria_id=c.id AND p.farmacia_id=c.farmacia_id WHERE c.farmacia_id=? GROUP BY c.id ORDER BY c.nome');
mysqli_stmt_bind_param($stmt,'i',$farmaciaId); mysqli_stmt_execute($stmt); $categorias=mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html><html lang="pt-BR"><head><?php recursosCabeca('Categorias'); ?></head><body>
<?php cabecalho('FarmaCerta - Gerente','gerente','produtos'); ?>
<main class="container py-4"><section class="card card-brand rounded-4 p-4 mb-4"><h2 class="h3 fw-bold">CATEGORIAS DE PRODUTOS</h2><p class="mb-0">Categorias exclusivas desta farmácia.</p></section>
<?php if(isset($_GET['ok'])):?><div class="alert alert-success">Categoria cadastrada.</div><?php endif; ?>
<?php if(isset($_GET['erro'])):?><div class="alert alert-danger">Não foi possível cadastrar. Verifique o nome.</div><?php endif; ?>
<section class="card shadow-sm rounded-4 p-4 mb-4"><form method="POST" class="row g-3"><?php echo campoCsrf(); ?><div class="col-md-8"><label class="form-label">Nome da categoria</label><input class="form-control" name="nome" maxlength="80" required></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary rounded-pill">CADASTRAR</button></div></form></section>
<section class="card shadow-sm rounded-4 p-3"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Categoria</th><th>Produtos</th><th>Status</th><th>Ação</th></tr></thead><tbody>
<?php while($c=mysqli_fetch_assoc($categorias)): ?><tr><td><?php echo htmlspecialchars($c['nome'],ENT_QUOTES,'UTF-8'); ?></td><td><?php echo (int)$c['produtos']; ?></td><td><?php echo $c['ativo']?'Ativa':'Inativa'; ?></td><td><form method="POST"><?php echo campoCsrf(); ?><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>"><button class="btn btn-sm btn-outline-primary rounded-pill" name="alterar_status" value="<?php echo $c['ativo']?'desativar':'ativar'; ?>"><?php echo $c['ativo']?'DESATIVAR':'ATIVAR'; ?></button></form></td></tr><?php endwhile; ?>
</tbody></table></div></section></main><?php recursosRodape(); ?></body></html>
