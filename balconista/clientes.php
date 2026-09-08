<?php
session_start();
require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigirLogin('balconista');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nome === '') {
        $erro = 'Informe o nome do cliente.';
    } elseif ($cpf !== '' && strlen($cpf) !== 11) {
        $erro = 'O CPF deve ter 11 números.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        if ($cpf !== '') {
            $stmt = mysqli_prepare($conexao, 'SELECT id FROM clientes WHERE farmacia_id = ? AND cpf = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'is', $farmaciaId, $cpf);
            mysqli_stmt_execute($stmt);
            $existente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } else {
            $existente = null;
        }

        if ($existente) {
            $erro = 'Já existe um cliente cadastrado com esse CPF.';
        } else {
            $cpfBanco = $cpf === '' ? null : $cpf;
            $stmt = mysqli_prepare($conexao, 'INSERT INTO clientes (farmacia_id, nome, cpf, telefone, email) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'issss', $farmaciaId, $nome, $cpfBanco, $telefone, $email);

            if (mysqli_stmt_execute($stmt)) {
                $clienteId = mysqli_insert_id($conexao);
                registrarAuditoria($conexao, 'cadastrar', 'cliente', $clienteId, 'Cliente ' . $nome . ' cadastrado pelo balconista.');
                $mensagem = 'Cliente cadastrado com sucesso.';
            } else {
                $erro = 'Não foi possível cadastrar o cliente.';
            }
        }
    }
}

$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $like = '%' . $busca . '%';
    $stmt = mysqli_prepare($conexao, 'SELECT id, nome, cpf, telefone, email, criado_em FROM clientes WHERE farmacia_id = ? AND (nome LIKE ? OR cpf LIKE ?) ORDER BY nome');
    mysqli_stmt_bind_param($stmt, 'iss', $farmaciaId, $like, $like);
} else {
    $stmt = mysqli_prepare($conexao, 'SELECT id, nome, cpf, telefone, email, criado_em FROM clientes WHERE farmacia_id = ? ORDER BY nome');
    mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
}
mysqli_stmt_execute($stmt);
$clientes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><?php recursosCabeca('FarmaCerta - Clientes'); ?></head>
<body>
<?php cabecalho('Sistema de Gestão', 'balconista', 'clientes'); ?>
<main class="container py-4">
    <section class="card card-brand rounded-4 p-4 mb-4">
        <h2 class="h3 fw-bold">CLIENTES</h2>
        <p class="mb-0">Cadastre os clientes da farmácia e consulte o histórico de compras.</p>
    </section>

    <?php if (isset($_GET['editado'])) { ?><div class="alert alert-success">Cliente atualizado com sucesso.</div><?php } ?>
    <?php if ($mensagem !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div><?php } ?>
    <?php if ($erro !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php } ?>

    <section class="card shadow-sm rounded-4 p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">CADASTRAR CLIENTE</h3>
        <form method="POST" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Nome</label>
                <input class="form-control" name="nome" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">CPF</label>
                <input class="form-control" name="cpf" maxlength="14" placeholder="Somente números ou formatado">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Telefone</label>
                <input class="form-control" name="telefone" maxlength="20">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">E-mail</label>
                <input class="form-control" type="email" name="email" maxlength="150">
            </div>
            <div class="col-12">
                <button class="btn btn-primary rounded-pill px-4" type="submit">CADASTRAR CLIENTE</button>
            </div>
        </form>
    </section>

    <section class="card shadow-sm rounded-4 p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
            <h3 class="h5 fw-bold mb-0">CLIENTES CADASTRADOS</h3>
            <form class="d-flex gap-2" method="GET">
                <input class="form-control" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome ou CPF">
                <button class="btn btn-outline-primary rounded-pill">BUSCAR</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Nome</th><th>CPF</th><th>Telefone</th><th>E-mail</th><th>Ação</th></tr></thead>
                <tbody>
                <?php if (!$clientes) { ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Nenhum cliente cadastrado.</td></tr>
                <?php } ?>
                <?php foreach ($clientes as $cliente) { ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cliente['cpf'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefone'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['email'] ?: '-'); ?></td>
                        <td class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-primary rounded-pill" href="historicocliente.php?id=<?php echo (int) $cliente['id']; ?>">HISTÓRICO</a><a class="btn btn-sm btn-outline-secondary rounded-pill" href="editarcliente.php?id=<?php echo (int) $cliente['id']; ?>">EDITAR</a></td>
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
