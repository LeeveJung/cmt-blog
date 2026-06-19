<?php

defined('TYPO3') or die('Access denied.');

// Add default RTE configuration
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['sitepackage'] = 'EXT:sitepackage/Configuration/RTE/Default.yaml';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['yoast_seo']['allowedDoktypes']['blog'] = 1775660402;

// Auto-fill TOC item label from linked content element header
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = \Brauer\Sitepackage\DataHandler\TocItemLabelEnricher::class;
