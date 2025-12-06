<?php

use Ayacoo\Podigee\Helper\PodigeeHelper;
use Ayacoo\Podigee\Rendering\PodigeeRenderer;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(function ($mediaFileExt) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'][$mediaFileExt] = PodigeeHelper::class;

    $rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(RendererRegistry::class);
    $rendererRegistry->registerRendererClass(PodigeeRenderer::class);

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'][$mediaFileExt] = 'audio/' . $mediaFileExt;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',' . $mediaFileExt;

    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerFileExtension($mediaFileExt, 'mimetypes-media-image-' . $mediaFileExt);
})('podigee');
