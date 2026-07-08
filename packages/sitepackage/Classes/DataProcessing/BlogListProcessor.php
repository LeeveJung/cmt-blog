<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataProcessing;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class BlogListProcessor implements DataProcessorInterface
{
    // doktype of the blog-page ContentBlock (typeName in config.yaml)
    private const BLOG_PAGE_DOKTYPE = 1775660402;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FileRepository $fileRepository,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $maxItems = (int)($cObj->data['brauer_max_items'] ?? 0);

        $language = $cObj->getRequest()->getAttribute('language');
        $languageId = $language?->getLanguageId() ?? 0;
        $locale = (string)($language?->getLocale() ?? 'de_DE');

        $qb = $this->connectionPool->getQueryBuilderForTable('pages');
        $qb->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        if ($languageId > 0) {
            // join the default-language page to its translation so the limit is
            // applied only to posts that actually have a translation, otherwise
            // an untranslated post among the top N would shrink the result below maxItems
            $qb->select('d.uid', 'o.title', 'o.description', 'd.brauer_publish_date', 'd.crdate', 'd.slug')
                ->from('pages', 'd')
                ->join(
                    'd',
                    'pages',
                    'o',
                    (string)$qb->expr()->and(
                        $qb->expr()->eq('o.l10n_parent', $qb->quoteIdentifier('d.uid')),
                        $qb->expr()->eq('o.sys_language_uid', $qb->createNamedParameter($languageId, Connection::PARAM_INT)),
                    )
                )
                ->where(
                    $qb->expr()->eq('d.doktype', $qb->createNamedParameter(self::BLOG_PAGE_DOKTYPE, Connection::PARAM_INT)),
                    $qb->expr()->eq('d.sys_language_uid', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->orderBy('d.brauer_publish_date', 'DESC')
                ->addOrderBy('d.crdate', 'DESC');
        } else {
            $qb->select('uid', 'title', 'description', 'brauer_publish_date', 'crdate', 'slug')
                ->from('pages')
                ->where(
                    $qb->expr()->eq('doktype', $qb->createNamedParameter(self::BLOG_PAGE_DOKTYPE, Connection::PARAM_INT)),
                    $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->orderBy('brauer_publish_date', 'DESC')
                ->addOrderBy('crdate', 'DESC');
        }

        if ($maxItems > 0) {
            $qb->setMaxResults($maxItems);
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();

        foreach ($rows as &$row) {
            $files = $this->fileRepository->findByRelation('pages', 'brauer_teaser_image', $row['uid']);
            $row['teaser_image'] = $files[0] ?? null;

            $publishDate = $row['brauer_publish_date'] ?? '';
            if (!empty($publishDate) && $publishDate !== '0000-00-00') {
                $dateObj = new \DateTimeImmutable($publishDate);
            } else {
                $dateObj = (new \DateTimeImmutable())->setTimestamp((int)$row['crdate']);
            }
            $row['display_date'] = $dateObj;
            $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
            $row['display_date_formatted'] = $formatter->format($dateObj);

            $row['categories'] = $this->fetchCategories($row['uid'], $languageId);
        }
        unset($row);

        $processedData['blogPosts'] = $rows;

        return $processedData;
    }

    private function fetchCategories(int $pageUid, int $langUid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');

        return $qb
            ->select('cat.uid')
            ->addSelectLiteral('COALESCE(tr.title, cat.title) AS title')
            ->from('sys_category_record_mm', 'mm')
            ->join('mm', 'sys_category', 'cat', $qb->expr()->eq('cat.uid', 'mm.uid_local'))
            ->leftJoin('cat', 'sys_category', 'tr', $qb->expr()->and(
                $qb->expr()->eq('tr.l10n_parent', 'cat.uid'),
                $qb->expr()->eq('tr.sys_language_uid', $qb->createNamedParameter($langUid, Connection::PARAM_INT)),
            ))
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
