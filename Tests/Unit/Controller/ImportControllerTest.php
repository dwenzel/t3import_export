<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Controller;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Domain\Model\TransferSet;
use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImportControllerTest extends TestCase
{
    #[Test]
    public function setsAreBuiltWithTransferSetFactory(): void
    {
        $transferSet = $this->createStub(TransferSet::class);
        $transferSetFactory = $this->createMock(TransferSetFactory::class);
        $transferSetFactory->expects(self::once())
            ->method('get')
            ->with(['tasks' => 'foo'], 'bar')
            ->willReturn($transferSet);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectTransferSetFactory($transferSetFactory);

        $sets = $reflection->getMethod('buildSetsFromSettings')
            ->invoke($subject, ['bar' => ['tasks' => 'foo']]);

        self::assertSame(['bar' => $transferSet], $sets);
    }

    #[Test]
    public function getConfigurationReturnsTasksAndSetsOfModuleFromYaml(): void
    {
        $yaml = [
            'import' => [
                'tasks' => ['yaml' => ['label' => 'YAML']],
                'sets' => ['yamlSet' => ['tasks' => 'yaml']],
            ],
            'export' => ['tasks' => ['ignored' => []]],
        ];
        $configurationHandler = $this->createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn($yaml);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);

        self::assertSame($yaml['import'], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    #[Test]
    public function getConfigurationIgnoresTypoScriptSettings(): void
    {
        $configurationHandler = $this->createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn([]);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);
        $reflection->getProperty('settings')->setValue(
            $subject,
            ['import' => ['tasks' => ['typoScript' => ['label' => 'TypoScript']]]]
        );

        self::assertSame([], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    #[Test]
    public function getConfigurationReturnsEmptyArrayWithoutConfigurationHandler(): void
    {
        $reflection = new \ReflectionClass(ImportController::class);
        $subject = $reflection->newInstanceWithoutConstructor();

        self::assertSame([], $reflection->getMethod('getConfiguration')->invoke($subject));
    }
}
