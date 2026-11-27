<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Service;

use CPSIT\ImportExportCore\Domain\Model\ResultInterface;
use CPSIT\ImportExportCore\Domain\Model\TransferTask;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use CPSIT\ImportExportCore\Service\DataTransferProcessor;
use CPSIT\T3importExport\Persistence\CompletableSourceInterface;
use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(TransferService::class)]
class TransferServiceTest extends TestCase
{
    /**
     * @var list<string>
     */
    public array $calls = [];

    protected function createSource(array $records): DataSourceInterface
    {
        $test = $this;
        return new class ($records, $test) implements CompletableSourceInterface, DataSourceInterface {
            public function __construct(private readonly array $records, private readonly TransferServiceTest $test) {}

            public function getRecords(array $configuration): iterable
            {
                $this->test->calls[] = 'read';
                return $this->records;
            }

            public function complete(ResultInterface $result): void
            {
                $this->test->calls[] = 'complete';
            }

            public function fail(): void
            {
                $this->test->calls[] = 'fail';
            }
        };
    }

    protected function createTarget(bool $fail = false): DataTargetInterface
    {
        $test = $this;
        return new class ($fail, $test) implements DataTargetInterface {
            public function __construct(private readonly bool $fail, private readonly TransferServiceTest $test) {}

            public function persist($object, ?array $configuration = null)
            {
                if ($this->fail) {
                    throw new \RuntimeException('target failed', 1795000001);
                }
                $this->test->calls[] = 'persist ' . $object['uid'];
                return true;
            }

            public function persistAll($result = null, ?array $configuration = null) {}
        };
    }

    protected function createTask(string $identifier, array $records, bool $fail = false): TransferTask
    {
        $task = new TransferTask();
        $task->setIdentifier($identifier);
        $task->setSource($this->createSource($records));
        $task->setTarget($this->createTarget($fail));

        return $task;
    }

    #[Test]
    public function runProcessesTasksAndReturnsRecords(): void
    {
        $subject = new TransferService(new DataTransferProcessor());

        $result = $subject->run([$this->createTask('foo', [['uid' => 1], ['uid' => 2]])]);

        self::assertSame([['uid' => 1], ['uid' => 2]], $result);
        self::assertSame(['read', 'persist 1', 'persist 2', 'complete'], $this->calls);
    }

    #[Test]
    public function runOnlyReadsRecordsInDryRun(): void
    {
        $subject = new TransferService(new DataTransferProcessor());

        self::assertSame([], $subject->run([$this->createTask('foo', [['uid' => 1]])], true));
        self::assertSame(['read'], $this->calls);
    }

    #[Test]
    public function runTellsSourcesAboutFailureAndRethrowsException(): void
    {
        $subject = new TransferService(new DataTransferProcessor());

        try {
            $subject->run([$this->createTask('foo', [['uid' => 1]], true)]);
            self::fail('Exception expected');
        } catch (\RuntimeException $exception) {
            self::assertSame(1795000001, $exception->getCode());
        }
        self::assertSame(['read', 'fail'], $this->calls);
    }

    #[Test]
    public function runLogsWarningsForFileOptionsOfVersion3(): void
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories'] = ['fileadmin/import'];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')
            ->with(self::stringContains("['fileSourceDirectories']"));
        $subject = new TransferService(new DataTransferProcessor(), $logger);

        try {
            $subject->run([$this->createTask('foo', [['uid' => 1]])]);
        } finally {
            $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        }
    }

    #[Test]
    public function runLogsNoWarningWithoutFileOptionsOfVersion3(): void
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $subject = new TransferService(new DataTransferProcessor(), $logger);

        try {
            $subject->run([$this->createTask('foo', [['uid' => 1]])]);
        } finally {
            $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        }
    }
}
