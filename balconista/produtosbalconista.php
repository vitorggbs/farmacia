<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';

exigirLogin('balconista');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../gerente/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

$farmaciaId = (int) $_SESSION['farmacia_id'];

$sql = 'SELECT p.id,p.nome,p.preco,p.quantidade,p.imagem,c.nome categoria,
        COALESCE(SUM(l.quantidade),0) lote_total,
        COALESCE(SUM(CASE WHEN l.validade >= CURDATE() THEN l.quantidade ELSE 0 END),0) lote_valido
        FROM produtos p
        LEFT JOIN categorias c ON c.id=p.categoria_id
        LEFT JOIN lotes l ON l.produto_id=p.id
        WHERE p.farmacia_id = ? AND p.ativo = 1
        GROUP BY p.id,p.nome,p.preco,p.quantidade,p.imagem,c.nome
        ORDER BY p.nome ASC';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado) {
    die("Erro ao buscar produtos: " . mysqli_error($conexao));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('FarmaCerta - Produtos'); ?>
</head>
<body>
    <?php cabecalho('Sistema de Gestão', 'balconista', 'produtos'); ?>

    <main class="container py-4">
        <section class="card card-brand rounded-4 p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h2 class="h4 fw-bold mb-0">PRODUTOS CADASTRADOS</h2>
                <input class="form-control" style="max-width:280px;" type="text" id="busca" placeholder="Buscar produto..." onkeyup="buscarProduto()">
            </div>

            <?php if (isset($_GET['ok'])) { ?>
                <div class="alert alert-light text-success">Produto adicionado ao carrinho.</div>
            <?php } ?>
            <?php if (isset($_GET['erro'])) { ?>
                <div class="alert alert-light text-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
            <?php } ?>

            <div class="table-responsive bg-white rounded-3">
                <table class="table table-hover align-middle mb-0" id="tabela-produtos">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Estoque</th>
                            <th>Comprar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($resultado) > 0) { ?>
                        <?php while ($produto = mysqli_fetch_assoc($resultado)) { $disponivel = max(0, (int)$produto['quantidade'] - (int)$produto['lote_total']) + (int)$produto['lote_valido']; ?>
                            <tr>
                                <td>
                                    <?php if (!empty($produto['imagem'])) { ?>
                                        <img class="produto-img rounded" src="../gerente/uploads/<?php echo htmlspecialchars($produto['imagem']); ?>" width="70" height="70" alt="Produto">
                                    <?php } else { ?>
                                        <div class="foto-exemplo rounded d-inline-flex align-items-center justify-content-center"></div>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria'] ?: 'Sem categoria'); ?></td>
                                <td>R$ <?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($disponivel <= 0) { ?>
                                        <span class="text-danger fw-bold">0<br>SEM ESTOQUE</span>
                                    <?php } else { ?>
                                        <?php echo $disponivel; ?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($disponivel > 0) { ?>
                                        <form action="adicionarcarrinho.php" method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="produto_id" value="<?php echo (int) $produto['id']; ?>">
                                            <input class="form-control form-control-sm" type="number" name="quantidade" value="1" min="1" max="<?php echo $disponivel; ?>" required>
                                            <button class="btn btn-sm btn-primary rounded-pill" type="submit">ADICIONAR</button>
                                        </form>
                                    <?php } else { ?>
                                        <button class="btn btn-sm btn-secondary rounded-pill" disabled>SEM ESTOQUE</button>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6">Nenhum produto cadastrado.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php recursosRodape(); ?>
    <script>
        function buscarProduto() {
            var busca = document.getElementById("busca").value.toLowerCase();
            var linhas = document.getElementById("tabela-produtos").getElementsByTagName("tr");
            for (var i = 1; i < linhas.length; i++) {
                var colunaNome = linhas[i].getElementsByTagName("td")[1];
                if (colunaNome) {
                    linhas[i].style.display = colunaNome.textContent.toLowerCase().indexOf(busca) > -1 ? "" : "none";
                }
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($conexao); ?>
