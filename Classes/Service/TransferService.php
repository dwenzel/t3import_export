<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Service;

use CPSIT\ImportExportCore\Domain\Model\Dto\TaskDemand;
use CPSIT\ImportExportCore\Domain\Model\TransferTaskInterface;
use CPSIT\ImportExportCore\Service\DataTransferProcessor;
use CPSIT\ImportExportCore\Service\DemandProcessor;
use CPSIT\T3importExport\Persistence\CompletableSourceInterface;

/**
 * Runs tasks with the processor of the core.
 *
 * If a task fails, the sources that can be completed are told so (fail()),
 * e.g. to mark claimed queue items as failed. The core processor only
 * completes sources of tasks that succeeded.
 */
class TransferService
{
    public function __construct(private readonly DataTransferProcessor $processor) {}

    /**
     * @param array<array-key, TransferTaskInterface> $tasks
     * @param bool $dryRun only read the records, do not process them
     * @return array the processed records
     */
    public function run(array $tasks, bool $dryRun = false): array
    {
        $demand = new TaskDemand();
        $demand->setTasks($tasks);
        $this->processor->buildQueue($demand);
        if ($dryRun) {
            return [];
        }

        try {
            $result = (new DemandProcessor($this->processor))->process($demand);
        } catch (\Throwable $exception) {
            foreach ($tasks as $task) {
                $source = $task->getSource();
                if ($source instanceof CompletableSourceInterface) {
                    $source->fail();
                }
            }
            throw $exception;
        }

        return $result->toArray();
    }
}
