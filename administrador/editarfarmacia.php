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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar farmácia</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php cabecalhoAdmin('FarmaCerta - Editar Farmácia'); ?>

    <main class="container">
        <div class="card">
            <h2>EDITAR FARMÁCIA</h2>

            <form method="POST" class="form-grid">
                <input type="hidden" name="id" value="<?php echo $farmacia['id']; ?>">

                <div>
                    <label>NOME *</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($farmacia['nome']); ?>" required>
                </div>

                <div>
                    <label>CNPJ</label>
                    <input type="text" name="cnpj" value="<?php echo htmlspecialchars($farmacia['cnpj']); ?>">
                </div>

                <div>
                    <label>TELEFONE</label>
                    <input type="text" name="telefone" value="<?php echo htmlspecialchars($farmacia['telefone']); ?>">
                </div>

                <div>
                    <label>STATUS</label>
                    <input type="text" value="<?php echo $farmacia['ativo'] ? 'Ativa' : 'Inativa'; ?>" disabled>
                </div>

                <div class="campo-grande">
                    <label>ENDEREÇO</label>
                    <input type="text" name="endereco" value="<?php echo htmlspecialchars($farmacia['endereco']); ?>">
                </div>

                <div class="campo-grande">
                    <button type="submit">SALVAR ALTERAÇÕES</button>
                    <a class="botao" href="farmacias.php">VOLTAR</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
