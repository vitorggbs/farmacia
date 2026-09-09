<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';

exigirLogin('gerente');

require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

$farmaciaId = (int) $_SESSION['farmacia_id'];
$categorias = mysqli_query($conexao, 'SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome');
$listaCategorias = mysqli_fetch_all($categorias, MYSQLI_ASSOC);

$sql = '
    SELECT p.*, c.nome AS categoria
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.farmacia_id = ?
    AND p.ativo = 1
    ORDER BY p.nome
';

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, 'i', $farmaciaId);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php recursosCabeca('FarmaCerta - Produtos'); ?>
</head>
<body>
<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'produtos'); ?>

<main class="container py-4">
    <section class="card card-brand rounded-4 p-4 mb-4" id="cadastrar">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><h2 class="h3 fw-bold mb-0">CADASTRAR PRODUTO</h2><a class="btn btn-outline-light rounded-pill" href="categorias.php">GERENCIAR CATEGORIAS</a></div>
        <?php if (isset($_GET['cadastro'])) { ?>
            <div class="alert alert-light text-success fw-bold">Produto cadastrado com sucesso!</div>
        <?php } ?>
        <form action="cadastrargerente.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Nome do produto</label>
                <input class="form-control" type="text" name="nomeProduto" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Preço</label>
                <input class="form-control" type="number" name="valor" step="0.01" min="0" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Quantidade</label>
                <input class="form-control" type="number" name="quantidade" min="0" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Estoque mínimo</label>
                <input class="form-control" type="number" name="estoque_minimo" min="0" value="5" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Categoria</label>
                <select class="form-select" name="categoria_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($listaCategorias as $cat) { ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option><?php } ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Prateleira</label>
                <input class="form-control" type="text" name="prateleira" placeholder="Ex.: A3" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold">Imagem</label>
                <input class="form-control" type="file" name="imagem" accept="image/*" required>
            </div>
            <div class="col-12">
                <button class="btn btn-outline-light rounded-pill fw-bold" type="submit">CADASTRAR PRODUTO</button>
            </div>
        </form>
    </section>

    <section class="card card-brand rounded-4 p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h2 class="h4 fw-bold mb-0">PRODUTOS CADASTRADOS</h2>
            <input class="form-control" style="max-width:280px;" type="text" id="busca" placeholder="Buscar produto..." onkeyup="buscarProduto()">
        </div>
        <div class="table-responsive bg-white rounded-3">
            <table class="table table-hover align-middle mb-0" id="tabela-produtos">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th>Prateleira</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($produto = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td>
                            <?php if (!empty($produto['imagem'])) { ?>
                                <img class="produto-img rounded" src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>" width="60" height="60" alt="Produto">
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['categoria'] ?: 'Sem categoria'); ?></td>
                        <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                        <td>
                            <?php echo (int) $produto['quantidade']; ?>
                            <?php if ($produto['quantidade'] <= 0) { ?>
                                <br><span class="text-danger fw-bold">SEM ESTOQUE</span>
                            <?php } elseif ($produto['quantidade'] <= $produto['estoque_minimo']) { ?>
                                <br><span class="text-danger fw-bold">ESTOQUE BAIXO</span>
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($produto['prateleira']); ?></td>
                        <td>
                            <details class="mb-2">
                                <summary class="btn btn-sm btn-primary rounded-pill">EDITAR</summary>
                                <form action="editarproduto.php" method="POST" class="mt-2 d-grid gap-2">
                                    <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                    <input class="form-control form-control-sm" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                                    <input class="form-control form-control-sm" type="number" step="0.01" name="preco" value="<?php echo $produto['preco']; ?>" required>
                                    <input class="form-control form-control-sm" type="number" name="estoque_minimo" value="<?php echo $produto['estoque_minimo']; ?>" required>
                                    <input class="form-control form-control-sm" name="prateleira" value="<?php echo htmlspecialchars($produto['prateleira']); ?>" required>
                                    <select class="form-select form-select-sm" name="categoria_id" required>
                                        <?php foreach ($listaCategorias as $cat) { ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ((int) $produto['categoria_id'] === (int) $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nome']); ?></option>
                                        <?php } ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary rounded-pill">SALVAR</button>
                                </form>
                            </details>
                            <a class="btn btn-sm btn-outline-primary rounded-pill mb-2" href="entradasmercadoria.php">ENTRADA COM LOTE</a>
                            <form action="excluirproduto.php" method="POST" onsubmit="return confirm('Excluir produto?')">
                                <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                                <button class="btn btn-sm btn-danger rounded-pill">EXCLUIR</button>
                            </form>
                        </td>
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
    var busca = document.getElementById('busca').value.toLowerCase();
    var linhas = document.querySelectorAll('#tabela-produtos tbody tr');
    for (var i = 0; i < linhas.length; i++) {
        linhas[i].style.display = linhas[i].textContent.toLowerCase().includes(busca) ? '' : 'none';
    }
}

function confirmarReposicao() {
    if (!confirm('Confirma a reposição?')) {
        return false;
    }
    return confirm('Tem certeza da quantidade?');
}
</script>
</body>
</html>
<?php mysqli_close($conexao); ?>
