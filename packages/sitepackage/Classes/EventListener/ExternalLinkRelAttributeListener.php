<?php

declare(strict_types=1);

namespace Brauer\Sitepackage\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

/**
 * Ensures all links opening in a new tab carry rel="noopener noreferrer" for
 * security/privacy, merging with any rel values already set (e.g. nofollow
 * from the CKEditor decorator). Also normalises the non-standard target="_new".
 */
#[AsEventListener]
final class ExternalLinkRelAttributeListener
{
    public function __invoke(AfterLinkIsGeneratedEvent $event): void
    {
        $linkResult = $event->getLinkResult();
        $target = $linkResult->getTarget();

        if (!in_array($target, ['_blank', '_new'], true)) {
            return;
        }

        if ($target === '_new') {
            $linkResult = $linkResult->withTarget('_blank');
        }

        $existing = $linkResult->getAttribute('rel') ?? '';
        $relValues = array_filter(explode(' ', $existing));

        foreach (['noopener', 'noreferrer'] as $token) {
            if (!in_array($token, $relValues, true)) {
                $relValues[] = $token;
            }
        }

        $linkResult = $linkResult->withAttribute('rel', implode(' ', $relValues));
        $event->setLinkResult($linkResult);
    }
}
