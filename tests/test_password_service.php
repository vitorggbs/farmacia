<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/seguranca.php';

function assertCustom(bool $condicao, string $descricao): void
{
    if (!$condicao) {
        echo "[\033[31mFALHA\033[0m] $descricao\n";
        exit(1);
    }
    echo "[\033[32mSUCESSO\033[0m] $descricao\n";
}

echo "=== INICIANDO TESTES DO PASSWORD SERVICE (BCRYPT) ===\n\n";

// 1. Geração de hash bcrypt
$senhaOriginal = 'Senha@Forte2026';
$hash = PasswordService::hash($senhaOriginal);

assertCustom(is_string($hash), 'Hash gerado deve ser uma string');
assertCustom(str_starts_with($hash, '$2y$12$'), 'Hash deve iniciar com prefixo bcrypt e custo 12 ($2y$12$)');
assertCustom(strlen($hash) === 60, 'Hash bcrypt deve possuir exatamente 60 caracteres');

// 2. Verificação com senha correta
assertCustom(PasswordService::verify($senhaOriginal, $hash), 'Verificação com a senha correta deve retornar true');

// 3. Verificação com senha incorreta
assertCustom(!PasswordService::verify('SenhaErrada123', $hash), 'Verificação com senha incorreta deve retornar false');

// 4. Validação de senha vazia
assertCustom(!PasswordService::verify('', $hash), 'Verificação com senha vazia deve retornar false');
assertCustom(!PasswordService::verify($senhaOriginal, ''), 'Verificação com hash vazio deve retornar false');

// 5. Validação de tamanho mínimo
$excecaoMinimo = false;
try {
    PasswordService::hash('12345');
} catch (InvalidArgumentException $e) {
    $excecaoMinimo = true;
}
assertCustom($excecaoMinimo, 'Senha com menos de 6 caracteres deve lançar InvalidArgumentException');

// 6. Validação de tamanho máximo (limite de 72 bytes do bcrypt)
$excecaoMaximo = false;
try {
    PasswordService::hash(str_repeat('a', 73));
} catch (InvalidArgumentException $e) {
    $excecaoMaximo = true;
}
assertCustom($excecaoMaximo, 'Senha com mais de 72 caracteres deve lançar InvalidArgumentException');

// 7. Teste de verificação e migração de senha legada (texto puro)
$senhaLegada = '123456';
assertCustom(PasswordService::verify('123456', $senhaLegada), 'Compatibilidade com senha legada em texto plano deve retornar true');
assertCustom(!PasswordService::verify('senhaIncorreta', $senhaLegada), 'Senha legada incorreta deve retornar false');
assertCustom(PasswordService::needsRehash($senhaLegada), 'Senha legada deve indicar necessidade de rehash');

// 8. Teste de hash atualizado não necessitando de rehash
assertCustom(!PasswordService::needsRehash($hash), 'Hash com custo atual (12) não deve exigir rehash');

// 9. Teste de hash antigo com custo inferior exigindo rehash
$hashCustoAntigo = password_hash($senhaOriginal, PASSWORD_BCRYPT, ['cost' => 10]);
assertCustom(PasswordService::needsRehash($hashCustoAntigo), 'Hash com custo antigo (10) deve indicar necessidade de rehash para 12');

// 10. Funções globais de conveniência
assertCustom(hashSenha('minhasenha123') !== '', 'Função hashSenha() deve funcionar');
assertCustom(verificarSenha('minhasenha123', hashSenha('minhasenha123')), 'Função verificarSenha() deve funcionar');

echo "\nTODOS OS TESTES PASSARAM COM ÊXITO!\n";
