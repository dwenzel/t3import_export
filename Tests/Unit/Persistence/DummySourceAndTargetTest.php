<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Persistence\NullDataSource;
use CPSIT\ImportExportCore\Persistence\NullDataTarget;
use CPSIT\T3importExport\Persistence\DataSourceDummy;
use CPSIT\T3importExport\Persistence\DataTargetDummy;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSourceDummy::class)]
#[CoversClass(DataTargetDummy::class)]
class DummySourceAndTargetTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $deprecations = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        set_error_handler(
            function (int $level, string $message): bool {
                $this->deprecations[] = $message;
                return true;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
    }

    #[Test]
    public function sourceReturnsNoRecordsAndTriggersDeprecation(): void
    {
        self::assertSame([], (new DataSourceDummy())->getRecords([]));
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString(NullDataSource::class, $this->deprecations[0]);
    }

    #[Test]
    public function targetWritesNothingAndTriggersDeprecation(): void
    {
        $subject = new DataTargetDummy();

        self::assertTrue($subject->persist(['id' => 1]));
        $subject->persistAll();
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString(NullDataTarget::class, $this->deprecations[0]);
    }
}
