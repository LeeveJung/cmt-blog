<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class BlogPageProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LinkService $linkService,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $pageRecord = $cObj->data;

        $locale = (string)($cObj->getRequest()->getAttribute('language')?->getLocale() ?? 'de_DE');
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);

        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $publishDate = $pageRecord['brauer_publish_date'] ?? '';
        if (!empty($publishDate) && $publishDate !== '0000-00-00') {
            $dateObj = new \DateTimeImmutable($publishDate, $berlinTz);
        } else {
            $dateObj = (new \DateTimeImmutable('@' . (int)$pageRecord['crdate']))->setTimezone($berlinTz);
        }
        $modifiedDate = (new \DateTimeImmutable('@' . (int)($pageRecord['tstamp'] ?? 0)))->setTimezone($berlinTz);
        $authorName = (string)($processorConfiguration['authorName'] ?? '');

        $processedData['blogPublishDate'] = $dateObj;
        $processedData['blogPublishDateFormatted'] = $formatter->format($dateObj);
        $processedData['blogAuthorName'] = $authorName;
        $processedData['blogCategories'] = $this->fetchCategories((int)$pageRecord['uid']);
        $langUid = $cObj->getRequest()->getAttribute('language')?->getLanguageId() ?? 0;
        $processedData['blogTocItems'] = $this->fetchTocItems((int)$pageRecord['uid'], $langUid);
        $processedData['blogSchemaJson'] = $this->buildSchemaJson(
            $cObj->getRequest()->getAttribute('site'),
            $cObj->getRequest()->getAttribute('language'),
            $pageRecord,
            $dateObj,
            $modifiedDate,
            $authorName,
            $processedData['blogTeaserImage'][0] ?? null,
        );

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->setMetaTag('property', 'article:published_time', $dateObj->format('c'));
        $pageRenderer->setMetaTag('property', 'article:modified_time', $modifiedDate->format('c'));
        $pageRenderer->setMetaTag('property', 'article:author', $authorName);

        return $processedData;
    }

    private function buildSchemaJson(
        \TYPO3\CMS\Core\Site\Entity\Site $site,
        \TYPO3\CMS\Core\Site\Entity\SiteLanguage $siteLanguage,
        array $pageRecord,
        \DateTimeImmutable $publishDate,
        \DateTimeImmutable $modifiedDate,
        string $authorName,
        mixed $teaserImage,
    ): string {
        $baseUrl = rtrim((string)$site->getBase(), '/');
        $langBase = rtrim((string)$siteLanguage->getBase(), '/');
        $articleUrl = $langBase . '/' . ltrim($pageRecord['slug'] ?? '', '/');
        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $personUrl = $baseUrl . '/ueber-mich';

        $person = [
            '@type' => 'Person',
            '@id' => $personUrl . '#person',
            'name' => $authorName,
            'url' => $personUrl,
        ];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $articleUrl . '#article',
            'headline' => $pageRecord['title'] ?? '',
            'mainEntityOfPage' => $articleUrl,
            'inLanguage' => $siteLanguage->getHreflang(),
            'datePublished' => $publishDate->setTimezone($berlinTz)->format('c'),
            'dateModified' => $modifiedDate->format('c'),
            'author' => $person,
            'publisher' => $person,
            'isPartOf' => [
                '@id' => $baseUrl . '/#website',
            ],
        ];

        if (!empty($pageRecord['description'])) {
            $schema['description'] = $pageRecord['description'];
        }

        if ($teaserImage !== null) {
            $imageObject = [
                '@type' => 'ImageObject',
                'url' => $baseUrl . '/' . ltrim($teaserImage->getPublicUrl(), '/'),
            ];
            $width = (int)$teaserImage->getProperty('width');
            $height = (int)$teaserImage->getProperty('height');
            if ($width > 0) {
                $imageObject['width'] = $width;
            }
            if ($height > 0) {
                $imageObject['height'] = $height;
            }
            $schema['image'] = $imageObject;
        }

        if (!empty($pageRecord['keywords'])) {
            $schema['keywords'] = $pageRecord['keywords'];
        }

        return (string)json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function fetchTocItems(int $pageUid, int $langUid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('brauer_toc_items');

        $items = $qb
            ->select('label', 'link')
            ->from('brauer_toc_items')
            ->where(
                $qb->expr()->eq('foreign_table_parent_uid', $qb->createNamedParameter($pageUid, Connection::PARAM_INT)),
                $qb->expr()->in('sys_language_uid', $qb->createNamedParameter([-1, $langUid], Connection::PARAM_INT_ARRAY)),
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        // Fallback to default language if no items exist for the requested language
        if (empty($items) && $langUid > 0) {
            $qb = $this->connectionPool->getQueryBuilderForTable('brauer_toc_items');
            $items = $qb
                ->select('label', 'link')
                ->from('brauer_toc_items')
                ->where(
                    $qb->expr()->eq('foreign_table_parent_uid', $qb->createNamedParameter($pageUid, Connection::PARAM_INT)),
                    $qb->expr()->in('sys_language_uid', $qb->createNamedParameter([-1, 0], Connection::PARAM_INT_ARRAY)),
                )
                ->orderBy('sorting')
                ->executeQuery()
                ->fetchAllAssociative();
        }

        foreach ($items as &$item) {
            if (empty(trim($item['label'] ?? ''))) {
                $item['label'] = $this->resolveLabelFromLink($item['link'] ?? '', $langUid);
            }
        }
        unset($item);

        return $items;
    }

    private function resolveLabelFromLink(string $link, int $langUid): string
    {
        if (empty($link)) {
            return '';
        }

        $ceUid = $this->extractContentElementUid($link);
        if ($ceUid <= 0) {
            return '';
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');

        // For non-default languages: try to find the translated record first
        if ($langUid > 0) {
            $translated = $qb
                ->select('header')
                ->from('tt_content')
                ->where(
                    $qb->expr()->eq('l18n_parent', $qb->createNamedParameter($ceUid, Connection::PARAM_INT)),
                    $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter($langUid, Connection::PARAM_INT)),
                )
                ->executeQuery()
                ->fetchAssociative();

            if (!empty($translated['header'])) {
                return (string)$translated['header'];
            }

            $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        }

        $row = $qb
            ->select('header')
            ->from('tt_content')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($ceUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return (string)($row['header'] ?? '');
    }

    private function extractContentElementUid(string $link): int
    {
        // Extract fragment after #, with or without leading 'c' (e.g. #35 or #c35)
        if (preg_match('/#c?(\d+)$/i', $link, $m)) {
            return (int)$m[1];
        }

        // Fallback: try LinkService
        try {
            $linkData = $this->linkService->resolve($link);
            $fragment = $linkData['fragment'] ?? '';
            if (preg_match('/^c?(\d+)$/i', $fragment, $m)) {
                return (int)$m[1];
            }
        } catch (\Throwable) {
        }

        return 0;
    }

    private function fetchCategories(int $pageUid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');

        return $qb
            ->select('cat.uid', 'cat.title')
            ->from('sys_category_record_mm', 'mm')
            ->join('mm', 'sys_category', 'cat', $qb->expr()->eq('cat.uid', 'mm.uid_local'))
            ->where(
                $qb->expr()->eq('mm.uid_foreign', $qb->createNamedParameter($pageUid, Connection::PARAM_INT)),
                $qb->expr()->eq('mm.tablenames', $qb->createNamedParameter('pages')),
                $qb->expr()->eq('mm.fieldname', $qb->createNamedParameter('categories')),
            )
            ->orderBy('mm.sorting_foreign')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
