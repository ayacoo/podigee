<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Rendering;

use Ayacoo\Podigee\Event\ModifyPodigeeOutputEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Podigee renderer class
 */
class PodigeeRenderer implements FileRendererInterface
{
    /**
     * @var OnlineMediaHelperInterface|false
     */
    protected $onlineMediaHelper;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConfigurationManager $configurationManager
    ) {
    }

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     * For example create a video renderer for a certain storage/driver type.
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File of FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file)
    {
        return ($file->getMimeType() === 'audio/podigee' || $file->getExtension() === 'podigee') &&
            $this->getOnlineMediaHelper($file) !== false;
    }

    public function render(FileInterface $file, $width, $height, array $options = [])
    {
        $videoId = $this->getVideoIdFromFile($file);

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('podigee');
        if (($extConf['display'] ?? '') === 'iframe') {
            $output = $this->renderIframe($videoId);
            if ($this->getPrivacySetting()) {
                $output = str_replace('src', 'data-name="script-podigee" data-src', $output);
            }
        } else {
            $output = $this->renderJavaScript($videoId);
            if ($this->getPrivacySetting()) {
                $output = str_replace('text/javascript', 'text/plain', $output);
            }
        }

        $modifyPodigeeOutputEvent = $this->eventDispatcher->dispatch(
            new ModifyPodigeeOutputEvent($output)
        );
        return $modifyPodigeeOutputEvent->getOutput();
    }

    /**
     * Get online media helper
     *
     * @param FileInterface $file
     * @return false|OnlineMediaHelperInterface
     */
    protected function getOnlineMediaHelper(FileInterface $file)
    {
        if ($this->onlineMediaHelper === null) {
            $orgFile = $file;
            if ($orgFile instanceof FileReference) {
                $orgFile = $orgFile->getOriginalFile();
            }
            if ($orgFile instanceof File) {
                $this->onlineMediaHelper = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)
                    ->getOnlineMediaHelper($orgFile);
            } else {
                $this->onlineMediaHelper = false;
            }
        }
        return $this->onlineMediaHelper;
    }

    /**
     * @param FileInterface $file
     * @return string
     */
    protected function getVideoIdFromFile(FileInterface $file)
    {
        if ($file instanceof FileReference) {
            $orgFile = $file->getOriginalFile();
        } else {
            $orgFile = $file;
        }

        return $this->getOnlineMediaHelper($file)->getOnlineMediaId($orgFile);
    }

    /**
     * @param string $videoId
     * @return string
     */
    protected function renderIframe(string $videoId): string
    {
        $iframe = '<iframe src="' . $videoId . '/embed?context=website" style="border: 0" border="0" height="100"';
        $iframe .= ' width="100%"></iframe>';

        return $iframe;
    }

    /**
     * @param string $videoId
     * @return string
     */
    protected function renderJavaScript(string $videoId): string
    {
        $javascript = '<script class="podigee-podcast-player" src="';
        $javascript .= 'https://player.podigee-cdn.net/podcast-player/javascripts/podigee-podcast-player.js"';
        $javascript .= ' data-configuration="' . $videoId . '/embed?context=external"></script>';

        return $javascript;
    }

    /**
     * @return bool
     */
    protected function getPrivacySetting(): bool
    {
        $privacy = false;
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $extSettings = $extbaseFrameworkConfiguration['plugin.']['tx_podigee.']['settings.'] ?? null;
        if (is_array($extSettings)) {
            $privacy = (bool)($extSettings['privacy'] ?? false);
        }
        return $privacy;
    }
}
