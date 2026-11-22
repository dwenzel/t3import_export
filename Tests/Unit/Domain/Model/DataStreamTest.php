<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Domain\Model;

use CPSIT\ImportExportCore\Domain\Model\DataStreamInterface as CoreDataStreamInterface;
use CPSIT\ImportExportCore\Stream\TemporaryStream;
use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataStream::class)]
final class DataStreamTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $deprecations = [];

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
    public function constructorTriggersDeprecation(): void
    {
        new DataStream();
        new DataStream();

        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString(DataStream::class . ' is deprecated since 4.0.', $this->deprecations[0]);
        self::assertStringContainsString(DeprecationUtility::CORE_ISSUE_URL, $this->deprecations[0]);
    }

    #[Test]
    public function implementsInterfacesOfExtensionAndCore(): void
    {
        $subject = new DataStream();

        self::assertInstanceOf(DataStreamInterface::class, $subject);
        self::assertInstanceOf(CoreDataStreamInterface::class, $subject);
        self::assertInstanceOf(\CPSIT\ImportExportCore\Domain\Model\DataStream::class, $subject);
    }

    #[Test]
    public function getStreamBufferReturnsNullForEmptyStream(): void
    {
        self::assertNull((new DataStream())->getStreamBuffer());
    }

    #[Test]
    public function streamBufferCanBeSetAndRead(): void
    {
        $subject = new DataStream();
        $subject->setStreamBuffer('<a>1</a>');

        self::assertSame('<a>1</a>', $subject->getStreamBuffer());
        self::assertSame('<a>1</a>', (string)$subject->getStream());
    }

    #[Test]
    public function constructorAcceptsStream(): void
    {
        $stream = new TemporaryStream('<a>1</a>');

        self::assertSame($stream, (new DataStream($stream))->getStream());
    }
}
