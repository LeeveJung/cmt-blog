<?php

defined('TYPO3') or die('Access denied.');

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// -------------------------------------------------------
// Container: Bild & Text (Grid)
// colPos 200 = Bild-Spalte, colPos 201 = Text-Spalte
// -------------------------------------------------------
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (new ContainerConfiguration(
        'brauer-image-text-grid',
        'Bild & Text (Grid)',
        'Bild und Text nebeneinander – Breite über Dropdown wählbar (70/30, 30/70, 50/50).',
        [
            [
                ['name' => 'Bild', 'colPos' => 200],
                ['name' => 'Text', 'colPos' => 201],
            ],
        ]
    ))->setIcon('EXT:container/Resources/Public/Icons/container-2col.svg')
);

// Layout-Dropdown: nutzt das bestehende tt_content.layout-Feld (kein neues DB-Feld nötig).
// columnsOverrides schränkt die Items auf diesen CType ein.
$GLOBALS['TCA']['tt_content']['types']['brauer-image-text-grid']['columnsOverrides']['layout'] = [
    'label' => 'Spaltenaufteilung',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'default' => 0,
        'items' => [
            ['label' => '70% Bild / 30% Text', 'value' => 0],
            ['label' => '30% Bild / 70% Text', 'value' => 1],
            ['label' => '50% Bild / 50% Text', 'value' => 2],
        ],
    ],
];

// -------------------------------------------------------
// Globales Feld: Ecken abrunden (rounded-md)
// Wird in die palette "mediaAdjustments" gehängt, die von
// textmedia, image und textpic genutzt wird.
// -------------------------------------------------------
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_sitepackage_rounded_image' => [
        'label' => 'Ecken abrunden',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
    'tx_sitepackage_vertical_align' => [
        'label' => 'Vertikale Ausrichtung',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => 'items-center',
            'items' => [
                ['label' => 'Oben', 'value' => 'items-start'],
                ['label' => 'Mitte', 'value' => 'items-center'],
                ['label' => 'Unten', 'value' => 'items-end'],
            ],
        ],
    ],
    'tx_sitepackage_show_divider' => [
        'label' => 'Trennlinie anzeigen',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
    'tx_sitepackage_no_padding' => [
        'label' => 'Vertikalen Abstand entfernen (py-12)',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);

$GLOBALS['TCA']['tt_content']['palettes']['mediaAdjustments']['showitem'] .= ',tx_sitepackage_rounded_image';

// layout-Feld in die Backend-Maske des Containers aufnehmen.
// b13/container setzt showitem automatisch; wir ergänzen das layout-Feld davor.
$GLOBALS['TCA']['tt_content']['types']['brauer-image-text-grid']['showitem'] =
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,' .
    'header,' .
    'layout,' .
    'tx_sitepackage_vertical_align,' .
    'tx_sitepackage_no_padding,' .
    'tx_sitepackage_show_divider,' .
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,' .
    'hidden,sys_language_uid,l18n_parent,colPos,tx_container_parent';
