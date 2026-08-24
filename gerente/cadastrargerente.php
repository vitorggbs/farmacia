<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/conexaoDB.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome = $_POST['nomeProduto'];
    $preco = str_replace(',', '.', $_POST['valor']);
    $quantidade = $_POST['quantidade'];
    $prateleira = $_POST['prateleira'];

    $descricao = '';
    $estoque_minimo = 0;

    $imagem = $_FILES['imagem']['name'];
    $temporario = $_FILES['imagem']['tmp_name'];

    $pasta = 'uploads/';

    if (!is_dir($pasta)) {
        mkdir($pasta);
    }

    $nomeImagem = time() . '_' . $imagem;

    move_uploaded_file(
        $temporario,
        $pasta . $nomeImagem
    );

    $sql = "INSERT INTO produtos
            (
                nome,
                descricao,
                preco,
                quantidade,
                estoque_minimo,
                imagem,
                criado_em,
                prateleira
            )
            VALUES
            (?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssdiiss",
        $nome,
        $descricao,
        $preco,
        $quantidade,
        $estoque_minimo,
        $nomeImagem,
        $prateleira
    );

    if (mysqli_stmt_execute($stmt)) {

        echo "<h1>Produto cadastrado com sucesso!</h1>";

        echo "<p>Nome: " . htmlspecialchars($nome) . "</p>";

        echo "<p>Preço: R$ " .
             number_format($preco, 2, ',', '.') .
             "</p>";

        echo "<p>Quantidade: " .
             htmlspecialchars($quantidade) .
             "</p>";

        echo "<p>Prateleira: " .
             htmlspecialchars($prateleira) .
             "</p>";

        echo "<br>";

        echo "<a href='cadastrargerente.html'>";
        echo "Cadastrar outro produto";
        echo "</a>";

        echo "<br><br>";

        echo "<a href='iniciogerente.html'>";
        echo "Voltar para o início";
        echo "</a>";

    } else {

        echo "Erro ao cadastrar: " .
             mysqli_error($conexao);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexao);
}

?>
