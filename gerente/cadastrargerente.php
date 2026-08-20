<?php

// Mostrar erros durante o desenvolvimento
error_reporting(E_ALL);
ini_set("display_errors", 1);


// ======================================================
// CONEXÃO COM O BANCO
// ======================================================

require_once __DIR__ . "/conexaoDB.php";


// ======================================================
// VERIFICAR SE O FORMULÁRIO FOI ENVIADO
// ======================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Erro: o formulário não foi enviado corretamente.");
}


// ======================================================
// RECEBER OS DADOS DO FORMULÁRIO
// ======================================================

$nomeproduto = isset($_POST["nomeProduto"])
    ? trim($_POST["nomeProduto"])
    : "";

$valor = isset($_POST["valor"])
    ? $_POST["valor"]
    : "";

$quantidade = isset($_POST["quantidade"])
    ? $_POST["quantidade"]
    : "";

$prateleira = isset($_POST["prateleira"])
    ? trim($_POST["prateleira"])
    : "";


// ======================================================
// VERIFICAR OS CAMPOS
// ======================================================

if ($nomeproduto == "") {
    die("Erro: informe o nome do produto.");
}

if ($valor == "") {
    die("Erro: informe o valor do produto.");
}

if ($quantidade == "") {
    die("Erro: informe a quantidade.");
}

if ($prateleira == "") {
    die("Erro: informe a prateleira.");
}


// ======================================================
// VERIFICAR A IMAGEM
// ======================================================

if (!isset($_FILES["imagem"])) {
    die("Erro: nenhuma imagem foi enviada.");
}


if ($_FILES["imagem"]["error"] != UPLOAD_ERR_OK) {

    switch ($_FILES["imagem"]["error"]) {

        case UPLOAD_ERR_INI_SIZE:
            die("Erro: a imagem é maior que o limite permitido pelo PHP.");

        case UPLOAD_ERR_FORM_SIZE:
            die("Erro: a imagem é maior que o limite permitido pelo formulário.");

        case UPLOAD_ERR_PARTIAL:
            die("Erro: a imagem foi enviada apenas parcialmente.");

        case UPLOAD_ERR_NO_FILE:
            die("Erro: nenhuma imagem foi selecionada.");

        default:
            die("Erro desconhecido no envio da imagem.");
    }
}


// ======================================================
// PEGAR INFORMAÇÕES DA IMAGEM
// ======================================================

$arquivoTemporario = $_FILES["imagem"]["tmp_name"];
$nomeOriginal = $_FILES["imagem"]["name"];
$tamanho = $_FILES["imagem"]["size"];


// ======================================================
// LIMITE DE 5 MB
// ======================================================

if ($tamanho > 5 * 1024 * 1024) {
    die("Erro: a imagem deve ter no máximo 5 MB.");
}


// ======================================================
// VERIFICAR SE O ARQUIVO É UMA IMAGEM
// ======================================================

$informacoesImagem = getimagesize($arquivoTemporario);

if ($informacoesImagem === false) {
    die("Erro: o arquivo enviado não é uma imagem válida.");
}


// ======================================================
// EXTENSÕES PERMITIDAS
// ======================================================

$tiposPermitidos = array(
    IMAGETYPE_JPEG => "jpg",
    IMAGETYPE_PNG  => "png",
    IMAGETYPE_GIF  => "gif",
    IMAGETYPE_WEBP => "webp"
);


$tipoImagem = $informacoesImagem[2];


if (!isset($tiposPermitidos[$tipoImagem])) {
    die("Erro: formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP.");
}


$extensao = $tiposPermitidos[$tipoImagem];


// ======================================================
// CRIAR PASTA UPLOADS
// ======================================================

$pastaUploads = __DIR__ . DIRECTORY_SEPARATOR . "uploads";


if (!is_dir($pastaUploads)) {

    if (!mkdir($pastaUploads, 0777, true)) {
        die("Erro: não foi possível criar a pasta uploads.");
    }
}


// ======================================================
// CRIAR NOME ÚNICO PARA A IMAGEM
// ======================================================

$nomeImagem = uniqid("produto_", true) . "." . $extensao;

$caminhoImagem = $pastaUploads . DIRECTORY_SEPARATOR . $nomeImagem;


// ======================================================
// SALVAR A IMAGEM
// ======================================================

if (!move_uploaded_file($arquivoTemporario, $caminhoImagem)) {
    die("Erro: não foi possível salvar a imagem na pasta uploads.");
}


// ======================================================
// INSERIR PRODUTO NO BANCO
// ======================================================

$sql = "INSERT INTO dados
        (nomeproduto, valor, quantidade, prateleira, imagem)
        VALUES (?, ?, ?, ?, ?)";


$stmt = mysqli_prepare($conexao, $sql);


if (!$stmt) {

    // Apaga a imagem caso o SQL dê erro
    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }

    die("Erro ao preparar o cadastro: " . mysqli_error($conexao));
}


// ======================================================
// PASSAR OS VALORES PARA O SQL
// ======================================================

mysqli_stmt_bind_param(
    $stmt,
    "sdiss",
    $nomeproduto,
    $valor,
    $quantidade,
    $prateleira,
    $nomeImagem
);


// ======================================================
// EXECUTAR
// ======================================================

if (mysqli_stmt_execute($stmt)) {

    // ==================================================
    // CADASTRO REALIZADO
    // ==================================================

    $valorFormatado = number_format(
        (float)$valor,
        2,
        ",",
        "."
    );

    echo "<!DOCTYPE html>";
    echo "<html lang='pt-BR'>";

    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Produto cadastrado</title>";
    echo "</head>";

    echo "<body>";

    echo "<h1>Produto cadastrado com sucesso!</h1>";

    echo "<p>";
    echo "Produto: <strong>";
    echo htmlspecialchars($nomeproduto);
    echo "</strong>";
    echo "</p>";

    echo "<p>";
    echo "Valor: R$ " . $valorFormatado;
    echo "</p>";

    echo "<p>";
    echo "Quantidade: " . htmlspecialchars($quantidade);
    echo "</p>";

    echo "<p>";
    echo "Prateleira: " . htmlspecialchars($prateleira);
    echo "</p>";

    echo "<p>Imagem:</p>";

    echo "<img ";
    echo "src='uploads/" . htmlspecialchars($nomeImagem) . "' ";
    echo "width='200' ";
    echo "alt='Imagem do produto'>";
    
    echo "<br><br>";

    echo "<a href='cadastrargerente.html'>";
    echo "Cadastrar outro produto";
    echo "</a>";

    echo "<br><br>";

    echo "<a href='iniciogerente.html'>";
    echo "Voltar para o início";
    echo "</a>";

    echo "</body>";
    echo "</html>";


} else {

    // ==================================================
    // ERRO NO CADASTRO
    // ==================================================

    $codigoErro = mysqli_errno($conexao);
    $mensagemErro = mysqli_stmt_error($stmt);


    // ==================================================
    // PRODUTO DUPLICADO
    // ==================================================

    if ($codigoErro == 1062) {

        // Remove a imagem que foi salva
        if (file_exists($caminhoImagem)) {
            unlink($caminhoImagem);
        }

        echo "<h1>Produto já cadastrado!</h1>";

        echo "<p>";
        echo "Este produto já existe no banco de dados.";
        echo "</p>";

        echo "<a href='cadastrargerente.html'>";
        echo "Voltar";
        echo "</a>";


    } else {

        // Remove a imagem
        if (file_exists($caminhoImagem)) {
            unlink($caminhoImagem);
        }

        echo "<h1>Erro ao cadastrar produto!</h1>";

        echo "<p>";
        echo htmlspecialchars($mensagemErro);
        echo "</p>";

        echo "<a href='cadastrargerente.html'>";
        echo "Voltar";
        echo "</a>";
    }
}


// ======================================================
// FECHAR
// ======================================================

mysqli_stmt_close($stmt);
mysqli_close($conexao);