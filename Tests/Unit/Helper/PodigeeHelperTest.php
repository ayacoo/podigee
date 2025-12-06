<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Tests\Unit\Helper;

use Ayacoo\Podigee\Helper\PodigeeHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PodigeeHelperTest extends UnitTestCase
{
    private PodigeeHelper $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PodigeeHelper('podigee');
    }

    #[Test]
    public function isAbstractOEmbedHelper(): void
    {
        self::assertInstanceOf(AbstractOEmbedHelper::class, $this->subject);
    }

    #[Test]
    public function getMetaDataWithOEmbedData()
    {
        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['width' => 150, 'height' => 150];
        $extensionConfigurationMock->method('get')->with('podigee')->willReturn($expectedConfiguration);

        $expectedMetaData = [
            'width' => 150,
            'height' => 150,
            'title' => 'Sample Title',
            'podigee_thumbnail' => 'https://podigee.com/thumbnail.jpg'
        ];

        // Mocking the getOnlineMediaId() and getOEmbedData() methods
        $podigeeHelper = $this->getMockBuilder(PodigeeHelper::class)
            ->onlyMethods(['getOnlineMediaId', 'getOEmbedData'])
            ->disableOriginalConstructor()
            ->getMock();

        $podigeeHelper->method('getOnlineMediaId')->willReturn('123456');
        $podigeeHelper->method('getOEmbedData')
            ->with('123456')
            ->willReturn([
                'title' => 'Sample Title',
                'thumbnail_url' => 'https://podigee.com/thumbnail.jpg'
            ]);


        $metaData = $podigeeHelper->getMetaData($fileMock);

        self::assertEquals($expectedMetaData, $metaData);
    }

    #[Test]
    public function getMetaDataWithoutOEmbedData()
    {
        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['width' => 150, 'height' => 150];
        $extensionConfigurationMock->method('get')->with('podigee')->willReturn($expectedConfiguration);

        // Mocking the getOnlineMediaId() and getOEmbedData() methods
        $podigeeHelper = $this->getMockBuilder(PodigeeHelper::class)
            ->onlyMethods(['getOnlineMediaId', 'getOEmbedData'])
            ->disableOriginalConstructor()
            ->getMock();

        $podigeeHelper->method('getOnlineMediaId')->willReturn('123456');
        $podigeeHelper->method('getOEmbedData')
            ->with('123456')
            ->willReturn([]);

        $metaData = $podigeeHelper->getMetaData($fileMock);

        self::assertEmpty($metaData);
    }

    #[Test]
    public function getPublicUrlReturnsPublicUrl()
    {
        $videoId = 'https://subdomain.podigee.io/podcast-title';

        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $podigeeHelperMock = $this->getMockBuilder(PodigeeHelper::class)
            ->onlyMethods(['getOnlineMediaId'])
            ->disableOriginalConstructor()
            ->getMock();
        $podigeeHelperMock->method('getOnlineMediaId')->with($fileMock)->willReturn($videoId);

        $result = $podigeeHelperMock->getPublicUrl($fileMock);
        $expectedUrl = 'https://subdomain.podigee.io/podcast-title';
        self::assertEquals($expectedUrl, $result);
    }

    #[Test]
    #[DataProvider('getVideoIdDataProvider')]
    public function getVideoIdWithValidUrlReturnsAudioIdOrNull(string $url, mixed $expectedVideoId)
    {
        $params = [$url];
        $methodName = 'getVideoId';
        $actualAudioId = $this->buildReflectionForProtectedFunction($methodName, $params);

        self::assertSame($expectedVideoId, $actualAudioId);
    }

    public static function getVideoIdDataProvider(): array
    {
        return [
            ['https://subdomain.podigee.io/podcast-title', 'https://subdomain.podigee.io/podcast-title'],
            ['https://podigee.io/', null],
            ['https://www.podigee.io/', null],
            ['https://google.com', null],
        ];
    }

    private function buildReflectionForProtectedFunction(string $methodName, array $params)
    {
        $reflectionCalendar = new \ReflectionClass($this->subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->subject, $params);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the GeneralUtility instance pool to remove the mock
        GeneralUtility::purgeInstances();
    }
}
