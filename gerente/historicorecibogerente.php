<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('gerente');

$periodo = 'dia';
$pagamento = '';

if (isset($_GET['periodo'])) {
    $periodo = $_GET['periodo'];
}

if (isset($_GET['pagamento'])) {
    $pagamento = $_GET['pagamento'];
}

$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = "SELECT v.*, u.nome AS usuario
        FROM vendas v
        INNER JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.farmacia_id = $farmaciaId";

if ($periodo == 'dia') {
    $sql = $sql . " AND DATE(v.data_venda) = CURDATE()";
}

$formas = array('dinheiro', 'pix', 'debito', 'credito');

if (in_array($pagamento, $formas)) {
    $pagamentoSeguro = mysqli_real_escape_string($conexao, $pagamento);
    $sql = $sql . " AND v.forma_pagamento = '$pagamentoSeguro'";
}

$sql = $sql . ' ORDER BY v.id DESC';
$vendas = mysqli_query($conexao, $sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Recibos</title>
        <link rel="stylesheet" href="../style.css">
    </head>
    <body>
        <?php cabecalho('FarmaCerta - Gerente','gerente','recibos'); ?>
        <main class="container">
            <div class="card">
                <h2>RECIBOS</h2>
                <form method="GET">
                    <label>Período</label>
                    <select name="periodo">
                        <option value="dia">Hoje</option>
                        <option value="todos" <?=$periodo==='todos'?'selected':''?>>Todos</option>
                    </select>
                    <label>Pagamento</label>
                    <select name="pagamento">
                        <option value="">Todos</option>
                        <?php
                        foreach (array('dinheiro', 'pix', 'debito', 'credito') as $forma) {
                        $selecionado = '';

                        if ($pagamento === $forma) {
                        $selecionado = 'selected';
                        }
                        ?>
                        <option
                            value="<?php echo $forma; ?>"
                            <?php echo $selecionado; ?>
                            >
                            <?php echo ucfirst($forma); ?>
                        </option>
                    <?php } ?>
                </select>
                <button>FILTRAR</button>
            </form>
        </div>
        <section class="secao-branca">
            <table class="tabela">
                <tr>
                    <th>Número</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Balconista</th>
                    <th>Pagamento</th>
                    <th>Valor</th>
                    <th>Ação</th>
                </tr>
                <?php while($v=mysqli_fetch_assoc($vendas)){ ?>
                    <tr>
                        <td>#<?=$v['id']?>
                        </td>
                        <td>
                            <?=date('d/m/Y H:i',strtotime($v['data_venda']))?>
                        </td>
                        <td>
                            <?=htmlspecialchars($v['cliente']?:'Não informado')?>
                        </td>
                        <td>
                            <?=htmlspecialchars($v['usuario'])?>
                        </td>
                        <td>
                            <?=$v['forma_pagamento']?>
                        </td>
                        <td>R$ <?=number_format($v['valor_total'],2,',','.')?>
                        </td>
                        <td>
                            <a class="acao" href="../balconista/recibobalconista.php?id=<?=$v['id']?>">ABRIR</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </main>
</body>
</html>
