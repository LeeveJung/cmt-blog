<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $siteLanguage = $cObj->getRequest()->getAttribute('language');
        $baseUrl = rtrim((string)$site->getBase(), '/');
        $langBase = rtrim((string)$siteLanguage->getBase(), '/');

        $pageRecord = $cObj->data;
        $pageUrl = $langBase . '/' . ltrim($pageRecord['slug'] ?? 'ueber-mich', '/');

        // Values come from TypoScript configuration (constants resolved from settings.yaml)
        $name = (string)($processorConfiguration['authorName'] ?? '');
        $description = (string)($processorConfiguration['authorDescription'] ?? '');
        $instagramUrl = (string)($processorConfiguration['authorInstagramUrl'] ?? '');
        $linkedInUrl = (string)($processorConfiguration['authorLinkedInUrl'] ?? '');
        $knowsAboutRaw = (string)($processorConfiguration['authorKnowsAbout'] ?? '');

        $cmtThing = [
            '@type' => 'Thing',
            'name' => 'Charcot-Marie-Tooth disease',
            'sameAs' => 'https://en.wikipedia.org/wiki/Charcot%E2%80%93Marie%E2%80%93Tooth_disease',
        ];
        $knowsAboutStrings = array_values(array_filter(array_map('trim', explode(',', $knowsAboutRaw))));
        $knowsAbout = [$cmtThing, ...$knowsAboutStrings];

        $sameAs = array_values(array_filter([$instagramUrl, $linkedInUrl]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $baseUrl . '/ueber-mich#person',
            'name' => $name,
            'url' => $pageUrl,
            'description' => $description,
            'sameAs' => $sameAs,
            'knowsAbout' => $knowsAbout,
        ];

        $imageAlt = (string)($processorConfiguration['authorImageAlt'] ?? '');
        $imageFalUid = (int)($processorConfiguration['authorImageFalUid'] ?? 0);
        $ogImageUrl = '';
        $ogImageWidth = 0;
        $ogImageHeight = 0;
        if ($imageFalUid > 0) {
            try {
                $file = $this->resourceFactory->getFileObject($imageFalUid);
                $ogImageUrl = $baseUrl . '/' . ltrim($file->getPublicUrl(), '/');
                $ogImageWidth = (int)$file->getProperty('width');
                $ogImageHeight = (int)$file->getProperty('height');
                $imageObject = ['@type' => 'ImageObject', 'url' => $ogImageUrl];
                if ($ogImageWidth > 0) {
                    $imageObject['width'] = $ogImageWidth;
                }
                if ($ogImageHeight > 0) {
                    $imageObject['height'] = $ogImageHeight;
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

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        if ($ogImageUrl !== '') {
            $subProps = ['alt' => $imageAlt];
            if ($ogImageWidth > 0) {
                $subProps['width'] = (string)$ogImageWidth;
            }
            if ($ogImageHeight > 0) {
                $subProps['height'] = (string)$ogImageHeight;
            }
            $pageRenderer->setMetaTag('property', 'og:image', $ogImageUrl, $subProps);
        }
        $pageRenderer->setMetaTag('property', 'profile:first_name', 'Christoph');
        $pageRenderer->setMetaTag('property', 'profile:last_name', 'Brauer');
        $pageRenderer->setMetaTag('property', 'profile:username', 'leben_mit_cmt');

        return $processedData;
    }
}
