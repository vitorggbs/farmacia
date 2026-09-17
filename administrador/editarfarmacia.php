<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: farmacias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');

    if ($nome == '') {
        header('Location: editarfarmacia.php?id=' . $id . '&erro=1');
        exit;
    }

    $sql = 'UPDATE farmacias SET nome = ?, cnpj = ?, telefone = ?, endereco = ? WHERE id = ?';
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssi', $nome, $cnpj, $telefone, $endereco, $id);
    mysqli_stmt_execute($stmt);

    header('Location: farmacias.php?ok=1');
    exit;
}

$sql = 'SELECT * FROM farmacias WHERE id = ?';
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$farmacia = mysqli_fetch_assoc($resultado);

if (!$farmacia) {
    header('Location: farmacias.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Editar farmácia'); ?>
</head>
<body>
    <?php cabecalhoAdmin('FarmaCerta - Editar Farmácia'); ?>

    <main class="container py-4">
        <?php if (isset($_GET['erro'])) { ?>
            <div class="alert alert-danger" role="alert">Informe o nome da farmácia.</div>
        <?php } ?>

        <section class="card card-brand rounded-4 p-4">
            <h2 class="h3 fw-bold">EDITAR FARMÁCIA</h2>
            <form method="POST" class="row g-3">
                <input type="hidden" name="id" value="<?php echo $farmacia['id']; ?>">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold">NOME *</label>
                    <input class="form-control" type="text" name="nome" value="<?php echo htmlspecialchars($farmacia['nome']); ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold">CNPJ</label>
                    <input class="form-control" type="text" name="cnpj" value="<?php echo htmlspecialchars($farmacia['cnpj']); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold">TELEFONE</label>
                    <input class="form-control" type="text" name="telefone" value="<?php echo htmlspecialchars($farmacia['telefone']); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold">STATUS</label>
                    <input class="form-control" type="text" value="<?php echo $farmacia['ativo'] ? 'Ativa' : 'Inativa'; ?>" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">ENDEREÇO</label>
                    <input class="form-control" type="text" name="endereco" value="<?php echo htmlspecialchars($farmacia['endereco']); ?>">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-light rounded-pill fw-bold" type="submit">SALVAR ALTERAÇÕES</button>
                    <a class="btn btn-outline-light rounded-pill" href="farmacias.php">VOLTAR</a>
                </div>
            </form>
        </section>
    </main>
    <?php recursosRodape(); ?>
</body>
</html>
