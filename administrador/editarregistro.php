<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../includes/auditoria.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

function identificadorSeguro(string $nome): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]+$/', $nome);
}

function buscarEstrutura(mysqli $conexao, string $banco, string $tabela): array
{
    $stmt = mysqli_prepare(
        $conexao,
        'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $banco, $tabela);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $colunas = array();

    while ($linha = mysqli_fetch_assoc($resultado)) {
        $colunas[] = $linha;
    }

    mysqli_stmt_close($stmt);
    return $colunas;
}

$tabela = $_GET['tabela'] ?? $_POST['tabela'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? '';
$erro = '';

if (!identificadorSeguro($tabela)) {
    http_response_code(400);
    exit('Tabela inválida.');
}

$estrutura = buscarEstrutura($conexao, $nomeBanco, $tabela);
if (!$estrutura) {
    http_response_code(404);
    exit('Tabela não encontrada.');
}

$chavePrimaria = null;
foreach ($estrutura as $coluna) {
    if ($coluna['COLUMN_KEY'] === 'PRI') {
        $chavePrimaria = $coluna['COLUMN_NAME'];
        break;
    }
}

if (!$chavePrimaria || !identificadorSeguro($chavePrimaria)) {
    http_response_code(400);
    exit('Esta tabela não possui chave primária editável.');
}

$sqlRegistro = 'SELECT * FROM `' . $tabela . '` WHERE `' . $chavePrimaria . '` = ? LIMIT 1';
$stmtRegistro = mysqli_prepare($conexao, $sqlRegistro);
mysqli_stmt_bind_param($stmtRegistro, 's', $id);
mysqli_stmt_execute($stmtRegistro);
$resultadoRegistro = mysqli_stmt_get_result($stmtRegistro);
$registro = mysqli_fetch_assoc($resultadoRegistro);
mysqli_stmt_close($stmtRegistro);

if (!$registro) {
    http_response_code(404);
    exit('Registro não encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sets = array();
    $valores = array();

    foreach ($estrutura as $coluna) {
        $nome = $coluna['COLUMN_NAME'];

        if ($nome === $chavePrimaria || stripos($coluna['EXTRA'], 'auto_increment') !== false || !identificadorSeguro($nome)) {
            continue;
        }

        // Senhas ficam protegidas: em branco = não alterar.
        if (strtolower($nome) === 'senha' && trim((string) ($_POST[$nome] ?? '')) === '') {
            continue;
        }

        if ($coluna['IS_NULLABLE'] === 'YES' && isset($_POST['null_' . $nome])) {
            $sets[] = '`' . $nome . '` = NULL';
            continue;
        }

        $sets[] = '`' . $nome . '` = ?';
        $valor = (string) ($_POST[$nome] ?? '');
        if (strtolower($nome) === 'senha') {
            $valor = password_hash($valor, PASSWORD_BCRYPT);
        }
        $valores[] = $valor;
    }

    if ($sets) {
        $sqlUpdate = 'UPDATE `' . $tabela . '` SET ' . implode(', ', $sets) . ' WHERE `' . $chavePrimaria . '` = ? LIMIT 1';
        $stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);

        $valores[] = (string) $id;
        $tipos = str_repeat('s', count($valores));
        $params = array($stmtUpdate, $tipos);
        foreach ($valores as $i => $valor) {
            $params[] = &$valores[$i];
        }
        call_user_func_array('mysqli_stmt_bind_param', $params);

        if (mysqli_stmt_execute($stmtUpdate)) {
            mysqli_stmt_close($stmtUpdate);
            registrarAuditoria(
                $conexao,
                'editar',
                'banco_sql:' . $tabela,
                is_numeric($id) ? (int) $id : null,
                'Registro alterado pelo administrador no painel Banco SQL.'
            );
            header('Location: banco.php?tabela=' . urlencode($tabela) . '&salvo=1');
            exit;
        }

        $erro = 'Não foi possível atualizar o registro: ' . mysqli_error($conexao);
        mysqli_stmt_close($stmtUpdate);
    }
}

function valorInput(array $registro, string $nome): string
{
    return htmlspecialchars((string) ($registro[$nome] ?? ''), ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Editar registro - Banco SQL'); ?>
</head>
<body>

<?php cabecalhoAdmin('FarmaCerta - Editar registro'); ?>

<main class="container py-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">EDITAR REGISTRO</h1>
            <p class="text-secondary mb-0">Tabela <strong><?php echo htmlspecialchars($tabela, ENT_QUOTES, 'UTF-8'); ?></strong> · <?php echo htmlspecialchars($chavePrimaria, ENT_QUOTES, 'UTF-8'); ?> = <?php echo htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a class="btn btn-outline-secondary rounded-pill" href="banco.php?tabela=<?php echo urlencode($tabela); ?>">Voltar</a>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="card rounded-4 p-4">
        <input type="hidden" name="tabela" value="<?php echo htmlspecialchars($tabela, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="row g-3">
            <?php foreach ($estrutura as $coluna): ?>
                <?php
                    $nome = $coluna['COLUMN_NAME'];
                    $tipo = strtolower($coluna['DATA_TYPE']);
                    $ehPk = $nome === $chavePrimaria;
                    $ehSenha = strtolower($nome) === 'senha';
                    $nullable = $coluna['IS_NULLABLE'] === 'YES';
                    $valorAtual = $registro[$nome] ?? null;
                ?>
                <div class="col-12 <?php echo in_array($tipo, array('text', 'longtext', 'mediumtext'), true) ? '' : 'col-md-6'; ?>">
                    <label class="form-label fw-semibold" for="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>
                        <span class="text-secondary fw-normal small">(<?php echo htmlspecialchars($coluna['COLUMN_TYPE'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                    </label>

                    <?php if ($ehPk): ?>
                        <input class="form-control" value="<?php echo valorInput($registro, $nome); ?>" disabled>
                    <?php elseif ($ehSenha): ?>
                        <input type="password" class="form-control" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Deixe em branco para manter a senha atual" autocomplete="new-password">
                    <?php elseif ($tipo === 'text' || str_contains($tipo, 'text')): ?>
                        <textarea class="form-control" rows="3" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>"><?php echo valorInput($registro, $nome); ?></textarea>
                    <?php elseif ($tipo === 'date'): ?>
                        <input type="date" class="form-control" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo valorInput($registro, $nome); ?>">
                    <?php elseif (in_array($tipo, array('datetime', 'timestamp'), true)): ?>
                        <?php $valorData = $valorAtual ? str_replace(' ', 'T', substr((string) $valorAtual, 0, 16)) : ''; ?>
                        <input type="datetime-local" class="form-control" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($valorData, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php elseif (preg_match("/^enum\\((.*)\\)$/", $coluna['COLUMN_TYPE'], $m)): ?>
                        <?php $opcoes = str_getcsv($m[1], ',', "'"); ?>
                        <select class="form-select" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($opcoes as $opcao): ?>
                                <option value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $valorAtual === (string) $opcao ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif (in_array($tipo, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'), true)): ?>
                        <input type="number" step="any" class="form-control" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo valorInput($registro, $nome); ?>">
                    <?php else: ?>
                        <input type="text" class="form-control" id="campo_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo valorInput($registro, $nome); ?>">
                    <?php endif; ?>

                    <?php if (!$ehPk && !$ehSenha && $nullable): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="null_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" id="null_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valorAtual === null ? 'checked' : ''; ?>>
                            <label class="form-check-label small text-secondary" for="null_<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>">Salvar como NULL</label>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a class="btn btn-outline-secondary rounded-pill px-4" href="banco.php?tabela=<?php echo urlencode($tabela); ?>">Cancelar</a>
            <button class="btn btn-danger rounded-pill px-4" type="submit">Salvar alterações</button>
        </div>
    </form>
</main>

<?php recursosRodape(); ?>
</body>
</html>
