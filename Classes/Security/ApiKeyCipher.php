<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Security;

final class ApiKeyCipher
{
    private const VERSION_PREFIX = 'v1:';

    public function encrypt(string $plainText): string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return '';
        }

        $key = $this->deriveKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, $key);

        return self::VERSION_PREFIX . base64_encode($nonce . $cipherText);
    }

    public function decrypt(string $encoded): string
    {
        $encoded = trim($encoded);
        if ($encoded === '') {
            return '';
        }
        if (!str_starts_with($encoded, self::VERSION_PREFIX)) {
            return '';
        }

        $rawPayload = base64_decode(substr($encoded, strlen(self::VERSION_PREFIX)), true);
        if (!is_string($rawPayload) || $rawPayload === '') {
            return '';
        }

        $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($rawPayload) <= $nonceLength) {
            return '';
        }

        $nonce = substr($rawPayload, 0, $nonceLength);
        $cipherText = substr($rawPayload, $nonceLength);
        $plainText = sodium_crypto_secretbox_open($cipherText, $nonce, $this->deriveKey());

        return is_string($plainText) ? $plainText : '';
    }

    private function deriveKey(): string
    {
        $baseKey = (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '');
        if ($baseKey === '') {
            throw new \RuntimeException('TYPO3 encryption key is missing.');
        }

        return hash('sha256', $baseKey, true);
    }
}
