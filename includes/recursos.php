<?php

function prefixoRelativo(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($script, '/funcionarios/') !== false) {
        return '../../';
    }

    if (
        strpos($script, '/gerente/') !== false
        || strpos($script, '/balconista/') !== false
        || strpos($script, '/administrador/') !== false
    ) {
        return '../';
    }

    return '';
}

function prefixoPaginas(string $pasta): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($script, '/funcionarios/') !== false) {
        return '../';
    }

    if ($pasta === 'gerente' && strpos($script, '/balconista/') !== false) {
        return '../gerente/';
    }

    if ($pasta === 'balconista' && strpos($script, '/gerente/') !== false) {
        return '../balconista/';
    }

    return '';
}

function recursosCabeca(string $titulo): void
{
    $prefixo = htmlspecialchars(prefixoRelativo(), ENT_QUOTES, 'UTF-8');
    $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $arquivoTema = __DIR__ . '/../assets/css/tema.css';
    $versaoTema = is_file($arquivoTema) ? filemtime($arquivoTema) : time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $tituloSeguro; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $prefixo; ?>assets/LOGO_2.png">
    <link rel="manifest" href="<?php echo $prefixo; ?>manifest.webmanifest">
    <link rel="apple-touch-icon" href="<?php echo $prefixo; ?>assets/farmacerta-icon-192x192.png">
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FarmaCerta">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $prefixo; ?>assets/css/tema.css?v=<?php echo $versaoTema; ?>">
    <?php
}

function agenteIaDisponivel(): bool
{
    $admin = isset($_SESSION['admin_id']) && (($_SESSION['perfil'] ?? '') === 'administrador');
    $usuario = isset($_SESSION['usuario_id']) && in_array(($_SESSION['cargo'] ?? ''), array('gerente', 'balconista'), true);
    return $admin || $usuario;
}

function renderizarAgenteIa(): void
{
    if (!agenteIaDisponivel()) {
        return;
    }

    $prefixo = htmlspecialchars(prefixoRelativo(), ENT_QUOTES, 'UTF-8');
    $perfil = isset($_SESSION['admin_id']) ? 'Administrador' : ucfirst((string) ($_SESSION['cargo'] ?? 'Usuário'));
    $nome = isset($_SESSION['admin_id'])
        ? ($_SESSION['admin_nome'] ?? 'Administrador')
        : ($_SESSION['usuario_nome'] ?? 'Usuário');
    $identidade = htmlspecialchars($perfil . ' · ' . $nome, ENT_QUOTES, 'UTF-8');
    ?>
    <button id="farmaAiLauncher" class="farma-ai-launcher" type="button"
            aria-label="Abrir agente IA" aria-controls="farmaAiPanel" aria-expanded="false">
        <span class="farma-ai-dot" aria-hidden="true"></span>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3.2c.5 3.5 2.3 5.3 5.8 5.8-3.5.5-5.3 2.3-5.8 5.8-.5-3.5-2.3-5.3-5.8-5.8 3.5-.5 5.3-2.3 5.8-5.8Z" fill="currentColor"/>
            <path d="M18.3 13.4c.25 1.85 1.2 2.8 3.05 3.05-1.85.25-2.8 1.2-3.05 3.05-.25-1.85-1.2-2.8-3.05-3.05 1.85-.25 2.8-1.2 3.05-3.05ZM5.2 14.2c.2 1.35.9 2.05 2.25 2.25-1.35.2-2.05.9-2.25 2.25-.2-1.35-.9-2.05-2.25-2.25 1.35-.2 2.05-.9 2.25-2.25Z" fill="currentColor" opacity=".85"/>
        </svg>
    </button>

    <section id="farmaAiPanel" class="farma-ai-panel" aria-hidden="true" aria-label="Chat com o agente IA">
        <header class="farma-ai-header">
            <div class="farma-ai-avatar" aria-hidden="true">✦</div>
            <div class="farma-ai-heading">
                <strong>FarmaCerta IA</strong>
                <span><?php echo $identidade; ?> · Google Cloud</span>
            </div>
            <button id="farmaAiClose" class="farma-ai-close" type="button" aria-label="Fechar agente">×</button>
        </header>

        <div id="farmaAiMessages" class="farma-ai-messages" aria-live="polite">
            <div class="farma-ai-message assistant">
                <div class="farma-ai-bubble">Olá! Sou o agente da FarmaCerta. Posso ajudar com o sistema e analisar os dados que seu perfil tem permissão para ver.</div>
            </div>
        </div>

        <div class="farma-ai-suggestions" aria-label="Sugestões">
            <button type="button" class="farma-ai-chip" data-prompt="Faça um resumo de hoje.">Resumo de hoje</button>
            <button type="button" class="farma-ai-chip" data-prompt="Quais produtos estão com estoque baixo?">Estoque baixo</button>
            <button type="button" class="farma-ai-chip" data-prompt="Há lotes vencendo em breve?">Validade</button>
            <button type="button" class="farma-ai-chip" data-prompt="Explique o que posso fazer nesta tela.">Ajuda da tela</button>
        </div>

        <form id="farmaAiForm" class="farma-ai-form" autocomplete="off">
            <textarea id="farmaAiInput" class="farma-ai-input" rows="1" maxlength="1800"
                      placeholder="Pergunte algo sobre a FarmaCerta…" aria-label="Mensagem para o agente"></textarea>
            <button id="farmaAiSend" class="farma-ai-send" type="submit" aria-label="Enviar">➜</button>
        </form>
        <div class="farma-ai-note">O agente é somente leitura: ele não altera estoque, vendas ou cadastros.</div>
    </section>
    <script>window.FARMA_AI_ROOT = <?php echo json_encode($prefixo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
    <script src="<?php echo $prefixo; ?>assets/js/agente-ia.js"></script>
    <?php
}

function recursosRodape(): void
{
    $prefixo = json_encode(prefixoRelativo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    renderizarAgenteIa();

    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script>';
    echo 'if ("serviceWorker" in navigator) {';
    echo 'window.addEventListener("load", function () {';
    echo 'navigator.serviceWorker.register(' . $prefixo . ' + "service-worker.js").catch(function (erro) {';
    echo 'console.error("Erro ao registrar o PWA:", erro);';
    echo '});';
    echo '});';
    echo '}';
    echo '</script>';
}
