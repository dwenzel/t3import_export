<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Controller;

use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImportControllerTest extends TestCase
{
    #[Test]
    public function setsAreBuiltWithTypoScriptSettings(): void
    {
        $settings = [
            'import' => [
                'tasks' => ['foo' => ['label' => 'Foo']],
                'sets' => ['bar' => ['tasks' => 'foo']],
            ],
        ];
        $transferSet = $this->createMock(TransferSet::class);
        $transferSetFactory = $this->createMock(TransferSetFactory::class);
        $transferSetFactory->expects(self::once())
            ->method('withSettings')
            ->with($settings)
            ->willReturnSelf();
        $transferSetFactory->expects(self::once())
            ->method('get')
            ->with(['tasks' => 'foo'], 'bar')
            ->willReturn($transferSet);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectTransferSetFactory($transferSetFactory);
        $reflection->getProperty('settings')->setValue($subject, $settings);

        $sets = $reflection->getMethod('buildSetsFromSettings')
            ->invoke($subject, $settings['import']['sets']);

        self::assertSame(['bar' => $transferSet], $sets);
    }

    #[Test]
    public function mergeYamlConfigurationAddsTasksAndSetsFromYaml(): void
    {
        $settings = [
            'import' => [
                'tasks' => ['both' => ['label' => 'from TypoScript'], 'ts' => ['label' => 'TypoScript only']],
            ],
            'export' => ['tasks' => ['other' => []]],
        ];
        $yaml = [
            'import' => [
                'tasks' => ['both' => ['label' => 'from YAML'], 'yaml' => ['label' => 'YAML only']],
                'sets' => ['yamlSet' => ['tasks' => 'yaml']],
            ],
            'export' => ['tasks' => ['ignored' => []]],
        ];
        $configurationHandler = $this->createMock(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn($yaml);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);

        $merged = $reflection->getMethod('mergeYamlConfiguration')->invoke($subject, $settings);

        self::assertSame(
            [
                'both' => ['label' => 'from TypoScript'],
                'yaml' => ['label' => 'YAML only'],
                'ts' => ['label' => 'TypoScript only'],
            ],
            $merged['import']['tasks']
        );
        self::assertSame(['yamlSet' => ['tasks' => 'yaml']], $merged['import']['sets']);
        self::assertSame($settings['export'], $merged['export']);
    }

    #[Test]
    public function mergeYamlConfigurationReturnsSettingsWithoutConfigurationHandler(): void
    {
        $settings = ['import' => ['tasks' => ['ts' => []]]];
        $reflection = new \ReflectionClass(ImportController::class);
        $subject = $reflection->newInstanceWithoutConstructor();

        self::assertSame($settings, $reflection->getMethod('mergeYamlConfiguration')->invoke($subject, $settings));
    }
}
