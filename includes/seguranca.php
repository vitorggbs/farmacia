<?php

function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('FARMACERTA_SESSION');
    session_set_cookie_params(array('lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax'));
    session_start();
}

function aplicarCabecalhosSeguranca(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if ($https) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

function tokenCsrf(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="_csrf" value="'.htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8').'">';
}

function validarCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    $recebido = (string) ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($recebido === '' || !hash_equals(tokenCsrf(), $recebido)) {
        http_response_code(403);
        exit('Requisição inválida ou expirada. Atualize a página e tente novamente.');
    }
}

iniciarSessaoSegura();
aplicarCabecalhosSeguranca();
