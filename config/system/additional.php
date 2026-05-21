<?php

/**
 * #ddev-generated: Automatically generated TYPO3 additional.php file.
 * ddev manages this file and may delete or overwrite the file unless this comment is removed.
 * It is recommended that you leave this file alone.
 */

if (\TYPO3\CMS\Core\Core\Environment::getContext()->isProduction()) {
    $GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
        $GLOBALS['TYPO3_CONF_VARS'],
        [
            'DB' => [
                'Connections' => [
                    'Default' => [
                        'host'     => getenv('DB_HOST'),
                        'dbname'   => getenv('DB_NAME'),
                        'user'     => getenv('DB_USER'),
                        'password' => getenv('DB_PASSWORD'),
                        'port'     => (int)(getenv('DB_PORT') ?: 3306),
                    ],
                ],
            ],
            'MAIL' => [
                'transport'              => getenv('MAIL_TRANSPORT') ?: 'sendmail',
                'transport_smtp_server'  => getenv('MAIL_SMTP_SERVER') ?: '',
                'transport_smtp_encrypt' => getenv('MAIL_SMTP_ENCRYPT') ?: '',
                'transport_smtp_username' => getenv('MAIL_SMTP_USER') ?: '',
                'transport_smtp_password' => getenv('MAIL_SMTP_PASSWORD') ?: '',
                'defaultMailFromAddress' => getenv('MAIL_FROM_ADDRESS') ?: '',
                'defaultMailFromName'    => getenv('MAIL_FROM_NAME') ?: '',
            ],
            'SYS' => [
                'trustedHostsPattern' => getenv('TYPO3_TRUSTED_HOSTS') ?: '.*',
            ],
        ]
    );
}

if (getenv('IS_DDEV_PROJECT') == 'true') {
    $GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
        $GLOBALS['TYPO3_CONF_VARS'],
        [
            'DB' => [
                'Connections' => [
                    'Default' => [
                        'dbname' => 'db',
                        'driver' => 'mysqli',
                        'host' => 'db',
                        'password' => 'db',
                        'port' => 3306,
                        'user' => 'db',
                    ],
                ],
            ],
            // This GFX configuration allows processing by installed ImageMagick 6
            'GFX' => [
                'processor' => 'ImageMagick',
                'processor_path' => '/usr/bin/',
                'processor_path_lzw' => '/usr/bin/',
            ],
            // This mail configuration sends all emails to mailpit
            'MAIL' => [
                'transport' => 'smtp',
                'transport_smtp_encrypt' => false,
                'transport_smtp_server' => 'localhost:1025',
            ],
            'SYS' => [
                'trustedHostsPattern' => '.*.*',
                'devIPmask' => '*',
                'displayErrors' => 1,
            ],
        ]
    );
}