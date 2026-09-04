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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaCerta - Movimentação de Estoque</title>
    <link rel="stylesheet" href="../style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
    <link rel="icon" type="image/png" href="../assets/LOGO_2.png">
</head>
<body>

    <?php cabecalho('FarmaCerta - Gerente', 'gerente', 'movimentacoes'); ?>

    <main class="container movimentacoes-pagina">

        <div class="card movimentacoes-filtro">
            <div class="cabecalho-card">
                <div>
                    <h2>MOVIMENTAÇÃO DE ESTOQUE</h2>
                    <p class="texto-apoio">Entradas, saídas e ajustes registrados nesta farmácia.</p>
                </div>
            </div>

            <form method="GET" class="form-filtros-movimentacao">
                <div>
                    <label>Produto</label>
                    <select name="produto">
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

                <div>
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="entrada" <?php echo $tipo == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                        <option value="saida" <?php echo $tipo == 'saida' ? 'selected' : ''; ?>>Saída</option>
                        <option value="ajuste" <?php echo $tipo == 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                    </select>
                </div>

                <div>
                    <label>Período</label>
                    <select name="periodo">
                        <option value="hoje" <?php echo $periodo == 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                        <option value="7" <?php echo $periodo == '7' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="30" <?php echo $periodo == '30' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                        <option value="todos" <?php echo $periodo == 'todos' ? 'selected' : ''; ?>>Todo o período</option>
                    </select>
                </div>

                <div class="filtro-botoes">
                    <button type="submit">FILTRAR</button>
                    <a href="movimentacoesestoque.php" class="botao-secundario">LIMPAR</a>
                </div>
            </form>
        </div>

        <section class="resumo-movimentacoes">
            <div class="resumo-movimento entrada">
                <span>ENTRADAS</span>
                <strong>+<?php echo $totalEntradas; ?></strong>
                <small>unidades no período</small>
            </div>

            <div class="resumo-movimento saida">
                <span>SAÍDAS</span>
                <strong>-<?php echo $totalSaidas; ?></strong>
                <small>unidades no período</small>
            </div>

            <div class="resumo-movimento ajuste">
                <span>AJUSTES</span>
                <strong><?php echo $totalAjustes; ?></strong>
                <small>unidades registradas</small>
            </div>

            <div class="resumo-movimento total">
                <span>REGISTROS</span>
                <strong><?php echo count($movimentacoes); ?></strong>
                <small>movimentações encontradas</small>
            </div>
        </section>

        <section class="secao-branca movimentacoes-tabela-wrap">
            <table class="tabela tabela-movimentacoes">
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Responsável</th>
                    <th>Motivo</th>
                </tr>

                <?php if (count($movimentacoes) == 0) { ?>
                    <tr>
                        <td colspan="6" class="movimentacoes-vazio">
                            Nenhuma movimentação encontrada para os filtros selecionados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($movimentacoes as $movimentacao) { ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($movimentacao['criado_em'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($movimentacao['produto']); ?></strong></td>
                        <td>
                            <span class="tipo-movimento tipo-<?php echo $movimentacao['tipo']; ?>">
                                <?php echo strtoupper($movimentacao['tipo']); ?>
                            </span>
                        </td>
                        <td class="quantidade-movimento">
                            <?php echo $movimentacao['tipo'] == 'entrada' ? '+' : ($movimentacao['tipo'] == 'saida' ? '-' : ''); ?><?php echo (int) $movimentacao['quantidade']; ?>
                        </td>
                        <td><?php echo htmlspecialchars($movimentacao['usuario']); ?></td>
                        <td><?php echo htmlspecialchars($movimentacao['observacao'] ?: 'Sem observação'); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </section>

    </main>

</body>
</html>
<?php mysqli_close($conexao); ?>
