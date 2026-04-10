<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied.');

// Allow teaser image to be synchronized from default language on translation
$GLOBALS['TCA']['pages']['columns']['brauer_teaser_image']['config']['behaviour']['allowLanguageSynchronization'] = true;
