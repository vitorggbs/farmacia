<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';
exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');

    if ($nome === '') {
        header('Location: fornecedores.php?erro=nome');
        exit;
    }

    $sql = 'INSERT INTO fornecedores (farmacia_id, nome, cnpj, telefone, email, endereco)
            VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'isssss', $farmaciaId, $nome, $cnpj, $telefone, $email, $endereco);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conexao);
    registrarAuditoria($conexao, 'cadastrar', 'fornecedor', $id, 'Fornecedor ' . $nome . ' cadastrado');
    header('Location: fornecedores.php?ok=1');
    exit;
}

if (isset($_GET['status'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $ativo = $_GET['status'] === 'ativar' ? 1 : 0;
    $stmt = mysqli_prepare($conexao, 'UPDATE fornecedores SET ativo = ? WHERE id = ? AND farmacia_id = ?');
    mysqli_stmt_bind_param($stmt, 'iii', $ativo, $id, $farmaciaId);
    mysqli_stmt_execute($stmt);
    registrarAuditoria($conexao, $ativo ? 'ativar' : 'desativar', 'fornecedor', $id, 'Status do fornecedor alterado');
    header('Location: fornecedores.php');
    exit;
}

$stmt = mysqli_prepare($conexao, 'SELECT * FROM fornecedores WHERE farmacia_id = ? ORDER BY nome');
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$fornecedores = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><?php recursosCabeca('Fornecedores'); ?></head>
<body>
<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'fornecedores'); ?>
<main class="container py-4">
    <section class="card card-brand rounded-4 p-4 mb-4">
        <h2 class="h3 fw-bold">FORNECEDORES</h2>
        <p class="mb-0">Cadastre os fornecedores utilizados pela sua farmácia.</p>
    </section>

    <?php if (isset($_GET['editado'])) { ?><div class="alert alert-success">Fornecedor atualizado com sucesso.</div><?php } ?>
    <?php if (isset($_GET['ok'])) { ?><div class="alert alert-success">Fornecedor cadastrado com sucesso.</div><?php } ?>
    <?php if (isset($_GET['erro'])) { ?><div class="alert alert-danger">Informe o nome do fornecedor.</div><?php } ?>

    <section class="card shadow-sm rounded-4 p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">NOVO FORNECEDOR</h3>
        <form method="POST" class="row g-3">
            <div class="col-12 col-md-6"><label class="form-label">Nome *</label><input class="form-control" name="nome" required></div>
            <div class="col-12 col-md-6"><label class="form-label">CNPJ</label><input class="form-control" name="cnpj"></div>
            <div class="col-12 col-md-4"><label class="form-label">Telefone</label><input class="form-control" name="telefone"></div>
            <div class="col-12 col-md-4"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email"></div>
            <div class="col-12 col-md-4"><label class="form-label">Endereço</label><input class="form-control" name="endereco"></div>
            <div class="col-12"><button class="btn btn-primary rounded-pill px-4">CADASTRAR</button></div>
        </form>
    </section>

    <section class="card shadow-sm rounded-4 p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Nome</th><th>CNPJ</th><th>Telefone</th><th>E-mail</th><th>Status</th><th>Ação</th></tr></thead>
                <tbody>
                <?php if (!$fornecedores) { ?><tr><td colspan="6" class="text-center text-secondary py-4">Nenhum fornecedor cadastrado.</td></tr><?php } ?>
                <?php foreach ($fornecedores as $f) { ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($f['cnpj'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['telefone'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['email'] ?: '-'); ?></td>
                        <td><span class="badge <?php echo $f['ativo'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $f['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                        <td class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-secondary rounded-pill" href="editarfornecedor.php?id=<?php echo $f['id']; ?>">EDITAR</a><a class="btn btn-sm btn-outline-primary rounded-pill" href="historicofornecedor.php?id=<?php echo $f['id']; ?>">HISTÓRICO</a><a class="btn btn-sm btn-outline-danger rounded-pill" href="fornecedores.php?id=<?php echo $f['id']; ?>&status=<?php echo $f['ativo'] ? 'desativar' : 'ativar'; ?>"><?php echo $f['ativo'] ? 'DESATIVAR' : 'ATIVAR'; ?></a></td>
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
