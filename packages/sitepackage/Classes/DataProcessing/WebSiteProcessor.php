<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class WebSiteProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $site = $cObj->getRequest()->getAttribute('site');
        $siteLanguage = $cObj->getRequest()->getAttribute('language');

        $baseUrl = rtrim((string)$site->getBase(), '/');

        // Generic language code from site config hreflang (e.g. "de", "en").
        // Intentionally not locale-specific (de-DE / en-US) since content is not regionally scoped.
        // All schemas using inLanguage must use this same source: $siteLanguage->getHreflang().
        $inLanguage = $siteLanguage->getHreflang();

        // Language-specific site title (falls back to site-level websiteTitle)
        $siteName = $siteLanguage->getWebsiteTitle() ?: ($site->getConfiguration()['websiteTitle'] ?? '');

        $description = (string)($processorConfiguration['websiteDescription'] ?? '');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $baseUrl . '/#website',
            'url' => $baseUrl . '/',
            'name' => $siteName,
            'inLanguage' => $inLanguage,
            'publisher' => [
                '@id' => $baseUrl . '/ueber-mich#person',
            ],
        ];

        if ($description !== '') {
            $schema['description'] = $description;
        }

        $processedData['websiteSchemaJson'] = (string)json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return $processedData;
    }
}
