<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\DataHandler;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\LinkHandling\LinkService;

final class TocItemLabelEnricher
{
    public function __construct(
        private readonly LinkService $linkService,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function processDatamap_preProcessFieldArray(
        array &$fieldArray,
        string $table,
        int|string $id,
        DataHandler $dataHandler,
    ): void {
        if ($table !== 'brauer_toc_items') {
            return;
        }

        // Only auto-fill if label is empty or not submitted
        $label = trim($fieldArray['label'] ?? '');
        if ($label !== '') {
            return;
        }

        // For updates: if label wasn't submitted, check the existing DB value
        $link = $fieldArray['link'] ?? '';
        $langUid = (int)($fieldArray['sys_language_uid'] ?? 0);
        if (!array_key_exists('label', $fieldArray) || !array_key_exists('sys_language_uid', $fieldArray)) {
            $existing = $this->connectionPool->getQueryBuilderForTable('brauer_toc_items')
                ->select('label', 'link', 'sys_language_uid')
                ->from('brauer_toc_items')
                ->where($this->connectionPool->getQueryBuilderForTable('brauer_toc_items')->expr()->eq('uid', (int)$id))
                ->executeQuery()
                ->fetchAssociative();

            if (!array_key_exists('label', $fieldArray)) {
                if (!empty(trim((string)($existing['label'] ?? '')))) {
                    return; // existing label is fine, don't overwrite
                }
                if (empty($link)) {
                    $link = $existing['link'] ?? '';
                }
            }
            if (!array_key_exists('sys_language_uid', $fieldArray)) {
                $langUid = (int)($existing['sys_language_uid'] ?? 0);
            }
        }

        if (empty($link)) {
            return;
        }

        $header = $this->resolveHeaderFromLink($link, $langUid);
        if ($header !== '') {
            $fieldArray['label'] = $header;
        }
    }

    private function resolveHeaderFromLink(string $link, int $langUid): string
    {
        try {
            $linkData = $this->linkService->resolve($link);
        } catch (\Exception) {
            return '';
        }

        // Content element links are stored as t3://page?uid=X#cY
        $fragment = $linkData['fragment'] ?? '';
        if (!str_starts_with($fragment, 'c')) {
            return '';
        }

        $ceUid = (int)substr($fragment, 1);
        if ($ceUid <= 0) {
            return '';
        }

        // For non-default languages: try to find the translated record first
        if ($langUid > 0) {
            $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $translated = $qb
                ->select('header')
                ->from('tt_content')
                ->where(
                    $qb->expr()->eq('l18n_parent', $qb->createNamedParameter($ceUid)),
                    $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter($langUid)),
                )
                ->executeQuery()
                ->fetchAssociative();

            if (!empty($translated['header'])) {
                return (string)$translated['header'];
            }
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $row = $qb
            ->select('header')
            ->from('tt_content')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($ceUid)))
            ->executeQuery()
            ->fetchAssociative();

        return (string)($row['header'] ?? '');
    }
}
