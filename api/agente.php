<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderErro(string $mensagem, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(array('ok' => false, 'erro' => $mensagem), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function limitarTexto(string $texto, int $limite): string
{
    $texto = trim($texto);
    if (mb_strlen($texto, 'UTF-8') <= $limite) {
        return $texto;
    }
    return mb_substr($texto, 0, $limite, 'UTF-8');
}

function consultaUmaLinha(mysqli $conexao, string $sql, string $tipos = '', array $params = array()): array
{
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return array();
    }

    if ($tipos !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return array();
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $linha = $resultado ? (mysqli_fetch_assoc($resultado) ?: array()) : array();
    mysqli_stmt_close($stmt);
    return $linha;
}

function consultaVariasLinhas(mysqli $conexao, string $sql, string $tipos = '', array $params = array()): array
{
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return array();
    }

    if ($tipos !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return array();
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $linhas = array();
    if ($resultado) {
        while ($linha = mysqli_fetch_assoc($resultado)) {
            $linhas[] = $linha;
        }
    }
    mysqli_stmt_close($stmt);
    return $linhas;
}

function contextoFarmacia(mysqli $conexao, int $farmaciaId, ?int $usuarioId = null): array
{
    $farmacia = consultaUmaLinha(
        $conexao,
        'SELECT id, nome FROM farmacias WHERE id = ? LIMIT 1',
        'i',
        array($farmaciaId)
    );

    $resumoHoje = consultaUmaLinha(
        $conexao,
        "SELECT COUNT(*) AS vendas_hoje, COALESCE(SUM(valor_total),0) AS faturamento_hoje, COALESCE(AVG(valor_total),0) AS ticket_medio_hoje
         FROM vendas WHERE farmacia_id = ? AND status = 'concluida' AND DATE(data_venda) = CURDATE()",
        'i',
        array($farmaciaId)
    );

    $resumoMes = consultaUmaLinha(
        $conexao,
        "SELECT COUNT(*) AS vendas_mes, COALESCE(SUM(valor_total),0) AS faturamento_mes
         FROM vendas WHERE farmacia_id = ? AND status = 'concluida'
         AND YEAR(data_venda)=YEAR(CURDATE()) AND MONTH(data_venda)=MONTH(CURDATE())",
        'i',
        array($farmaciaId)
    );

    $estoque = consultaUmaLinha(
        $conexao,
        'SELECT COUNT(*) AS produtos_ativos,
                SUM(CASE WHEN quantidade <= estoque_minimo THEN 1 ELSE 0 END) AS estoque_baixo,
                COALESCE(SUM(quantidade),0) AS unidades_em_estoque
         FROM produtos WHERE farmacia_id = ? AND ativo = 1',
        'i',
        array($farmaciaId)
    );

    $estoqueBaixo = consultaVariasLinhas(
        $conexao,
        'SELECT nome, quantidade, estoque_minimo, prateleira
         FROM produtos WHERE farmacia_id = ? AND ativo = 1 AND quantidade <= estoque_minimo
         ORDER BY quantidade ASC, nome ASC LIMIT 8',
        'i',
        array($farmaciaId)
    );

    $validade = consultaVariasLinhas(
        $conexao,
        'SELECT p.nome AS produto, l.numero_lote, l.quantidade, l.validade
         FROM lotes l INNER JOIN produtos p ON p.id = l.produto_id
         WHERE l.farmacia_id = ? AND l.quantidade > 0
         AND l.validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 45 DAY)
         ORDER BY l.validade ASC LIMIT 8',
        'i',
        array($farmaciaId)
    );

    $maisVendidos = consultaVariasLinhas(
        $conexao,
        "SELECT p.nome, SUM(iv.quantidade) AS quantidade_vendida, COALESCE(SUM(iv.subtotal),0) AS total
         FROM itens_venda iv
         INNER JOIN vendas v ON v.id = iv.venda_id
         INNER JOIN produtos p ON p.id = iv.produto_id
         WHERE v.farmacia_id = ? AND v.status = 'concluida'
         AND v.data_venda >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY p.id, p.nome
         ORDER BY quantidade_vendida DESC LIMIT 6",
        'i',
        array($farmaciaId)
    );

    $dados = array(
        'farmacia' => $farmacia,
        'hoje' => $resumoHoje,
        'mes_atual' => $resumoMes,
        'estoque' => $estoque,
        'produtos_com_estoque_baixo' => $estoqueBaixo,
        'lotes_vencendo_em_45_dias' => $validade,
        'mais_vendidos_ultimos_30_dias' => $maisVendidos,
    );

    if ($usuarioId) {
        $dados['desempenho_do_usuario_hoje'] = consultaUmaLinha(
            $conexao,
            "SELECT COUNT(*) AS vendas, COALESCE(SUM(valor_total),0) AS total
             FROM vendas WHERE farmacia_id = ? AND usuario_id = ? AND status = 'concluida' AND DATE(data_venda)=CURDATE()",
            'ii',
            array($farmaciaId, $usuarioId)
        );
    }

    return $dados;
}

function contextoAdministrador(mysqli $conexao): array
{
    $geral = consultaUmaLinha(
        $conexao,
        "SELECT
            (SELECT COUNT(*) FROM farmacias WHERE ativo = 1) AS farmacias_ativas,
            (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) AS usuarios_ativos,
            (SELECT COUNT(*) FROM vendas WHERE status='concluida' AND DATE(data_venda)=CURDATE()) AS vendas_hoje,
            (SELECT COALESCE(SUM(valor_total),0) FROM vendas WHERE status='concluida' AND DATE(data_venda)=CURDATE()) AS faturamento_hoje,
            (SELECT COUNT(*) FROM produtos WHERE ativo=1 AND quantidade <= estoque_minimo) AS produtos_estoque_baixo"
    );

    $porFarmacia = consultaVariasLinhas(
        $conexao,
        "SELECT f.id, f.nome,
                COUNT(v.id) AS vendas_hoje,
                COALESCE(SUM(v.valor_total),0) AS faturamento_hoje
         FROM farmacias f
         LEFT JOIN vendas v ON v.farmacia_id=f.id AND v.status='concluida' AND DATE(v.data_venda)=CURDATE()
         WHERE f.ativo=1
         GROUP BY f.id, f.nome
         ORDER BY faturamento_hoje DESC
         LIMIT 20"
    );

    return array(
        'visao_geral' => $geral,
        'resumo_por_farmacia_hoje' => $porFarmacia,
    );
}

function obterTokenGoogle(): string
{
    $url = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token';
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Metadata-Flavor: Google'),
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
    ));
    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$resposta) {
        return '';
    }

    $json = json_decode($resposta, true);
    return is_array($json) ? (string) ($json['access_token'] ?? '') : '';
}

function chamarVertex(string $systemPrompt, array $historico, string $mensagem): string
{
    $projeto = getenv('GCP_PROJECT_ID') ?: (getenv('GOOGLE_CLOUD_PROJECT') ?: 'farmacia-508118');
    $local = getenv('VERTEX_LOCATION') ?: 'us-central1';
    $modelo = getenv('VERTEX_MODEL') ?: 'gemini-2.5-flash';
    $token = obterTokenGoogle();

    if ($token === '') {
        throw new RuntimeException('Não foi possível autenticar no Google Cloud.');
    }

    $contents = array();
    foreach (array_slice($historico, -10) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $role = ($item['role'] ?? '') === 'assistant' ? 'model' : 'user';
        $text = limitarTexto((string) ($item['text'] ?? ''), 1600);
        if ($text === '') {
            continue;
        }
        $contents[] = array('role' => $role, 'parts' => array(array('text' => $text)));
    }
    $contents[] = array('role' => 'user', 'parts' => array(array('text' => limitarTexto($mensagem, 1800))));

    $payload = array(
        'systemInstruction' => array(
            'parts' => array(array('text' => $systemPrompt)),
        ),
        'contents' => $contents,
        'generationConfig' => array(
            'temperature' => 0.25,
            'maxOutputTokens' => 900,
        ),
    );

    $endpoint = sprintf(
        'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
        rawurlencode($local),
        rawurlencode($projeto),
        rawurlencode($local),
        rawurlencode($modelo)
    );

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
        ),
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 35,
    ));

    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($status < 200 || $status >= 300 || !$resposta) {
        $detalhe = $erroCurl ?: ('HTTP ' . $status);
        throw new RuntimeException('Vertex AI indisponível: ' . $detalhe);
    }

    $json = json_decode($resposta, true);
    $partes = $json['candidates'][0]['content']['parts'] ?? array();
    $texto = '';
    foreach ($partes as $parte) {
        if (isset($parte['text'])) {
            $texto .= $parte['text'];
        }
    }

    $texto = trim($texto);
    if ($texto === '') {
        throw new RuntimeException('O modelo não retornou texto.');
    }
    return $texto;
}

$ehAdmin = isset($_SESSION['admin_id']) && (($_SESSION['perfil'] ?? '') === 'administrador');
$ehUsuario = isset($_SESSION['usuario_id'], $_SESSION['farmacia_id'])
    && in_array(($_SESSION['cargo'] ?? ''), array('gerente', 'balconista'), true);

if (!$ehAdmin && !$ehUsuario) {
    responderErro('Sua sessão expirou. Entre novamente no sistema.', 401);
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responderErro('Requisição inválida.');
}

$mensagem = limitarTexto((string) ($entrada['mensagem'] ?? ''), 1800);
$historico = is_array($entrada['historico'] ?? null) ? $entrada['historico'] : array();
$pagina = limitarTexto((string) ($entrada['pagina'] ?? ''), 180);

if ($mensagem === '') {
    responderErro('Digite uma mensagem para o agente.');
}

require_once __DIR__ . '/../gerente/conexaoDB.php';

if ($ehAdmin) {
    $perfil = 'administrador';
    $nome = (string) ($_SESSION['admin_nome'] ?? 'Administrador');
    $contexto = contextoAdministrador($conexao);
    $escopo = 'Você pode responder com a visão geral das farmácias. Não exponha senhas, credenciais, tokens, dados secretos ou conteúdo de colunas de senha.';
} else {
    $perfil = (string) $_SESSION['cargo'];
    $nome = (string) ($_SESSION['usuario_nome'] ?? 'Usuário');
    $farmaciaId = (int) $_SESSION['farmacia_id'];
    $usuarioId = (int) $_SESSION['usuario_id'];
    $contexto = contextoFarmacia($conexao, $farmaciaId, $perfil === 'balconista' ? $usuarioId : null);
    $escopo = 'Responda somente com dados da farmácia desta sessão. Nunca invente ou revele dados de outras unidades.';
}

$contextoJson = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

$systemPrompt = <<<PROMPT
Você é o FarmaCerta IA, assistente interno do sistema de gestão de farmácias FarmaCerta.
Usuário atual: {$nome}
Perfil: {$perfil}
Página atual do sistema: {$pagina}

Seu trabalho é ser uma assistente útil, versátil e natural. Você pode ajudar tanto com o FarmaCerta quanto com perguntas gerais, explicações, ideias e cálculos matemáticos. Quando a pergunta envolver dados internos da farmácia, use o CONTEXTO ATUAL DO SISTEMA como fonte de verdade.
{$escopo}

REGRAS IMPORTANTES:
- Responda em português do Brasil, de forma clara, natural e prática. Pode detalhar mais quando a pergunta exigir.
- Não limite a conversa apenas ao FarmaCerta. Perguntas gerais que não dependem de dados internos podem ser respondidas normalmente usando seu conhecimento geral.
- Você pode fazer cálculos matemáticos, incluindo soma, subtração, multiplicação, divisão, porcentagens, descontos, acréscimos, médias, regra de três, lucro, margem, markup, ticket médio e outros cálculos solicitados.
- Em cálculos, mostre o resultado e, quando for útil, uma conta ou explicação curta. Confira a aritmética antes de responder.
- Diferencie dados internos de números fornecidos pelo usuário. Você pode calcular livremente usando números informados pelo usuário ou constantes matemáticas.
- Para fatos, números, vendas, estoque, clientes, funcionários ou qualquer outro dado INTERNO do FarmaCerta, não invente informações: use somente o CONTEXTO ATUAL DO SISTEMA.
- Se uma pergunta sobre o FarmaCerta exigir um dado interno que não está no contexto, diga claramente que esse dado não está disponível para o agente nessa conversa.
- Você é SOMENTE LEITURA em relação ao sistema. Nunca diga que alterou, cadastrou, excluiu, cancelou ou salvou algo no FarmaCerta.
- Se a pessoa pedir uma alteração no sistema, explique onde ou como ela pode fazer isso, quando souber.
- Nunca forneça senha, hash, token, segredo, variável de ambiente, credencial ou dado de autenticação.
- Valores monetários devem ser apresentados em reais (R$) quando aplicável.
- Datas devem ser apresentadas preferencialmente no formato dd/mm/aaaa.
- Para balconistas, quando a pergunta for sobre o FarmaCerta, priorize produtos, clientes, carrinho, vendas e estoque visível da própria unidade.
- Para gerentes, quando a pergunta for sobre o FarmaCerta, priorize estoque, validade, vendas, relatórios, fornecedores, funcionários e operação da própria unidade.
- Para administradores, quando a pergunta for sobre o FarmaCerta, priorize visão geral das unidades e administração do sistema.

CONTEXTO ATUAL DO SISTEMA:
{$contextoJson}
PROMPT;

try {
    $resposta = chamarVertex($systemPrompt, $historico, $mensagem);
    echo json_encode(array('ok' => true, 'resposta' => $resposta), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('FarmaCerta IA: ' . $e->getMessage());
    responderErro('O agente IA não conseguiu responder agora. Verifique a configuração do Vertex AI no Google Cloud.', 503);
}
