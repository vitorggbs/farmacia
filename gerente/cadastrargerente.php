<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/conexaoDB.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Erro: o formulário não foi enviado corretamente.');
}

$nomeproduto = isset($_POST['nomeProduto']) ? trim($_POST['nomeProduto']) : '';
$valor       = isset($_POST['valor']) ? str_replace(',', '.', trim($_POST['valor'])) : '';
$quantidade  = isset($_POST['quantidade']) ? (int) $_POST['quantidade'] : 0;
$prateleira  = isset($_POST['prateleira']) ? trim($_POST['prateleira']) : '';

if ($nomeproduto === '') {
    die('Erro: informe o nome do produto.');
}

if ($valor === '' || !is_numeric($valor)) {
    die('Erro: informe um valor válido.');
}

if ($quantidade < 0) {
    die('Erro: informe uma quantidade válida.');
}

if ($prateleira === '') {
    die('Erro: informe a prateleira.');
}

if (!isset($_FILES['imagem'])) {
    die('Erro: nenhuma imagem foi enviada.');
}

if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    die('Erro no envio da imagem. Código: ' . $_FILES['imagem']['error']);
}

$arquivoTemporario = $_FILES['imagem']['tmp_name'];
$tamanho = $_FILES['imagem']['size'];

if ($tamanho > 5 * 1024 * 1024) {
    die('Erro: a imagem deve ter no máximo 5 MB.');
}

$informacoesImagem = getimagesize($arquivoTemporario);
if ($informacoesImagem === false) {
    die('Erro: o arquivo enviado não é uma imagem válida.');
}

$tiposPermitidos = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_GIF  => 'gif',
    IMAGETYPE_WEBP => 'webp'
];

$tipoImagem = $informacoesImagem[2];
if (!isset($tiposPermitidos[$tipoImagem])) {
    die('Erro: formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP.');
}

$extensao = $tiposPermitidos[$tipoImagem];
$pastaUploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

if (!is_dir($pastaUploads) && !mkdir($pastaUploads, 0777, true)) {
    die('Erro: não foi possível criar a pasta uploads.');
}

$nomeImagem = uniqid('produto_', true) . '.' . $extensao;
$caminhoImagem = $pastaUploads . DIRECTORY_SEPARATOR . $nomeImagem;

if (!move_uploaded_file($arquivoTemporario, $caminhoImagem)) {
    die('Erro: não foi possível salvar a imagem na pasta uploads.');
}

/*
 * A partir daqui o arquivo consulta a ESTRUTURA REAL da tabela produtos.
 * Assim ele não depende do bancodasfarmacia.sql antigo.
 */
$resultadoColunas = mysqli_query($conexao, "SHOW COLUMNS FROM produtos");

if (!$resultadoColunas) {
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }
    die('Erro: não foi possível ler a tabela produtos: ' . mysqli_error($conexao));
}

$colunasTabela = [];
while ($linha = mysqli_fetch_assoc($resultadoColunas)) {
    $colunasTabela[$linha['Field']] = $linha;
}

function primeiraColunaExistente($colunasTabela, $opcoes) {
    foreach ($opcoes as $opcao) {
        if (isset($colunasTabela[$opcao])) {
            return $opcao;
        }
    }
    return null;
}

$colunaNome       = primeiraColunaExistente($colunasTabela, ['nome', 'nomeProduto', 'nome_produto', 'produto']);
$colunaValor      = primeiraColunaExistente($colunasTabela, ['valor', 'preco', 'preço']);
$colunaQuantidade = primeiraColunaExistente($colunasTabela, ['quantidade', 'estoque', 'qtd']);
$colunaPrateleira = primeiraColunaExistente($colunasTabela, ['prateleira', 'localizacao', 'localização']);
$colunaImagem     = primeiraColunaExistente($colunasTabela, ['imagem', 'foto', 'image']);

if ($colunaNome === null || $colunaValor === null || $colunaQuantidade === null) {
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }

    $nomes = implode(', ', array_keys($colunasTabela));
    die(
        'Erro: a tabela produtos não possui as colunas mínimas esperadas. ' .
        'Colunas encontradas: ' . htmlspecialchars($nomes)
    );
}

$campos = [];
$valoresSql = [];
$tipos = '';
$valores = [];

$campos[] = "`$colunaNome`";
$valoresSql[] = '?';
$tipos .= 's';
$valores[] = $nomeproduto;

$campos[] = "`$colunaValor`";
$valoresSql[] = '?';
$tipos .= 'd';
$valores[] = (float) $valor;

$campos[] = "`$colunaQuantidade`";
$valoresSql[] = '?';
$tipos .= 'i';
$valores[] = $quantidade;

if ($colunaPrateleira !== null) {
    $campos[] = "`$colunaPrateleira`";
    $valoresSql[] = '?';
    $tipos .= 's';
    $valores[] = $prateleira;
}

if ($colunaImagem !== null) {
    $campos[] = "`$colunaImagem`";
    $valoresSql[] = '?';
    $tipos .= 's';
    $valores[] = $nomeImagem;
}

/* Preenche campos comuns obrigatórios se existirem na sua tabela */
if (isset($colunasTabela['descricao'])) {
    $campos[] = '`descricao`';
    $valoresSql[] = '?';
    $tipos .= 's';
    $valores[] = '';
}

if (isset($colunasTabela['estoque_minimo'])) {
    $campos[] = '`estoque_minimo`';
    $valoresSql[] = '?';
    $tipos .= 'i';
    $valores[] = 0;
}

if (isset($colunasTabela['criado_em'])) {
    $campos[] = '`criado_em`';
    $valoresSql[] = 'NOW()';
}

$sql = 'INSERT INTO produtos (' . implode(', ', $campos) . ') VALUES (' . implode(', ', $valoresSql) . ')';
$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }
    die('Erro ao preparar o cadastro: ' . mysqli_error($conexao));
}

if (!empty($valores)) {
    $parametros = [$tipos];
    foreach ($valores as $chave => $valorParametro) {
        $parametros[] = &$valores[$chave];
    }
    call_user_func_array([$stmt, 'bind_param'], $parametros);
}

try {
    $executou = mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }
    die('Erro ao cadastrar produto: ' . htmlspecialchars($e->getMessage()));
}

if (!$executou) {
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }
    die('Erro ao cadastrar produto: ' . htmlspecialchars(mysqli_stmt_error($stmt)));
}

$valorFormatado = number_format((float) $valor, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produto cadastrado</title>
</head>
<body>
    <h1>Produto cadastrado com sucesso!</h1>
    <p>Produto: <strong><?= htmlspecialchars($nomeproduto) ?></strong></p>
    <p>Valor: R$ <?= $valorFormatado ?></p>
    <p>Quantidade: <?= htmlspecialchars((string) $quantidade) ?></p>
    <p>Prateleira: <?= htmlspecialchars($prateleira) ?></p>

    <?php if ($colunaImagem !== null): ?>
        <p>Imagem:</p>
        <img src="uploads/<?= htmlspecialchars($nomeImagem) ?>" width="200" alt="Imagem do produto">
    <?php endif; ?>

    <br><br>
    <a href="cadastrargerente.html">Cadastrar outro produto</a>
    <br><br>
    <a href="iniciogerente.html">Voltar para o início</a>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexao);
?>
