<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class FaqSchemaViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('items', 'array', 'FAQ items', true);
    }

    public function render(): string
    {
        $items = $this->arguments['items'] ?? [];
        if (empty($items)) {
            return '';
        }

        $mainEntity = [];
        foreach ($items as $item) {
            $question = trim(strip_tags((string)($item->get('question') ?? '')));
            $answer = trim(strip_tags((string)($item->get('answer') ?? '')));
            if ($question === '' || $answer === '') {
                continue;
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if (empty($mainEntity)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return (string)json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
