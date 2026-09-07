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
    <?php recursosCabeca('Administrador'); ?>
</head>
<body>
    <?php cabecalhoAdmin('FarmaCerta - Administrador'); ?>

    <main class="container py-4">
        <section class="card card-brand rounded-4 p-4 mb-4">
            <h2 class="fw-bold h3 mb-4">RESUMO DO SISTEMA</h2>
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="card h-100 text-center p-3">
                        <h3 class="h4 text-primary mb-1"><?php echo (int) $dados['total']; ?></h3>
                        <p class="text-secondary mb-0">Farmácias</p>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="card h-100 text-center p-3">
                        <h3 class="h4 text-primary mb-1"><?php echo (int) $dados['ativas']; ?></h3>
                        <p class="text-secondary mb-0">Farmácias ativas</p>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="card h-100 text-center p-3">
                        <h3 class="h4 text-primary mb-1"><?php echo (int) $totalUsuarios; ?></h3>
                        <p class="text-secondary mb-0">Usuários ativos</p>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="card h-100 text-center p-3">
                        <h3 class="h5 text-primary mb-1">R$ <?php echo number_format($totalVendas, 2, ',', '.'); ?></h3>
                        <p class="text-secondary mb-0">Vendas registradas</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="card shadow-sm rounded-4 p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                <h2 class="h4 fw-bold mb-0">ÚLTIMAS FARMÁCIAS</h2>
                <a class="btn btn-primary rounded-pill" href="farmacias.php#cadastrar">+ NOVA FARMÁCIA</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Farmácia</th>
                            <th>CNPJ</th>
                            <th>Status</th>
                            <th>Cadastrada em</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($farmacia = mysqli_fetch_assoc($ultimas)) { ?>
                        <tr>
                            <td><?php echo $farmacia['id']; ?></td>
                            <td><?php echo htmlspecialchars($farmacia['nome']); ?></td>
                            <td><?php echo htmlspecialchars($farmacia['cnpj']); ?></td>
                            <td>
                                <span class="badge rounded-pill <?php echo $farmacia['ativo'] ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                    <?php echo $farmacia['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($farmacia['criado_em'])); ?></td>
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
