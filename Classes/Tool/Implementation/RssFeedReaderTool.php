<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Tool\Implementation;

use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;
use Kohlercode\Agents\Utilities\RssFeedReader;
use RuntimeException;

final class RssFeedReaderTool implements ToolInterface, ToolMetadataInterface
{
    public function getSourceExtensionKey(): string
    {
        return 'agents';
    }

    public function getName(): string
    {
        return 'rss_feed_reader';
    }

    public function getDescription(): string
    {
        return 'Reads an RSS feed from a given URL and returns the items.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string', 
                    'format' => 'uri',
                    'description' => 'The URL of the RSS feed to read.',
                ],
            ],
            'required' => ['url'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    {
        $url = $arguments['url'] ?? null;
        if (!is_string($url) || trim($url) === '') {
            throw new \InvalidArgumentException('URL is required.');
        }

        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('URL must be a valid URL.');
        }

        $reader = new RssFeedReader();
        try {
            // Fetch feed, limit to the latest 5 items
            $feedData = $reader->fetch($url, 5);
            
            return [
                'message' => 'RSS feed from ' . $url . ' read successfully.',
                'data' => $feedData,
            ];
        } catch (RuntimeException $e) {
            throw new \RuntimeException('Failed to read RSS feed: ' . $e->getMessage());
        }
    }
}