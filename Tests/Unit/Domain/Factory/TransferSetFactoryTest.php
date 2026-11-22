<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Domain\Factory;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Factory\ComponentFactory;
use CPSIT\ImportExportCore\Factory\ComponentRegistry;
use CPSIT\ImportExportCore\Factory\TransferSetFactory as CoreTransferSetFactory;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\Persistence\NullDataSource;
use CPSIT\ImportExportCore\Persistence\NullDataTarget;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransferSetFactory::class)]
class TransferSetFactoryTest extends TestCase
{
    protected const array TASK = [
        'source' => ['class' => NullDataSource::class],
        'target' => ['class' => NullDataTarget::class],
    ];

    protected array $configuration = [];

    protected function createSubject(): TransferSetFactory
    {
        $configurationHandler = self::createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')
            ->willReturnCallback(fn(): array => $this->configuration);

        return new TransferSetFactory(
            new CoreTransferSetFactory(new TransferTaskFactory(new ComponentFactory(new ComponentRegistry()))),
            $configurationHandler
        );
    }

    #[Test]
    public function getSetsIdentifierLabelAndDescription(): void
    {
        $set = $this->createSubject()->get(['label' => 'Foo', 'description' => 'Bar'], 'fooSet');

        self::assertSame('fooSet', $set->getIdentifier());
        self::assertSame('Foo', $set->getLabel());
        self::assertSame('Bar', $set->getDescription());
        self::assertSame([], $set->getTasks());
    }

    #[Test]
    public function getResolvesImportAndExportTasksFromConfigurationHandler(): void
    {
        $this->configuration = [
            'import' => ['tasks' => ['foo' => self::TASK]],
            'export' => ['tasks' => ['bar' => self::TASK]],
        ];

        $tasks = $this->createSubject()->get(['tasks' => 'foo, bar'], 'set')->getTasks();

        self::assertSame(['foo', 'bar'], array_keys($tasks));
        self::assertSame('foo', $tasks['foo']->getIdentifier());
        self::assertInstanceOf(NullDataSource::class, $tasks['foo']->getSource());
    }

    #[Test]
    public function getPrefersImportTasksOverExportTasks(): void
    {
        $this->configuration = [
            'import' => ['tasks' => ['foo' => self::TASK + ['label' => 'yaml import']]],
            'export' => ['tasks' => ['foo' => self::TASK + ['label' => 'yaml export']]],
        ];
        $subject = $this->createSubject();

        self::assertSame('yaml import', $subject->get(['tasks' => 'foo'], 'set')->getTasks()['foo']->getLabel());
    }

    #[Test]
    public function getReadsConfigurationOnEachCall(): void
    {
        $subject = $this->createSubject();
        $this->configuration = ['import' => ['tasks' => ['foo' => self::TASK]]];

        self::assertCount(1, $subject->get(['tasks' => 'foo'], 'set')->getTasks());
    }

    #[Test]
    public function getThrowsExceptionForUndefinedTask(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1794230031);

        $this->createSubject()->get(['tasks' => 'missing'], 'set');
    }
}
