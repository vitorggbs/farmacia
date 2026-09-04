<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

exigirLogin('balconista');

$usuarioId = (int) $_SESSION['usuario_id'];
$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = 'SELECT * FROM vendas
        WHERE usuario_id = ? AND farmacia_id = ?
        ORDER BY id DESC';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $usuarioId, $farmaciaId);
mysqli_stmt_execute($stmt);
$vendas = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Histórico</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        /* Faz a área da logo funcionar como botão */
        .logo-link {
            display: inline-block;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <?php cabecalho('FarmaCerta - Balconista', 'balconista', 'historico'); ?>

    <!--
        Link da logo para a página inicial do balconista.
        Se a logo estiver dentro do cabeçalho, o código abaixo
        pode ser usado caso queira adicionar uma segunda logo/link.
    -->

    <main class="container">

        <section class="secao-branca">

            <h2>MEUS RECIBOS</h2>

            <table class="tabela">

                <tr>
                    <th>Numero</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th>Valor</th>
                    <th>Acao</th>
                </tr>

                <?php while ($v = mysqli_fetch_assoc($vendas)) { ?>

                    <tr>

                        <td>
                            #<?php echo $v['id']; ?>
                        </td>

                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($v['data_venda'])); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($v['cliente'] ?: 'Nao informado'); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($v['forma_pagamento']); ?>
                        </td>

                        <td>
                            R$ <?php echo number_format($v['valor_total'], 2, ',', '.'); ?>
                        </td>

                        <td>
                            <a
                                class="acao"
                                href="recibobalconista.php?id=<?php echo $v['id']; ?>"
                            >
                                ABRIR
                            </a>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        </section>

    </main>

</body>
</html>
