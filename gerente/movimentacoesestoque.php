<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$produtoId = isset($_GET['produto']) ? (int) $_GET['produto'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '30';

$tiposPermitidos = array('entrada', 'saida', 'ajuste');
$periodosPermitidos = array('hoje', '7', '30', 'todos');

if (!in_array($tipo, $tiposPermitidos)) {
    $tipo = '';
}

if (!in_array($periodo, $periodosPermitidos)) {
    $periodo = '30';
}

$sqlProdutos = 'SELECT id, nome
                FROM produtos
                WHERE farmacia_id = ?
                ORDER BY nome';

$stmtProdutos = mysqli_prepare($conexao, $sqlProdutos);
mysqli_stmt_bind_param($stmtProdutos, 'i', $farmaciaId);
mysqli_stmt_execute($stmtProdutos);
$resultadoProdutos = mysqli_stmt_get_result($stmtProdutos);

$produtos = array();
while ($produto = mysqli_fetch_assoc($resultadoProdutos)) {
    $produtos[] = $produto;
}
mysqli_stmt_close($stmtProdutos);

$sql = 'SELECT m.id,
               m.tipo,
               m.quantidade,
               m.observacao,
               m.criado_em,
               p.nome AS produto,
               u.nome AS usuario
        FROM movimentacoes_estoque m
        INNER JOIN produtos p ON p.id = m.produto_id
        INNER JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.farmacia_id = ?';

$parametros = array($farmaciaId);
$tipos = 'i';

if ($produtoId > 0) {
    $sql .= ' AND m.produto_id = ?';
    $parametros[] = $produtoId;
    $tipos .= 'i';
}

if ($tipo != '') {
    $sql .= ' AND m.tipo = ?';
    $parametros[] = $tipo;
    $tipos .= 's';
}

if ($periodo == 'hoje') {
    $sql .= ' AND DATE(m.criado_em) = CURDATE()';
} elseif ($periodo == '7') {
    $sql .= ' AND m.criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($periodo == '30') {
    $sql .= ' AND m.criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

$sql .= ' ORDER BY m.id DESC';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$movimentacoes = array();
$totalEntradas = 0;
$totalSaidas = 0;
$totalAjustes = 0;

while ($movimentacao = mysqli_fetch_assoc($resultado)) {
    $movimentacoes[] = $movimentacao;

    if ($movimentacao['tipo'] == 'entrada') {
        $totalEntradas += (int) $movimentacao['quantidade'];
    } elseif ($movimentacao['tipo'] == 'saida') {
        $totalSaidas += (int) $movimentacao['quantidade'];
    } else {
        $totalAjustes += (int) $movimentacao['quantidade'];
    }
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('FarmaCerta - Movimentação de Estoque'); ?>
</head>
<body>

    <?php cabecalho('FarmaCerta - Gerente', 'gerente', 'movimentacoes'); ?>

    <main class="container py-4">

        <div class="card card-brand rounded-4 p-4 mb-4">
            <h2 class="h3 fw-bold">MOVIMENTAÇÃO DE ESTOQUE</h2>
            <p>Entradas, saídas e ajustes registrados nesta farmácia.</p>

            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label fw-bold">Produto</label>
                    <select class="form-select" name="produto">
                        <option value="0">Todos os produtos</option>
                        <?php foreach ($produtos as $produto) { ?>
                            <option
                                value="<?php echo $produto['id']; ?>"
                                <?php echo $produtoId == $produto['id'] ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($produto['nome']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-bold">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="entrada" <?php echo $tipo == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                        <option value="saida" <?php echo $tipo == 'saida' ? 'selected' : ''; ?>>Saída</option>
                        <option value="ajuste" <?php echo $tipo == 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-bold">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="hoje" <?php echo $periodo == 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                        <option value="7" <?php echo $periodo == '7' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="30" <?php echo $periodo == '30' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                        <option value="todos" <?php echo $periodo == 'todos' ? 'selected' : ''; ?>>Todo o período</option>
                    </select>
                </div>

                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-outline-light rounded-pill fw-bold" type="submit">FILTRAR</button>
                    <a href="movimentacoesestoque.php" class="btn btn-light rounded-pill">LIMPAR</a>
                </div>
            </form>
        </div>

        <section class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card shadow-sm p-3">
                    <span class="small text-secondary fw-bold">ENTRADAS</span>
                    <strong class="fs-3 text-success">+<?php echo $totalEntradas; ?></strong>
                    <small class="text-secondary">unidades no período</small>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card shadow-sm p-3">
                    <span class="small text-secondary fw-bold">SAÍDAS</span>
                    <strong class="fs-3 text-danger">-<?php echo $totalSaidas; ?></strong>
                    <small class="text-secondary">unidades no período</small>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card shadow-sm p-3">
                    <span class="small text-secondary fw-bold">AJUSTES</span>
                    <strong class="fs-3 text-warning">+<?php echo $totalAjustes; ?></strong>
                    <small class="text-secondary">unidades registradas</small>
                </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card shadow-sm p-3">
                    <span class="small text-secondary fw-bold">REGISTROS</span>
                    <strong class="fs-3"><?php echo count($movimentacoes); ?></strong>
                    <small class="text-secondary">movimentações encontradas</small>
                </article>
            </div>
        </section>

        <section class="card shadow-sm rounded-4 p-3">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Responsável</th>
                    <th>Motivo</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($movimentacoes) == 0) { ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            Nenhuma movimentação encontrada para os filtros selecionados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($movimentacoes as $movimentacao) {
                    $classeTipo = $movimentacao['tipo'] == 'entrada' ? 'text-bg-success' : ($movimentacao['tipo'] == 'saida' ? 'text-bg-danger' : 'text-bg-warning');
                ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($movimentacao['criado_em'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($movimentacao['produto']); ?></strong></td>
                        <td>
                            <span class="badge rounded-pill <?php echo $classeTipo; ?>">
                                <?php echo strtoupper($movimentacao['tipo']); ?>
                            </span>
                        </td>
                        <td class="fw-bold">
                            <?php echo $movimentacao['tipo'] == 'entrada' ? '+' : ($movimentacao['tipo'] == 'saida' ? '-' : ''); ?><?php echo (int) $movimentacao['quantidade']; ?>
                        </td>
                        <td><?php echo htmlspecialchars($movimentacao['usuario']); ?></td>
                        <td><?php echo htmlspecialchars($movimentacao['observacao'] ?: 'Sem observação'); ?></td>
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
<?php mysqli_close($conexao); ?>
