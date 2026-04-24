<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\File;

/**
 * Adjusts the icon for resources with mime type "audio/podigee".
 */
final class ModifyIconForResourcePropertiesListener
{
    #[AsEventListener(identifier: 'podigee/modify-icon-for-resource')]
    public function __invoke(ModifyIconForResourcePropertiesEvent $event): void
    {
        $resource = $event->getResource();

        if (!$resource instanceof File) {
            return;
        }

        if ($resource->getMimeType() === 'audio/podigee') {
            $event->setIconIdentifier('mimetypes-media-image-podigee');
        }
    }
}
