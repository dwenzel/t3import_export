<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Controller;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Domain\Model\TransferSet;
use CPSIT\T3importExport\Controller\ExportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExportControllerTest extends TestCase
{
    #[Test]
    public function getConfigurationReturnsTasksAndSetsOfExportFromYaml(): void
    {
        $yaml = [
            'import' => ['sets' => ['ignored' => []]],
            'export' => [
                'sets' => ['daily' => ['label' => 'Daily', 'description' => 'Daily export', 'tasks' => 'foo']],
            ],
        ];
        $configurationHandler = $this->createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn($yaml);

        $reflection = new \ReflectionClass(ExportController::class);
        /** @var ExportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);

        self::assertSame($yaml['export'], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    #[Test]
    public function setsKeepLabelAndDescriptionFromConfiguration(): void
    {
        $settings = ['label' => 'Daily', 'description' => 'Daily export', 'tasks' => 'foo'];
        $transferSet = new TransferSet();
        $transferSet->setIdentifier('daily');
        $transferSet->setLabel('Daily');
        $transferSet->setDescription('Daily export');
        $transferSetFactory = $this->createMock(TransferSetFactory::class);
        $transferSetFactory->expects(self::once())
            ->method('get')
            ->with($settings, 'daily')
            ->willReturn($transferSet);

        $reflection = new \ReflectionClass(ExportController::class);
        /** @var ExportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectTransferSetFactory($transferSetFactory);

        $sets = $reflection->getMethod('buildSetsFromSettings')->invoke($subject, ['daily' => $settings]);

        self::assertSame(['daily' => $transferSet], $sets);
        self::assertSame('Daily', $sets['daily']->getLabel());
        self::assertSame('Daily export', $sets['daily']->getDescription());
    }
}
