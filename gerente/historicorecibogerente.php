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
        <?php recursosCabeca('Recibos'); ?>
    </head>
    <body>
        <?php cabecalho('FarmaCerta - Gerente','gerente','recibos'); ?>
        <main class="container py-4">
            <div class="card card-brand rounded-4 p-4 mb-4">
                <h2 class="h3 fw-bold">RECIBOS</h2>
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                    <label class="form-label fw-bold">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="dia">Hoje</option>
                        <option value="todos" <?=$periodo==='todos'?'selected':''?>>Todos</option>
                    </select>
                    </div>
                    <div class="col-12 col-md-4">
                    <label class="form-label fw-bold">Pagamento</label>
                    <select class="form-select" name="pagamento">
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
                    </div>
                    <div class="col-12 col-md-4">
                <button class="btn btn-outline-light rounded-pill fw-bold">FILTRAR</button>
                    </div>
            </form>
        </div>
        <section class="card shadow-sm rounded-4 p-3">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Balconista</th>
                    <th>Pagamento</th>
                    <th>Valor</th>
                    <th>Ação</th>
                </tr>
                </thead>
                <tbody>
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
                            <a class="btn btn-sm btn-primary rounded-pill" href="../balconista/recibobalconista.php?id=<?=$v['id']?>">ABRIR</a>
                        </td>
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
