<?php

declare(strict_types=1);

/**
 * FarmaCerta - Módulo de Segurança e Criptografia de Senhas
 *
 * Implementa rotinas de hashing seguro utilizando o algoritmo bcrypt via password_hash(),
 * seguindo as diretrizes OWASP e os princípios de Secure by Design e Fail Securely.
 */

final class PasswordService
{
    /**
     * Fator de custo padrão para o algoritmo bcrypt.
     * Custo 12 oferece alta resistência contra ataques de força bruta
     * e dicionário, mantendo tempo de resposta ágil para a aplicação.
     */
    public const DEFAULT_COST = 12;

    /**
     * Tamanho mínimo permitido para senhas.
     */
    public const MIN_PASSWORD_LENGTH = 6;

    /**
     * Limite de tamanho imposto pela especificação do algoritmo bcrypt (72 bytes).
     */
    public const MAX_PASSWORD_LENGTH = 72;

    /**
     * Gera um hash bcrypt seguro com salt criptograficamente forte gerado automaticamente.
     *
     * @param string $senha Senha em texto plano.
     * @param int $cost Fator de custo de computação do bcrypt.
     * @return string Hash gerado no formato bcrypt ($2y$...).
     * @throws InvalidArgumentException Se a senha não atender aos requisitos de tamanho.
     * @throws RuntimeException Se houver falha interna na geração do hash.
     */
    public static function hash(string $senha, int $cost = self::DEFAULT_COST): string
    {
        $comprimento = strlen($senha);

        if ($comprimento < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('A senha deve conter no mínimo %d caracteres.', self::MIN_PASSWORD_LENGTH)
            );
        }

        if ($comprimento > self::MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('A senha não pode ultrapassar %d caracteres devido às restrições do algoritmo de segurança.', self::MAX_PASSWORD_LENGTH)
            );
        }

        $opcoes = [
            'cost' => $cost
        ];

        $hash = password_hash($senha, PASSWORD_BCRYPT, $opcoes);

        if (!is_string($hash) || empty($hash)) {
            throw new RuntimeException('Falha interna ao gerar o hash da senha.');
        }

        return $hash;
    }

    /**
     * Verifica com segurança em tempo constante se a senha corresponde ao hash armazenado.
     * Suporta compatibilidade retroativa e migração transparente de senhas legadas em texto plano.
     *
     * @param string $senha Senha informada no formulário de login.
     * @param string $hashArmazenado Hash (ou texto legado) armazenado no banco de dados.
     * @return bool True se a senha for válida, False caso contrário.
     */
    public static function verify(string $senha, string $hashArmazenado): bool
    {
        if ($senha === '' || $hashArmazenado === '') {
            return false;
        }

        $info = password_get_info($hashArmazenado);

        // Se o hash armazenado for um hash reconhecido pelo PHP (ex: bcrypt)
        if ($info['algo'] !== null && $info['algo'] !== 0) {
            return password_verify($senha, $hashArmazenado);
        }

        // Fallback seguro em tempo constante para senhas legadas em texto plano
        return hash_equals($hashArmazenado, $senha);
    }

    /**
     * Verifica se o hash precisa ser recalculado e atualizado no banco de dados.
     * Retorna true se a senha original estava em texto plano ou se o custo configurado mudou.
     *
     * @param string $hashArmazenado Hash armazenado atualmente no banco.
     * @param int $cost Custo alvo desejado.
     * @return bool True se for necessário gerar um novo hash e persistir no banco.
     */
    public static function needsRehash(string $hashArmazenado, int $cost = self::DEFAULT_COST): bool
    {
        $info = password_get_info($hashArmazenado);

        // Se era legado (texto plano)
        if ($info['algo'] === null || $info['algo'] === 0) {
            return true;
        }

        return password_needs_rehash($hashArmazenado, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}

// Funções utilitárias de conveniência global para compatibilidade com código existente

if (!function_exists('hashSenha')) {
    function hashSenha(string $senha, int $cost = PasswordService::DEFAULT_COST): string
    {
        return PasswordService::hash($senha, $cost);
    }
}

if (!function_exists('verificarSenha')) {
    function verificarSenha(string $senha, string $hash): bool
    {
        return PasswordService::verify($senha, $hash);
    }
}

if (!function_exists('senhaPrecisaRehash')) {
    function senhaPrecisaRehash(string $hash, int $cost = PasswordService::DEFAULT_COST): bool
    {
        return PasswordService::needsRehash($hash, $cost);
    }
}
