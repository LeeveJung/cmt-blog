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

        $locale = (string)($cObj->getRequest()->getAttribute('language')?->getLocale() ?? 'de_DE');

        $qb = $this->connectionPool->getQueryBuilderForTable('pages');
        $qb->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        $qb->select('uid', 'title', 'description', 'brauer_publish_date', 'crdate', 'slug')
            ->from('pages')
            ->where(
                $qb->expr()->eq('doktype', $qb->createNamedParameter(self::BLOG_PAGE_DOKTYPE, Connection::PARAM_INT)),
                $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('brauer_publish_date', 'DESC')
            ->addOrderBy('crdate', 'DESC');

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

            $row['categories'] = $this->fetchCategories($row['uid']);
        }
        unset($row);

        $processedData['blogPosts'] = $rows;

        return $processedData;
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
