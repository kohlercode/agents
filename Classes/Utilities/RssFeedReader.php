<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Utilities;

use RuntimeException;
use SimpleXMLElement;
use Exception;

class RssFeedReader
{
    private const DEFAULT_TIMEOUT = 10;
    // A standard browser User-Agent prevents blocks from strict servers (e.g., Cloudflare, ModSecurity)
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Fetch and parse an RSS feed into a clean array.
     *
     * @param string $url The RSS feed URL.
     * @param int $limit Maximum number of items to return. 0 for unlimited.
     * @return array<string, mixed>
     * @throws RuntimeException If the feed cannot be fetched or parsed.
     */
    public function fetch(string $url, int $limit = 0): array
    {
        $xmlString = $this->fetchXmlContent($url);
        
        // Suppress libxml errors to throw a clean custom Exception instead
        $useErrors = libxml_use_internal_errors(true);
        
        try {
            // LIBXML_NOCDATA automatically merges <![CDATA[ ... ]]> sections into standard text
            $xml = new SimpleXMLElement($xmlString, LIBXML_NOCDATA);
        } catch (Exception $e) {
            libxml_use_internal_errors($useErrors);
            throw new RuntimeException("Failed to parse RSS XML from {$url}: " . $e->getMessage());
        }

        if ($xml === false) {
            $error = libxml_get_last_error();
            $errorMessage = $error ? $error->message : 'Unknown XML parsing error.';
            libxml_clear_errors();
            libxml_use_internal_errors($useErrors);
            throw new RuntimeException("Invalid XML format in RSS feed from {$url}: {$errorMessage}");
        }

        libxml_use_internal_errors($useErrors);

        return $this->parseFeed($xml, $limit);
    }

    /**
     * Fetch the raw XML content using cURL.
     */
    private function fetchXmlContent(string $url): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => self::DEFAULT_TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_ENCODING => '', // Passing an empty string tells cURL to handle all supported encodings (gzip, deflate)
        ]);

        $content = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($content === false || $error) {
            throw new RuntimeException("cURL error while fetching RSS feed from {$url}: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException("HTTP error {$httpCode} while fetching RSS feed from {$url}");
        }

        return (string) $content;
    }

    /**
     * Parse the SimpleXMLElement into a structured array.
     */
    private function parseFeed(SimpleXMLElement $xml, int $limit): array
    {
        $channel = $this->findChannel($xml);
        $feed = [
            'channel' => [
                'title' => $channel !== null ? $this->getChildText($channel, 'title') : '',
                'link' => $channel !== null ? $this->getChildText($channel, 'link') : '',
                'description' => $channel !== null ? $this->getChildText($channel, 'description') : '',
                'image' => $channel !== null ? $this->getNestedChildText($channel, ['image', 'url']) : '', // Channel logo
            ],
            'items' => []
        ];

        $items = $this->findFeedItems($xml, $channel);
        if ($items === []) {
            return $feed;
        }

        $count = 0;
        foreach ($items as $item) {
            if ($limit > 0 && $count >= $limit) {
                break;
            }

            // 1. Extract HTML Content
            $contentHtml = $this->getContentEncoded($item);
            $contentText = $this->htmlToText($contentHtml);

            // 2. Extract Featured Image
            $imageUrl = $this->extractImage($item, $contentHtml);
            $pubDate = $this->getChildText($item, 'pubDate');

            $feed['items'][] = [
                'title' => $this->getChildText($item, 'title'),
                'link' => $this->getChildText($item, 'link'),
                'description' => $this->getChildText($item, 'description'),
                'content' => $contentText,
                'content_html' => trim($contentHtml),
                'image' => $imageUrl, // The intelligently extracted image
                'pubDate' => $pubDate,
                'timestamp' => strtotime($pubDate !== '' ? $pubDate : 'now'),
                'guid' => $this->getChildText($item, 'guid'),
            ];

            $count++;
        }

        return $feed;
    }

    private function findChannel(SimpleXMLElement $xml): ?SimpleXMLElement
    {
        $matches = $xml->xpath('/*[local-name()="rss"]/*[local-name()="channel"]');
        if (is_array($matches) && isset($matches[0])) {
            return $matches[0];
        }

        $matches = $xml->xpath('//*[local-name()="channel"]');
        if (is_array($matches) && isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @return array<int, SimpleXMLElement>
     */
    private function findFeedItems(SimpleXMLElement $xml, ?SimpleXMLElement $channel): array
    {
        if ($channel !== null) {
            $items = $channel->xpath('./*[local-name()="item"]');
            if (is_array($items) && $items !== []) {
                return $items;
            }
        }

        $items = $xml->xpath('//*[local-name()="item"]');
        return is_array($items) ? $items : [];
    }

    /**
     * @return array<int, SimpleXMLElement>
     */
    private function getChildElements(SimpleXMLElement $element, string $name): array
    {
        $matches = $element->xpath('./*[local-name()="' . $name . '"]');
        return is_array($matches) ? $matches : [];
    }

    private function getChildText(SimpleXMLElement $element, string $name): string
    {
        $matches = $this->getChildElements($element, $name);
        return isset($matches[0]) ? trim((string)$matches[0]) : '';
    }

    private function getContentEncoded(SimpleXMLElement $item): string
    {
        $content = $this->getNamespacedChildText(
            $item,
            'http://purl.org/rss/1.0/modules/content/',
            'encoded'
        );

        return $content !== '' ? $content : $this->getChildText($item, 'encoded');
    }

    private function getNamespacedChildText(SimpleXMLElement $element, string $namespace, string $name): string
    {
        $children = $element->children($namespace);
        if (!isset($children->{$name})) {
            return '';
        }

        return trim((string)$children->{$name});
    }

    private function htmlToText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $withLineBreaks = preg_replace(
            '/<(br|\/p|\/div|\/h[1-6]|\/li|\/blockquote)\b[^>]*>/i',
            "\n",
            $html
        );
        $text = html_entity_decode(strip_tags((string)$withLineBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string)$text);

        return trim((string)$text);
    }

    /**
     * @param array<int, string> $path
     */
    private function getNestedChildText(SimpleXMLElement $element, array $path): string
    {
        $current = $element;
        foreach ($path as $name) {
            $matches = $this->getChildElements($current, $name);
            if (!isset($matches[0])) {
                return '';
            }
            $current = $matches[0];
        }

        return trim((string)$current);
    }

    /**
     * Intelligently extract the best image for an RSS item.
     */
    private function extractImage(SimpleXMLElement $item, string $htmlContent): string
    {
        // Check 1: Yahoo Media Namespace (<media:content> or <media:thumbnail>)
        foreach ($this->getChildElements($item, 'content') as $media) {
            $attr = $media->attributes();
            // Allow explicit images or media without a type definition
            if (isset($attr['url']) && (!isset($attr['type']) || str_starts_with((string)$attr['type'], 'image/'))) {
                return (string)$attr['url'];
            }
        }

        foreach ($this->getChildElements($item, 'thumbnail') as $thumbnail) {
            $attr = $thumbnail->attributes();
            if (isset($attr['url'])) {
                return (string)$attr['url'];
            }
        }

        // Check 2: Standard RSS Enclosure (<enclosure type="image/jpeg">)
        foreach ($this->getChildElements($item, 'enclosure') as $enclosure) {
            $attr = $enclosure->attributes();
            if (isset($attr['url']) && isset($attr['type']) && str_starts_with((string)$attr['type'], 'image/')) {
                return (string)$attr['url'];
            }
        }

        // Check 3: Regex fallback (Extract first image tag from HTML content)
        $htmlToSearch = $htmlContent ?: $this->getChildText($item, 'description');
        if (!empty($htmlToSearch) && preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $htmlToSearch, $matches)) {
            return $matches[1];
        }

        return ''; // No image found
    }
}