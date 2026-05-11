<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Backend\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;

abstract class AbstractApiController
{
    protected function success(array $data = []): ResponseInterface
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ]);
    }

    protected function error(string $message, int $statusCode = 400): ResponseInterface
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'message' => $message,
            ],
        ], $statusCode);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJsonBody(ServerRequestInterface $request): array
    {
        $raw = (string)$request->getBody();
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function resolveBackendUserId(): int
    {
        $beUser = $GLOBALS['BE_USER'] ?? null;
        if (!$beUser instanceof BackendUserAuthentication) {
            return 0;
        }
        return (int)($beUser->user['uid'] ?? 0);
    }
}
