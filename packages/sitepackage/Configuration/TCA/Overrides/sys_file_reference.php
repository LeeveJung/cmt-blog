<?php

defined('TYPO3') or die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// -------------------------------------------------------
// Pro-Bild steuerbare Browser-Hints: loading + fetchpriority.
// Gilt fuer jedes Bild-Overlay (imageoverlayPalette) im Backend.
// Leerer Wert = Standard (globale Einstellung greift).
// -------------------------------------------------------
ExtensionManagementUtility::addTCAcolumns('sys_file_reference', [
    'tx_sitepackage_loading' => [
        'label' => 'Ladeverhalten (loading)',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => '',
            'items' => [
                ['label' => 'Standard', 'value' => ''],
                ['label' => 'Lazy (verzögert)', 'value' => 'lazy'],
                ['label' => 'Eager (sofort)', 'value' => 'eager'],
            ],
        ],
    ],
    'tx_sitepackage_fetchpriority' => [
        'label' => 'Ladepriorität (fetchpriority)',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => '',
            'items' => [
                ['label' => 'Standard', 'value' => ''],
                ['label' => 'Hoch', 'value' => 'high'],
                ['label' => 'Niedrig', 'value' => 'low'],
                ['label' => 'Auto', 'value' => 'auto'],
            ],
        ],
    ],
]);

// imageoverlayPalette: Core-/FSC-Bilder (z. B. textmedia).
// basicoverlayPalette: von Content-Blocks-File-Feldern fuer Bilder genutzt (z. B. Teaser).
foreach (['imageoverlayPalette', 'basicoverlayPalette'] as $palette) {
    ExtensionManagementUtility::addFieldsToPalette(
        'sys_file_reference',
        $palette,
        'tx_sitepackage_loading, tx_sitepackage_fetchpriority',
        'after:description'
    );
}