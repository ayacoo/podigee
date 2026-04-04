<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Tests\Unit\Rendering;

use Ayacoo\Podigee\Event\ModifyPodigeeOutputEvent;
use Ayacoo\Podigee\Helper\PodigeeHelper;
use Ayacoo\Podigee\Rendering\PodigeeRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PodigeeRendererTest extends UnitTestCase
{
    private PodigeeRenderer $subject;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new PodigeeRenderer($eventDispatcherMock, $configurationManagerMock);
    }

    #[Test]
    public function hasFileRendererInterface(): void
    {
        self::assertInstanceOf(FileRendererInterface::class, $this->subject);
    }

    #[Test]
    public function canRenderWithMatchingMimeTypeReturnsTrue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['podigee'] = PodigeeHelper::class;

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/podigee');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('podigee');

        $result = $this->subject->canRender($fileResourceMock);
        self::assertTrue($result);
    }

    #[Test]
    public function canRenderWithMatchingMimeTypeReturnsFalse(): void
    {
        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/podigee');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('podigee');

        $result = $this->subject->canRender($fileResourceMock);
        self::assertFalse($result);
    }

    #[Test]
    #[DataProvider('getPrivacySettingWithExistingConfigReturnsBooleanDataProvider')]
    public function getPrivacySettingWithExistingConfigReturnsBoolean(array $pluginConfig, bool $expected)
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $subject = new PodigeeRenderer($eventDispatcherMock, $configurationManagerMock);

        $params = [];
        $methodName = 'getPrivacySetting';
        $result = $this->buildReflectionForProtectedFunction($methodName, $params, $subject);

        self::assertEquals($expected, $result);
    }

    public static function getPrivacySettingWithExistingConfigReturnsBooleanDataProvider(): array
    {
        return [
            'Privacy setting true' => [
                [
                    'plugin.' => [
                        'tx_podigee.' => [
                            'settings.' => [
                                'privacy' => true,
                            ],
                        ],
                    ],
                ],
                true,
            ],
            'Privacy setting false' => [
                [
                    'plugin.' => [
                        'tx_podigee.' => [
                            'settings.' => [
                                'privacy' => false,
                            ],
                        ],
                    ],
                ],
                false,
            ],
            'Privacy setting non-existing' => [
                [],
                false,
            ],
        ];
    }

    #[Test]
    public function renderWihtIframeAndWithoutPrivacyReturnsPodigeeHtml(): void
    {
        $videoId = 'https://subdomain.podigee.io/podcast-title';
        $iframe = '<iframe src="' . $videoId . '/embed?context=website" style="border: 0" border="0" height="100" ';
        $iframe .= 'width="100%"></iframe>';
        $expected = $iframe;

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/podigee');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('podigee');

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'iframe'];
        $extensionConfigurationMock->method('get')->with('podigee')->willReturn($expectedConfiguration);

        $event = new ModifyPodigeeOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $podigeeHelperMock = $this->getMockBuilder(PodigeeRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $podigeeHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $podigeeHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderWithIframeAndPrivacyReturnsPodigeeHtml(): void
    {
        $videoId = 'https://subdomain.podigee.io/podcast-title';
        $expected = '<iframe data-name="script-podigee" data-src="' . $videoId . '/embed?context=website" ';
        $expected .= 'style="border: 0" border="0" height="100" width="100%"></iframe>';

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/podigee');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('podigee');

        $event = new ModifyPodigeeOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $pluginConfig = [
            'plugin.' => [
                'tx_podigee.' => [
                    'settings.' => [
                        'privacy' => true,
                    ],
                ],
            ],
        ];

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $videoId = 'https://subdomain.podigee.io/podcast-title';

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'iframe'];
        $extensionConfigurationMock->method('get')->with('podigee')->willReturn($expectedConfiguration);

        $podigeeHelperMock = $this->getMockBuilder(PodigeeRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $podigeeHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $podigeeHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderWithJavaScriptAndPrivacyReturnsPodigeeHtml(): void
    {
        $videoId = 'https://subdomain.podigee.io/podcast-title';
        $expected = '<script class="podigee-podcast-player" nonce=""';
        $expected .= 'src="https://player.podigee-cdn.net/podcast-player/javascripts/';
        $expected .= 'podigee-podcast-player.js" data-configuration="' . $videoId;
        $expected .= '/embed?context=external"></script>';

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/podigee');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('podigee');

        $event = new ModifyPodigeeOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $pluginConfig = [
            'plugin.' => [
                'tx_podigee.' => [
                    'settings.' => [
                        'privacy' => true,
                    ],
                ],
            ],
        ];

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $videoId = 'https://subdomain.podigee.io/podcast-title';

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'javascript'];
        $extensionConfigurationMock->method('get')->with('podigee')->willReturn($expectedConfiguration);

        $podigeeHelperMock = $this->getMockBuilder(PodigeeRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $podigeeHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $podigeeHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderJavaScriptGeneratesExpectedPodigeeEmbedCode(): void
    {
        $videoId = 'https://subdomain.podigee.io/podcast-title';

        $params = [$videoId];
        $methodName = 'renderJavaScript';
        $result = $this->buildReflectionForProtectedFunction($methodName, $params, $this->subject);

        self::assertStringContainsString('podigee-podcast-player', $result);
        self::assertStringContainsString('nonce', $result);
        self::assertStringContainsString('data-configuration', $result);
        self::assertStringContainsString('embed?context=external', $result);
        self::assertStringContainsString('https://player.podigee-cdn.net/podcast-player/', $result);
    }

    protected function buildReflectionForProtectedFunction(
        string $methodName,
        array $params,
        PodigeeRenderer $subject
    ): mixed {
        $reflectionCalendar = new \ReflectionClass($subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($subject, $params);
    }
}
