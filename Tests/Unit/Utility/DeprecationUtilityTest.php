<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Utility;

use CPSIT\T3importExport\Utility\DeprecationUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeprecationUtilityTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $messages = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        $this->messages = [];
        set_error_handler(
            function (int $level, string $message): bool {
                $this->messages[] = $message;
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
    public function triggerTriggersEachMessageOnce(): void
    {
        DeprecationUtility::trigger('First is deprecated.');
        DeprecationUtility::trigger('First is deprecated.');
        DeprecationUtility::trigger('Second is deprecated.');

        self::assertSame(
            [
                't3import_export: First is deprecated. See ' . DeprecationUtility::ISSUE_URL,
                't3import_export: Second is deprecated. See ' . DeprecationUtility::ISSUE_URL,
            ],
            $this->messages
        );
    }
}
