<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\T3importExport\Dbal\QueueStatusReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows the number of items by status for each queue and, with `--queue` or
 * `--status`, lists the items.
 */
#[AsCommand(
    name: QueueStatusCommand::DEFAULT_NAME,
    description: QueueStatusCommand::MESSAGE_DESCRIPTION_COMMAND,
    aliases: QueueStatusCommand::COMMAND_ALIASES
)]
class QueueStatusCommand extends Command
{
    final public const string DEFAULT_NAME = 't3import-export:queue:status';
    final public const array COMMAND_ALIASES = ['queue:status'];
    final public const string MESSAGE_DESCRIPTION_COMMAND = 'Shows the number of queue items by status and lists items.';
    final public const string OPTION_QUEUE = 'queue';
    final public const string OPTION_STATUS = 'status';
    final public const string OPTION_LIMIT = 'limit';
    final public const int DEFAULT_LIMIT = 50;

    public function __construct(protected readonly QueueStatusReader $reader)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_QUEUE,
                'q',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only this queue (the option "identifier" of the queue source and target); can be repeated. Lists the items.'
            )
            ->addOption(
                self::OPTION_STATUS,
                's',
                InputOption::VALUE_REQUIRED,
                'Lists the items with this status: new, processing, finished, failed (or 0 to 3)'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum number of items to list',
                (string)self::DEFAULT_LIMIT
            )
            ->setHelp(
                'Without options, the number of items by status is shown for each queue.'
                . ' With --queue or --status, the items are listed as well, in the order they were added.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queues = array_values(array_filter(
            array_map(strval(...), (array)$input->getOption(self::OPTION_QUEUE)),
            static fn(string $queue): bool => $queue !== ''
        ));
        $statusOption = $input->getOption(self::OPTION_STATUS);
        $status = null;
        if ($statusOption !== null) {
            $status = self::parseStatus((string)$statusOption);
            if ($status === null) {
                $output->writeln(sprintf('<error>Invalid status "%s". Use new, processing, finished or failed.</error>', $statusOption));
                return Command::INVALID;
            }
        }
        $limit = (string)$input->getOption(self::OPTION_LIMIT);
        if (!ctype_digit($limit) || (int)$limit < 1) {
            $output->writeln(sprintf('<error>Invalid limit "%s". Use a positive integer.</error>', $limit));
            return Command::INVALID;
        }

        $counts = $this->reader->countByQueue($queues);
        if ($counts === []) {
            $output->writeln('No queue items found.');
            return Command::SUCCESS;
        }
        $this->renderCounts($output, $counts);

        if ($queues !== [] || $status !== null) {
            $this->renderItems($output, $this->reader->findItems($queues, $status, (int)$limit), (int)$limit);
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, array<int, int>> $counts
     */
    protected function renderCounts(OutputInterface $output, array $counts): void
    {
        $table = new Table($output);
        $table->setHeaders(['Queue', ...array_map(self::label(...), QueueItemStatus::cases()), 'total']);
        foreach ($counts as $queue => $countByStatus) {
            $row = [$queue];
            foreach (QueueItemStatus::cases() as $status) {
                $row[] = $countByStatus[$status->value] ?? 0;
            }
            $row[] = array_sum($countByStatus);
            $table->addRow($row);
        }
        $table->render();
    }

    /**
     * @param list<array{id: int, queue: string, status: QueueItemStatus|null, statusValue: int, createdAt: string, updatedAt: string}> $items
     */
    protected function renderItems(OutputInterface $output, array $items, int $limit): void
    {
        if ($items === []) {
            $output->writeln('No queue items with this status.');
            return;
        }
        $table = new Table($output);
        $table->setHeaders(['ID', 'Queue', 'Status', 'Created', 'Updated']);
        foreach ($items as $item) {
            $status = $item['status'] !== null ? self::label($item['status']) : sprintf('unknown (%d)', $item['statusValue']);
            $table->addRow([$item['id'], $item['queue'], $status, $item['createdAt'], $item['updatedAt']]);
        }
        $table->render();
        if (count($items) >= $limit) {
            $output->writeln(sprintf('Showing the first %d items. Use --limit to show more.', $limit));
        }
    }

    protected static function parseStatus(string $value): ?QueueItemStatus
    {
        if (ctype_digit($value)) {
            return QueueItemStatus::tryFrom((int)$value);
        }
        foreach (QueueItemStatus::cases() as $status) {
            if (strcasecmp($status->name, $value) === 0) {
                return $status;
            }
        }

        return null;
    }

    protected static function label(QueueItemStatus $status): string
    {
        return strtolower($status->name);
    }
}
