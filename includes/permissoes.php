<?php
function exigirPermissaoGerente($acao)
{
    if (($_SESSION['cargo'] ?? '') !== 'gerente') {
        http_response_code(403);
        exit('Acesso negado.');
    }

    $permitidas = array('cancelar_venda', 'gerenciar_estoque', 'relatorios', 'fiscal');
    if (!in_array($acao, $permitidas, true)) {
        http_response_code(403);
        exit('Permissão inválida.');
    }
}
