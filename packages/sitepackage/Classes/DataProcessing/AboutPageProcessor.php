<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class AboutPageProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $site = $cObj->getRequest()->getAttribute('site');
        $baseUrl = rtrim((string)$site->getBase(), '/');

        $pageRecord = $cObj->data;
        $pageUrl = $baseUrl . '/' . ltrim($pageRecord['slug'] ?? 'ueber-mich', '/');

        // Values come from TypoScript configuration (constants resolved from settings.yaml)
        $name = (string)($processorConfiguration['authorName'] ?? '');
        $description = (string)($processorConfiguration['authorDescription'] ?? '');
        $instagramUrl = (string)($processorConfiguration['authorInstagramUrl'] ?? '');
        $knowsAboutRaw = (string)($processorConfiguration['authorKnowsAbout'] ?? '');

        $knowsAbout = array_values(array_filter(array_map('trim', explode(',', $knowsAboutRaw))));
        $sameAs = array_values(array_filter([$instagramUrl]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $pageUrl . '#person',
            'name' => $name,
            'url' => $pageUrl,
            'description' => $description,
            'sameAs' => $sameAs,
            'knowsAbout' => $knowsAbout,
        ];

        $imageFalUid = (int)($processorConfiguration['authorImageFalUid'] ?? 0);
        if ($imageFalUid > 0) {
            try {
                $file = $this->resourceFactory->getFileObject($imageFalUid);
                $imageObject = [
                    '@type' => 'ImageObject',
                    'url' => $baseUrl . '/' . ltrim($file->getPublicUrl(), '/'),
                ];
                $width = (int)$file->getProperty('width');
                $height = (int)$file->getProperty('height');
                if ($width > 0) {
                    $imageObject['width'] = $width;
                }
                if ($height > 0) {
                    $imageObject['height'] = $height;
                }
                $schema['image'] = $imageObject;
            } catch (\Throwable) {
                // File not found – schema remains without image rather than breaking the page
            }
        }

        $processedData['personSchemaJson'] = (string)json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        return $processedData;
    }
}
