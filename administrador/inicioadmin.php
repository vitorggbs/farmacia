<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/cabecalhoadmin.php';

exigirAdministrador();

$resumo = mysqli_query($conexao, "SELECT
    COUNT(*) AS total,
    SUM(ativo = 1) AS ativas,
    SUM(ativo = 0) AS inativas
    FROM farmacias");

$dados = mysqli_fetch_assoc($resumo);

$usuarios = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM usuarios WHERE ativo = 1");
$totalUsuarios = mysqli_fetch_assoc($usuarios)['total'];

$vendas = mysqli_query($conexao, "SELECT COALESCE(SUM(valor_total), 0) AS total FROM vendas");
$totalVendas = mysqli_fetch_assoc($vendas)['total'];

$ultimas = mysqli_query($conexao, "SELECT id, nome, cnpj, ativo, criado_em
    FROM farmacias
    ORDER BY id DESC
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Administrador</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php cabecalhoAdmin('FarmaCerta - Administrador'); ?>

    <main class="container">
        <div class="card">
            <h2>RESUMO DO SISTEMA</h2>

            <div class="painel-inicio">
                <div class="bloco">
                    <h3><?php echo (int) $dados['total']; ?></h3>
                    <p>Farmácias</p>
                </div>
                <div class="bloco">
                    <h3><?php echo (int) $dados['ativas']; ?></h3>
                    <p>Farmácias ativas</p>
                </div>
                <div class="bloco">
                    <h3><?php echo (int) $totalUsuarios; ?></h3>
                    <p>Usuários ativos</p>
                </div>
                <div class="bloco">
                    <h3>R$ <?php echo number_format($totalVendas, 2, ',', '.'); ?></h3>
                    <p>Vendas registradas</p>
                </div>
            </div>
        </div>

        <section class="secao-branca">
            <div class="titulo-com-acao">
                <h2>ÚLTIMAS FARMÁCIAS</h2>
                <a class="botao" href="farmacias.php#cadastrar">+ NOVA FARMÁCIA</a>
            </div>

            <div class="tabela-wrapper">
                <table class="tabela">
                    <tr>
                        <th>ID</th>
                        <th>Farmácia</th>
                        <th>CNPJ</th>
                        <th>Status</th>
                        <th>Cadastrada em</th>
                    </tr>

                    <?php while ($farmacia = mysqli_fetch_assoc($ultimas)) { ?>
                        <tr>
                            <td><?php echo $farmacia['id']; ?></td>
                            <td><?php echo htmlspecialchars($farmacia['nome']); ?></td>
                            <td><?php echo htmlspecialchars($farmacia['cnpj']); ?></td>
                            <td><?php echo $farmacia['ativo'] ? 'Ativa' : 'Inativa'; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($farmacia['criado_em'])); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
