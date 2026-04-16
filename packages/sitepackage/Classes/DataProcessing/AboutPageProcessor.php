<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class AboutPageProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $site = $cObj->getRequest()->getAttribute('site');
        $settings = $site->getSettings();
        $baseUrl = rtrim((string)$site->getBase(), '/');

        $pageRecord = $cObj->data;
        $pageUrl = $baseUrl . '/' . ltrim($pageRecord['slug'] ?? 'ueber-mich', '/');

        $knowsAboutRaw = $settings->get('author.knowsAbout', '');
        $knowsAbout = array_values(array_filter(array_map('trim', explode(',', (string)$knowsAboutRaw))));

        $instagramUrl = (string)$settings->get('author.instagramUrl', '');
        $sameAs = array_values(array_filter([$instagramUrl]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $pageUrl . '#person',
            'name' => (string)$settings->get('author.name', ''),
            'url' => $pageUrl,
            'description' => (string)$settings->get('author.description', ''),
            'sameAs' => $sameAs,
            'knowsAbout' => $knowsAbout,
            // TODO: add 'image' once portrait photo FAL UID is known.
            // 'image' => [
            //     '@type' => 'ImageObject',
            //     'url'   => $baseUrl . '/path/to/portrait.jpg',
            // ],
        ];

        $processedData['personSchemaJson'] = (string)json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return $processedData;
    }
}
