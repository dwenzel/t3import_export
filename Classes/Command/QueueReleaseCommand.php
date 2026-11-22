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
use CPSIT\ImportExportCore\Queue\QueueRepositoryInterface;
use CPSIT\T3importExport\Dbal\QueueStatusReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sets the items in processing back to new, e.g. after a run that was
 * interrupted. Uses `release()` of the queue repository of the core.
 */
#[AsCommand(
    name: QueueReleaseCommand::DEFAULT_NAME,
    description: QueueReleaseCommand::MESSAGE_DESCRIPTION_COMMAND,
    aliases: QueueReleaseCommand::COMMAND_ALIASES
)]
class QueueReleaseCommand extends Command
{
    final public const string DEFAULT_NAME = 't3import-export:queue:release';
    final public const array COMMAND_ALIASES = ['queue:release'];
    final public const string MESSAGE_DESCRIPTION_COMMAND = 'Sets queue items in processing back to new.';
    final public const string OPTION_QUEUE = 'queue';
    final public const string OPTION_ALL = 'all';

    public function __construct(
        protected readonly QueueRepositoryInterface $repository,
        protected readonly QueueStatusReader $reader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_QUEUE,
                'q',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Release the items of this queue (the option "identifier" of the queue source); can be repeated'
            )
            ->addOption(
                self::OPTION_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Release the items of all queues'
            )
            ->setHelp(
                'Items in processing were claimed by a run of a task with a queue source. If the run was interrupted'
                . ' (e.g. the process was killed), they stay in processing and are not read again. This command sets'
                . ' them back to new, so that the next run reads them. Do not run it while a task reads from the queue:'
                . ' its items would be read a second time.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queues = array_values(array_filter(
            array_map(strval(...), (array)$input->getOption(self::OPTION_QUEUE)),
            static fn(string $queue): bool => $queue !== ''
        ));
        $all = (bool)$input->getOption(self::OPTION_ALL);
        if ($queues === [] && !$all) {
            $output->writeln('<error>Name a queue with --queue or use --all.</error>');
            return Command::INVALID;
        }
        if ($queues !== [] && $all) {
            $output->writeln('<error>Use either --queue or --all.</error>');
            return Command::INVALID;
        }
        if ($all) {
            $queues = array_keys(array_filter(
                $this->reader->countByQueue(),
                static fn(array $counts): bool => ($counts[QueueItemStatus::Processing->value] ?? 0) > 0
            ));
            if ($queues === []) {
                $output->writeln('No queue items in processing.');
                return Command::SUCCESS;
            }
        }

        foreach ($queues as $queue) {
            $released = $this->repository->release((string)$queue);
            $output->writeln(sprintf('Released %d items of queue "%s".', $released, $queue));
        }

        return Command::SUCCESS;
    }
}
