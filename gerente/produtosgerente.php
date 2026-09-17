<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';

exigirLogin('gerente');

require_once __DIR__ . '/conexaoDB.php';
require_once __DIR__ . '/../cabecalho.php';

$farmaciaId = (int) $_SESSION['farmacia_id'];

$categorias = mysqli_query(
    $conexao,
    'SELECT id, nome 
     FROM categorias 
     WHERE ativo = 1 
     ORDER BY nome'
);

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

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $farmaciaId
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('FarmaCerta - Produtos'); ?>

    <style>
        /* ==========================================
           CAMPO DE PREÇO
           Formato: R$ 9.999.999.999.999,99
           Limite: 13 dígitos inteiros + 2 centavos
        ========================================== */

        .campo-preco {
            background-color: var(--fc-surface, #fff) !important;
            border: 2px solid rgba(229, 43, 56, 0.35) !important;
            border-radius: 30px !important;
            box-shadow: 0 0 0 4px rgba(229, 43, 56, 0.12) !important;
            color: var(--fc-text, #333) !important;
        }

        .campo-preco:focus {
            background-color: var(--fc-surface, #fff) !important;
            border-color: #e52b38 !important;
            box-shadow: 0 0 0 4px rgba(229, 43, 56, 0.20) !important;
            outline: none !important;
        }

        [data-bs-theme="dark"] .campo-preco {
            background-color: #1e2020 !important;
            color: #f1f5f9 !important;
            border-color: #3d4141 !important;
            box-shadow: none !important;
        }

        [data-bs-theme="dark"] .campo-preco:focus {
            background-color: #232525 !important;
            color: #f1f5f9 !important;
            border-color: var(--fc-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(230, 57, 70, 0.35) !important;
        }
    </style>

</head>

<body>

<?php cabecalho('FarmaCerta - Gerente', 'gerente', 'produtos'); ?>


<main class="container py-4">


    <!-- ==========================================
         CADASTRAR PRODUTO
    =========================================== -->

    <section
        class="card card-brand rounded-4 p-4 mb-4"
        id="cadastrar"
    >

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

            <h2 class="h3 fw-bold mb-0">
                CADASTRAR PRODUTO
            </h2>

            <a
                class="btn btn-outline-light rounded-pill"
                href="categorias.php"
            >
                GERENCIAR CATEGORIAS
            </a>

        </div>


        <?php if (isset($_GET['cadastro'])) { ?>

            <div class="alert alert-light text-success fw-bold">
                Produto cadastrado com sucesso!
            </div>

        <?php } ?>


        <form
            action="cadastrargerente.php"
            method="POST"
            enctype="multipart/form-data"
            class="row g-3"
        >


            <!-- ==================================
                 NOME DO PRODUTO
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="nomeProduto"
                >
                    Nome do produto
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="nomeProduto"
                    name="nomeProduto"
                    placeholder="Digite o nome do produto"
                    required
                >

            </div>


            <!-- ==================================
                 PREÇO
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="valor"
                >
                    Preço
                </label>

                <input
                    class="form-control campo-preco"
                    type="text"
                    id="valor"
                    name="valor"
                    inputmode="decimal"
                    autocomplete="off"
                    maxlength="23"
                    placeholder="R$ 0,00"
                    oninput="mascaraMoeda(this)"
                    required
                >

            </div>


            <!-- ==================================
                 QUANTIDADE
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="quantidade"
                >
                    Quantidade
                </label>

                <input
                    class="form-control"
                    type="number"
                    id="quantidade"
                    name="quantidade"
                    min="0"
                    placeholder="Digite a quantidade em estoque"
                    required
                >

            </div>


            <!-- ==================================
                 ESTOQUE MÍNIMO
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="estoque_minimo"
                >
                    Estoque mínimo
                </label>

                <input
                    class="form-control"
                    type="number"
                    id="estoque_minimo"
                    name="estoque_minimo"
                    min="0"
                    value="5"
                    placeholder="Digite o estoque mínimo"
                    required
                >

            </div>


            <!-- ==================================
                 CATEGORIA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="categoria_id"
                >
                    Categoria
                </label>

                <select
                    class="form-select"
                    id="categoria_id"
                    name="categoria_id"
                    required
                >

                    <option value="">
                        Selecione
                    </option>

                    <?php foreach ($listaCategorias as $cat) { ?>

                        <option
                            value="<?php echo $cat['id']; ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $cat['nome']
                            );

                            ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- ==================================
                 PRATELEIRA
                 MANTIDO COMO ESTAVA
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="prateleira"
                >
                    Prateleira
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="prateleira"
                    name="prateleira"
                    placeholder="Ex.: A3"
                    required
                >

            </div>


            <!-- ==================================
                 IMAGEM
            =================================== -->

            <div class="col-12 col-md-6">

                <label
                    class="form-label fw-bold"
                    for="imagem"
                >
                    Imagem
                </label>

                <input
                    class="form-control"
                    type="file"
                    id="imagem"
                    name="imagem"
                    accept="image/*"
                    required
                >

            </div>


            <!-- ==================================
                 BOTÃO
            =================================== -->

            <div class="col-12">

                <button
                    class="btn btn-outline-light rounded-pill fw-bold"
                    type="submit"
                >
                    CADASTRAR PRODUTO
                </button>

            </div>

        </form>

    </section>



    <!-- ==========================================
         PRODUTOS CADASTRADOS
    =========================================== -->

    <section class="card card-brand rounded-4 p-4">

        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3"
        >

            <h2 class="h4 fw-bold mb-0">
                PRODUTOS CADASTRADOS
            </h2>


            <!-- BUSCAR PRODUTO
                 MANTIDO COMO ESTAVA -->

            <input
                class="form-control"
                style="max-width:280px;"
                type="text"
                id="busca"
                placeholder="Buscar produto..."
                onkeyup="buscarProduto()"
            >

        </div>


        <div class="table-responsive bg-white rounded-3">

            <table
                class="table table-hover align-middle mb-0"
                id="tabela-produtos"
            >

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


                        <!-- FOTO -->

                        <td>

                            <?php if (!empty($produto['imagem'])) { ?>

                                <img
                                    class="produto-img rounded"
                                    src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                    width="60"
                                    height="60"
                                    alt="Produto"
                                >

                            <?php } ?>

                        </td>


                        <!-- PRODUTO -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $produto['nome']
                            );

                            ?>

                        </td>


                        <!-- CATEGORIA -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $produto['categoria'] ?: 'Sem categoria'
                            );

                            ?>

                        </td>


                        <!-- PREÇO -->

                        <td>

                            R$

                            <?php

                            echo number_format(
                                $produto['preco'],
                                2,
                                ',',
                                '.'
                            );

                            ?>

                        </td>


                        <!-- ESTOQUE -->

                        <td>

                            <?php

                            echo (int) $produto['quantidade'];

                            ?>


                            <?php if ($produto['quantidade'] <= 0) { ?>

                                <br>

                                <span class="text-danger fw-bold">
                                    SEM ESTOQUE
                                </span>

                            <?php } elseif ($produto['quantidade'] <= $produto['estoque_minimo']) { ?>

                                <br>

                                <span class="text-danger fw-bold">
                                    ESTOQUE BAIXO
                                </span>

                            <?php } ?>

                        </td>


                        <!-- PRATELEIRA -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $produto['prateleira']
                            );

                            ?>

                        </td>


                        <!-- AÇÕES -->

                        <td>


                            <!-- ==================================
                                 EDITAR PRODUTO
                            =================================== -->

                            <details class="mb-2">

                                <summary
                                    class="btn btn-sm btn-primary rounded-pill"
                                >
                                    EDITAR
                                </summary>


                                <form
                                    action="editarproduto.php"
                                    method="POST"
                                    class="mt-2 d-grid gap-2"
                                >

                                    <input
                                        type="hidden"
                                        name="produto_id"
                                        value="<?php echo $produto['id']; ?>"
                                    >


                                    <!-- NOME -->

                                    <input
                                        class="form-control form-control-sm"
                                        type="text"
                                        name="nome"
                                        value="<?php echo htmlspecialchars($produto['nome']); ?>"
                                        placeholder="Digite o nome do produto"
                                        required
                                    >


                                    <!-- PREÇO -->

                                    <input
                                        class="form-control form-control-sm campo-preco"
                                        type="text"
                                        name="preco"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        maxlength="23"
                                        value="<?php echo number_format((float) $produto['preco'], 2, ',', '.'); ?>"
                                        oninput="mascaraMoeda(this)"
                                        placeholder="R$ 0,00"
                                        required
                                    >


                                    <!-- ESTOQUE MÍNIMO -->

                                    <input
                                        class="form-control form-control-sm"
                                        type="number"
                                        name="estoque_minimo"
                                        value="<?php echo $produto['estoque_minimo']; ?>"
                                        placeholder="Digite o estoque mínimo"
                                        required
                                    >


                                    <!-- PRATELEIRA -->

                                    <input
                                        class="form-control form-control-sm"
                                        type="text"
                                        name="prateleira"
                                        value="<?php echo htmlspecialchars($produto['prateleira']); ?>"
                                        placeholder="Ex.: A3"
                                        required
                                    >


                                    <!-- CATEGORIA -->

                                    <select
                                        class="form-select form-select-sm"
                                        name="categoria_id"
                                        required
                                    >

                                        <option value="">
                                            Selecione
                                        </option>


                                        <?php foreach ($listaCategorias as $cat) { ?>

                                            <option
                                                value="<?php echo $cat['id']; ?>"
                                                <?php echo ((int) $produto['categoria_id'] === (int) $cat['id']) ? 'selected' : ''; ?>
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $cat['nome']
                                                );

                                                ?>

                                            </option>

                                        <?php } ?>

                                    </select>


                                    <button
                                        class="btn btn-sm btn-primary rounded-pill"
                                        type="submit"
                                    >
                                        SALVAR
                                    </button>

                                </form>

                            </details>


                            <!-- ==================================
                                 ENTRADA COM LOTE
                            =================================== -->

                            <a
                                class="btn btn-sm btn-outline-primary rounded-pill mb-2"
                                href="entradasmercadoria.php"
                            >
                                ENTRADA COM LOTE
                            </a>


                            <!-- ==================================
                                 EXCLUIR
                            =================================== -->

                            <form
                                action="excluirproduto.php"
                                method="POST"
                                onsubmit="return confirm('Excluir produto?')"
                            >

                                <input
                                    type="hidden"
                                    name="produto_id"
                                    value="<?php echo $produto['id']; ?>"
                                >

                                <button
                                    class="btn btn-sm btn-danger rounded-pill"
                                    type="submit"
                                >
                                    EXCLUIR
                                </button>

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

/* ==========================================
   MÁSCARA DE PREÇO

   Formato:
   R$ 0,01
   R$ 1,23
   R$ 1.234,56
   R$ 9.999.999.999.999,99

   Limite máximo:
   9.999.999.999.999,99
========================================== */

function mascaraMoeda(campo) {

    var numeros = campo.value.replace(/\D/g, '');

    /* Máximo de 15 dígitos:
       13 para reais + 2 para centavos. */
    numeros = numeros.substring(0, 15);

    if (numeros.length === 0) {
        campo.value = '';
        return;
    }

    /* Garante pelo menos 3 dígitos para
       separar reais e centavos. */
    numeros = numeros.padStart(3, '0');

    var centavos = numeros.slice(-2);
    var reais = numeros.slice(0, -2);

    reais = reais.replace(/^0+(?=\d)/, '');

    /* Coloca os pontos a cada 3 dígitos. */
    reais = reais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    campo.value = 'R$ ' + reais + ',' + centavos;
}


/* ==========================================
   CONVERTE O PREÇO PARA O FORMATO DO PHP

   Exemplo:
   R$ 1.234,56 -> 1234.56
========================================== */

function prepararPrecoParaEnvio(campo) {

    var valor = campo.value
        .replace(/R\$/g, '')
        .replace(/\./g, '')
        .replace(',', '.')
        .trim();

    if (valor === '') {
        valor = '0.00';
    }

    campo.value = valor;
}


/* ==========================================
   MÁSCARA INICIAL DOS PREÇOS DE EDIÇÃO
========================================== */

document.addEventListener('DOMContentLoaded', function () {

    var camposPreco = document.querySelectorAll('.campo-preco');

    camposPreco.forEach(function (campo) {

        if (campo.value.trim() !== '') {
            mascaraMoeda(campo);
        }

    });


    /* Cadastro de produto */

    var formularioCadastro = document.querySelector(
        'form[action="cadastrargerente.php"]'
    );

    if (formularioCadastro) {

        formularioCadastro.addEventListener('submit', function () {

            prepararPrecoParaEnvio(
                document.getElementById('valor')
            );

        });

    }


    /* Edição de produto */

    var formulariosEdicao = document.querySelectorAll(
        'form[action="editarproduto.php"]'
    );

    formulariosEdicao.forEach(function (formulario) {

        formulario.addEventListener('submit', function () {

            var campo = formulario.querySelector(
                'input[name="preco"]'
            );

            if (campo) {
                prepararPrecoParaEnvio(campo);
            }

        });

    });

});


/* ==========================================
   BUSCAR PRODUTO
========================================== */

function buscarProduto() {

    var busca = document
        .getElementById('busca')
        .value
        .toLowerCase();

    var linhas = document.querySelectorAll(
        '#tabela-produtos tbody tr'
    );

    for (var i = 0; i < linhas.length; i++) {

        linhas[i].style.display =
            linhas[i]
                .textContent
                .toLowerCase()
                .includes(busca)
                ? ''
                : 'none';

    }

}


/* ==========================================
   CONFIRMAR REPOSIÇÃO
========================================== */

function confirmarReposicao() {

    if (!confirm('Confirma a reposição?')) {

        return false;

    }

    return confirm(
        'Tem certeza da quantidade?'
    );

}

</script>


</body>
</html>

<?php

mysqli_close($conexao);

?>