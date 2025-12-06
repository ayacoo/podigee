<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Helper;

use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Podigee helper class
 */
class PodigeeHelper extends AbstractOEmbedHelper
{
    /**
     * Get OEmbed data
     *
     * @param string $mediaId
     * @return array|null
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected function getOEmbedData($mediaId)
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('podigee');
        $token = $extConf['token'] ?? '';
        $clientId = $extConf['clientId'] ?? '';

        $oEmbedUrl = $this->getOEmbedUrl($mediaId);
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $additionalOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Client-Id' => $clientId,
            ],
        ];
        try {
            $response = $requestFactory->request($oEmbedUrl, 'GET', $additionalOptions);
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            return [];
        } catch (ClientException $e) {
            return [];
        }
    }

    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        return sprintf(
            'https://embed.podigee.com/oembed?url=%s',
            rawurlencode($mediaId)
        );
    }

    public function transformUrlToFile($url, Folder $targetFolder)
    {
        $videoId = $this->getVideoId($url);
        if ($videoId === null || $videoId === '' || $videoId === '0') {
            return null;
        }

        return $this->transformMediaIdToFile($videoId, $targetFolder, $this->extension);
    }

    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        return $this->getOnlineMediaId($file);
    }

    /**
     * Get meta data for OnlineMedia item
     * Using the meta data from oEmbed
     *
     * @param File $file
     * @return array with metadata
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function getMetaData(File $file): array
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('podigee');

        $metaData = [];

        $oEmbed = $this->getOEmbedData($this->getOnlineMediaId($file));
        if ($oEmbed) {
            $metaData['width'] = $extConf['width'] ?? 800;
            $metaData['height'] = $extConf['height'] ?? 450;
            $metaData['title'] = $oEmbed['title'] ?? '';
            $thumbnailUrl = $oEmbed['thumbnail_url'] ?? '';
            $thumbnailUrl = str_replace('%{width}', (string)$metaData['width'], $thumbnailUrl);
            $thumbnailUrl = str_replace('%{height}', (string)$metaData['height'], $thumbnailUrl);

            $metaData['podigee_thumbnail'] = $thumbnailUrl;
        }

        return $metaData;
    }

    public function getPreviewImage(File $file)
    {
        $properties = $file->getProperties();
        $previewImageUrl = $properties['podigee_thumbnail'] ?? '';

        // get preview from podigee
        if ($previewImageUrl === '') {
            $oEmbed = $this->getOEmbedData($this->getOnlineMediaId($file));
            $previewImageUrl = $oEmbed['thumbnail_url'] ?? '';
        }

        $videoId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . $file->getExtension() . '_' . md5($videoId) . '.jpg';

        if (!empty($previewImageUrl)) {
            $previewImage = GeneralUtility::getUrl($previewImageUrl);
            file_put_contents($temporaryFileName, $previewImage);
            GeneralUtility::fixPermissions($temporaryFileName);
            return $temporaryFileName;
        }

        return '';
    }

    protected function getVideoId(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // Require:
        // - scheme http or https
        // - a subdomain before podigee.io (e.g. omr.podigee.io)
        // - a non-empty path after the host
        $pattern = '~^https?://([a-z0-9-]+)\.podigee\.io/[^\s?#]+(?:[?#].*)?$~i';
        if (preg_match($pattern, $url) === 1) {
            return $url;
        }

        return null;
    }
}
