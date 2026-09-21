<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

function nomeIdentificadorSeguro(string $nome): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]+$/', $nome);
}

function listarTabelas(mysqli $conexao, string $banco): array
{
    $tabelas = array();
    $stmt = mysqli_prepare(
        $conexao,
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
    );
    mysqli_stmt_bind_param($stmt, 's', $banco);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($resultado)) {
        $tabelas[] = $linha['TABLE_NAME'];
    }

    mysqli_stmt_close($stmt);
    return $tabelas;
}

function listarColunas(mysqli $conexao, string $banco, string $tabela): array
{
    $colunas = array();
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

    while ($linha = mysqli_fetch_assoc($resultado)) {
        $colunas[] = $linha;
    }

    mysqli_stmt_close($stmt);
    return $colunas;
}

$tabelas = listarTabelas($conexao, $nomeBanco);
$tabelaSelecionada = $_GET['tabela'] ?? ($tabelas[0] ?? '');

if (!in_array($tabelaSelecionada, $tabelas, true) || !nomeIdentificadorSeguro($tabelaSelecionada)) {
    $tabelaSelecionada = $tabelas[0] ?? '';
}

$colunas = array();
$totalRegistros = 0;
$registros = array();
$chavePrimaria = null;

$porPagina = 50;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

if ($tabelaSelecionada !== '') {
    $colunas = listarColunas($conexao, $nomeBanco, $tabelaSelecionada);

    foreach ($colunas as $coluna) {
        if ($coluna['COLUMN_KEY'] === 'PRI') {
            $chavePrimaria = $coluna['COLUMN_NAME'];
            break;
        }
    }

    $sqlTotal = 'SELECT COUNT(*) AS total FROM `' . $tabelaSelecionada . '`';
    $resultadoTotal = mysqli_query($conexao, $sqlTotal);
    if ($resultadoTotal) {
        $totalRegistros = (int) (mysqli_fetch_assoc($resultadoTotal)['total'] ?? 0);
    }

    $ordem = $chavePrimaria ? ' ORDER BY `' . $chavePrimaria . '` DESC' : '';
    $sql = 'SELECT * FROM `' . $tabelaSelecionada . '`' . $ordem . ' LIMIT ' . (int) $porPagina . ' OFFSET ' . (int) $offset;
    $resultado = mysqli_query($conexao, $sql);

    if ($resultado) {
        while ($linha = mysqli_fetch_assoc($resultado)) {
            $registros[] = $linha;
        }
    }
}

$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('Banco SQL - Administrador'); ?>
    <style>
        .tabela-banco { min-width: 1100px; }
        .tabela-banco th { white-space: nowrap; }
        .tabela-banco td { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .nome-tabela { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .badge-coluna { font-size: .72rem; font-weight: 600; }
    </style>
</head>
<body>

<?php cabecalhoAdmin('FarmaCerta - Banco SQL'); ?>

<main class="container-fluid px-3 px-lg-4 py-4">
    <section class="card card-brand rounded-4 p-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h1 class="h3 fw-bold mb-1">BANCO DE DADOS</h1>
                <p class="text-secondary mb-0">Visualize todas as colunas das tabelas e edite os registros pelo painel do administrador.</p>
            </div>
            <div class="text-lg-end">
                <span class="badge text-bg-light border px-3 py-2">Cloud SQL: <?php echo htmlspecialchars($nomeBanco, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </section>

    <section class="card rounded-4 p-3 p-lg-4 mb-4">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-12 col-md-5 col-lg-4">
                <label for="tabela" class="form-label fw-semibold">Tabela</label>
                <select class="form-select" id="tabela" name="tabela" onchange="this.form.submit()">
                    <?php foreach ($tabelas as $tabela): ?>
                        <option value="<?php echo htmlspecialchars($tabela, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tabela === $tabelaSelecionada ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tabela, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <div class="small text-secondary">Registros</div>
                <div class="fw-bold fs-5"><?php echo number_format($totalRegistros, 0, ',', '.'); ?></div>
            </div>
            <div class="col-12 col-md-auto">
                <div class="small text-secondary">Colunas</div>
                <div class="fw-bold fs-5"><?php echo count($colunas); ?></div>
            </div>
        </form>
    </section>

    <?php if (isset($_GET['salvo'])): ?>
        <div class="alert alert-success rounded-4">Registro atualizado com sucesso.</div>
    <?php endif; ?>

    <section class="card rounded-4 overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong class="nome-tabela"><?php echo htmlspecialchars($tabelaSelecionada, ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="small text-secondary">Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabela-banco">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($colunas as $coluna): ?>
                            <th>
                                <?php echo htmlspecialchars($coluna['COLUMN_NAME'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($coluna['COLUMN_KEY'] === 'PRI'): ?>
                                    <span class="badge text-bg-danger badge-coluna">PK</span>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if ($chavePrimaria): ?><th class="text-end">Ação</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$registros): ?>
                        <tr><td colspan="<?php echo count($colunas) + 1; ?>" class="text-center py-5 text-secondary">Nenhum registro nesta tabela.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $registro): ?>
                            <tr>
                                <?php foreach ($colunas as $coluna): ?>
                                    <?php
                                        $nomeColuna = $coluna['COLUMN_NAME'];
                                        $valor = $registro[$nomeColuna];
                                        $texto = $valor === null ? 'NULL' : (string) $valor;
                                        $ehSenha = strtolower($nomeColuna) === 'senha';
                                    ?>
                                    <td title="<?php echo htmlspecialchars($ehSenha ? 'Campo protegido' : $texto, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($valor === null): ?>
                                            <span class="text-secondary fst-italic">NULL</span>
                                        <?php elseif ($ehSenha): ?>
                                            <span class="text-secondary">••••••••</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($chavePrimaria): ?>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="editarregistro.php?tabela=<?php echo urlencode($tabelaSelecionada); ?>&id=<?php echo urlencode((string) $registro[$chavePrimaria]); ?>">Editar</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <div class="p-3 border-top d-flex justify-content-between">
                <?php if ($pagina > 1): ?>
                    <a class="btn btn-outline-secondary rounded-pill" href="?tabela=<?php echo urlencode($tabelaSelecionada); ?>&pagina=<?php echo $pagina - 1; ?>">← Anterior</a>
                <?php else: ?><span></span><?php endif; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a class="btn btn-outline-secondary rounded-pill" href="?tabela=<?php echo urlencode($tabelaSelecionada); ?>&pagina=<?php echo $pagina + 1; ?>">Próxima →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php recursosRodape(); ?>
</body>
</html>
