<?php

session_start();

require_once __DIR__ . '/../autenticacao.php';
require_once __DIR__ . '/../gerente/conexaoDB.php';

exigirLogin('balconista');

$farmaciaId = (int) $_SESSION['farmacia_id'];
$usuarioId = (int) $_SESSION['usuario_id'];
$carrinho = $_SESSION['carrinho'] ?? array();

if (count($carrinho) == 0) {
    header('Location: carrinhobalconista.php?erro=Carrinho vazio');
    exit;
}

$formaPagamento = $_POST['forma_pagamento'];
$formasPermitidas = array('dinheiro', 'pix', 'debito', 'credito');

if (!in_array($formaPagamento, $formasPermitidas)) {
    header('Location: carrinhobalconista.php?erro=Pagamento invalido');
    exit;
}

$cliente = trim($_POST['nome_cliente'] ?? '');
$cpfCliente = preg_replace('/\D/', '', $_POST['cpf_cliente'] ?? '');
$itens = array();
$total = 0;

mysqli_begin_transaction($conexao);

try {
    foreach ($carrinho as $produtoId => $quantidade) {
        $produtoId = (int) $produtoId;
        $quantidade = (int) $quantidade;

        $sql = 'SELECT nome, preco, quantidade
                FROM produtos
                WHERE id = ? AND farmacia_id = ? AND ativo = 1
                FOR UPDATE';

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $produtoId, $farmaciaId);
        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);
        $produto = mysqli_fetch_assoc($resultado);

        if (!$produto || $quantidade < 1 || $quantidade > $produto['quantidade']) {
            throw new Exception('Estoque insuficiente.');
        }

        $preco = (float) $produto['preco'];
        $subtotal = $preco * $quantidade;
        $total += $subtotal;

        $itens[] = array(
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'preco' => $preco,
            'subtotal' => $subtotal
        );
    }

    if ($formaPagamento == 'dinheiro') {
        $valorRecebido = (float) ($_POST['valor_recebido'] ?? 0);
    } else {
        $valorRecebido = $total;
    }

    if ($valorRecebido < $total) {
        throw new Exception('Valor recebido insuficiente.');
    }

    $troco = $valorRecebido - $total;

    $sql = 'INSERT INTO vendas
            (farmacia_id, usuario_id, cliente, cpf_cliente, valor_total,
             valor_recebido, troco, forma_pagamento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        'iissddds',
        $farmaciaId,
        $usuarioId,
        $cliente,
        $cpfCliente,
        $total,
        $valorRecebido,
        $troco,
        $formaPagamento
    );
    mysqli_stmt_execute($stmt);

    $vendaId = mysqli_insert_id($conexao);

    foreach ($itens as $item) {
        $produtoId = $item['produto_id'];
        $quantidade = $item['quantidade'];
        $preco = $item['preco'];
        $subtotal = $item['subtotal'];

        $sql = 'INSERT INTO itens_venda
                (venda_id, produto_id, quantidade, preco_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)';

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'iiidd', $vendaId, $produtoId, $quantidade, $preco, $subtotal);
        mysqli_stmt_execute($stmt);

        $sql = 'UPDATE produtos
                SET quantidade = quantidade - ?
                WHERE id = ? AND farmacia_id = ?';

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'iii', $quantidade, $produtoId, $farmaciaId);
        mysqli_stmt_execute($stmt);

        $tipo = 'saida';
        $observacao = 'Venda #' . $vendaId;

        $sql = 'INSERT INTO movimentacoes_estoque
                (farmacia_id, produto_id, usuario_id, tipo, quantidade, observacao)
                VALUES (?, ?, ?, ?, ?, ?)';

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'iiisis',
            $farmaciaId,
            $produtoId,
            $usuarioId,
            $tipo,
            $quantidade,
            $observacao
        );
        mysqli_stmt_execute($stmt);
    }

    mysqli_commit($conexao);
    $_SESSION['carrinho'] = array();

    header('Location: recibobalconista.php?id=' . $vendaId);
    exit;

} catch (Exception $erro) {
    mysqli_rollback($conexao);
    header('Location: carrinhobalconista.php?erro=' . urlencode($erro->getMessage()));
    exit;
}
