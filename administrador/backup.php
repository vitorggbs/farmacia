<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirAdministrador();


/*
|--------------------------------------------------------------------------
| Geração do backup
|--------------------------------------------------------------------------
*/

if (isset($_GET['baixar'])) {

    header('Content-Type: application/sql; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="farmacerta_backup_' .
        date('Y-m-d_H-i') .
        '.sql"'
    );

    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";


    /*
    |--------------------------------------------------------------------------
    | Busca todas as tabelas
    |--------------------------------------------------------------------------
    */

    $tabelas = mysqli_query(
        $conexao,
        'SHOW TABLES'
    );


    while ($linha = mysqli_fetch_row($tabelas)) {

        $tabela = $linha[0];


        /*
        |--------------------------------------------------------------------------
        | Estrutura da tabela
        |--------------------------------------------------------------------------
        */

        $resultadoCreate = mysqli_query(
            $conexao,
            "SHOW CREATE TABLE `" . $tabela . "`"
        );

        $estrutura = mysqli_fetch_assoc(
            $resultadoCreate
        );


        echo "DROP TABLE IF EXISTS `" . $tabela . "`;\n";

        echo $estrutura['Create Table'] . ";\n\n";


        /*
        |--------------------------------------------------------------------------
        | Dados da tabela
        |--------------------------------------------------------------------------
        */

        $dados = mysqli_query(
            $conexao,
            "SELECT * FROM `" . $tabela . "`"
        );


        while ($row = mysqli_fetch_assoc($dados)) {

            $colunas = array();
            $valores = array();


            foreach ($row as $coluna => $valor) {

                $colunas[] = '`' . $coluna . '`';


                if ($valor === null) {

                    $valores[] = 'NULL';

                } else {

                    $valores[] =
                        "'" .
                        mysqli_real_escape_string(
                            $conexao,
                            (string) $valor
                        ) .
                        "'";
                }
            }


            echo
                "INSERT INTO `" .
                $tabela .
                "` (" .
                implode(',', $colunas) .
                ") VALUES (" .
                implode(',', $valores) .
                ");\n";
        }


        echo "\n";
    }


    echo "SET FOREIGN_KEY_CHECKS=1;\n";

    exit;
}


require_once __DIR__ . '/cabecalhoadmin.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php recursosCabeca('Backup'); ?>

    <style>

        /*
        |--------------------------------------------------------------------------
        | Botão de Backup - Vermelho
        |--------------------------------------------------------------------------
        */

        .btn-backup {
            background-color: #e52b38 !important;
            border-color: #e52b38 !important;
            color: #ffffff !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-backup:hover {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
        }

        .btn-backup:focus,
        .btn-backup:active {
            background-color: #c91f2d !important;
            border-color: #c91f2d !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.25) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Linha de seleção - Vermelha
        |--------------------------------------------------------------------------
        */

        .table-hover > tbody > tr:hover {
            --bs-table-hover-bg: rgba(229, 43, 56, 0.10) !important;
            --bs-table-hover-color: inherit !important;
            background-color: rgba(229, 43, 56, 0.10) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Campos em foco - Vermelho
        |--------------------------------------------------------------------------
        */

        .form-control:focus,
        .form-select:focus {
            border-color: #e52b38 !important;
            box-shadow: 0 0 0 0.2rem rgba(229, 43, 56, 0.20) !important;
        }

    </style>

</head>

<body>

<?php cabecalhoAdmin('FarmaCerta - Administrador'); ?>


<main class="container py-4">

    <section class="card shadow-sm rounded-4 p-4">

        <h2 class="h3 fw-bold">
            BACKUP DO BANCO
        </h2>

        <p>
            Gere um arquivo SQL com a estrutura e os dados atuais do sistema.
        </p>


        <div class="alert alert-warning">

            Guarde o arquivo em local seguro.
            Ele contém dados do sistema.

        </div>


        <a
            class="btn btn-backup rounded-pill px-4"
            href="backup.php?baixar=1"
        >
            BAIXAR BACKUP .SQL
        </a>

    </section>

</main>


<?php recursosRodape(); ?>

</body>
</html>
