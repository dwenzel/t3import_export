<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\UpdateTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(UpdateTable::class)]
class UpdateTableTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected UpdateTable $subject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->subject = $this->get(UpdateTable::class);
    }

    private function fetchPages(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder->select('uid', 'title', 'hidden')
            ->from('pages')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    #[Test]
    public function processUpdatesMatchingRecords(): void
    {
        $records = [];
        $configuration = [
            UpdateTable::KEY_TABLE => 'pages',
            UpdateTable::KEY_WHERE => 'pid = 1',
            UpdateTable::KEY_SET_FIELDS => ['title' => 'Updated'],
        ];

        $warnings = [];
        set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        });
        try {
            self::assertTrue($this->subject->isConfigurationValid($configuration));
            self::assertTrue($this->subject->process($configuration, $records));
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);

        self::assertSame(
            [
                ['uid' => 1, 'title' => 'Root', 'hidden' => 0],
                ['uid' => 2, 'title' => 'Updated', 'hidden' => 0],
                ['uid' => 3, 'title' => 'Updated', 'hidden' => 0],
            ],
            $this->fetchPages()
        );
    }

    #[Test]
    public function processUpdatesFieldsWithConfiguredTypes(): void
    {
        $records = [];
        $configuration = [
            UpdateTable::KEY_TABLE => 'pages',
            UpdateTable::KEY_WHERE => 'uid = 3',
            UpdateTable::KEY_SET_FIELDS => ['hidden' => '1', 'title' => 'Hidden'],
            UpdateTable::KEY_TYPES => ['hidden' => 'int'],
        ];

        $this->subject->process($configuration, $records);

        self::assertSame(['uid' => 3, 'title' => 'Hidden', 'hidden' => 1], $this->fetchPages()[2]);
    }

    #[Test]
    public function processReturnsFalseWithoutCondition(): void
    {
        $records = [];
        $configuration = [
            UpdateTable::KEY_TABLE => 'pages',
            UpdateTable::KEY_SET_FIELDS => ['hidden' => 1],
        ];

        self::assertFalse($this->subject->process($configuration, $records));
        self::assertSame([0, 0, 0], array_column($this->fetchPages(), 'hidden'));
    }
}
