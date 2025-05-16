<?php
namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\T3importExport\Resource\ResourceTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class ResourceTraitTest
 */
class ResourceTraitTest extends TestCase
{
    /**
     * @var object with ResourceTrait
     */
    protected $subject;

    protected function setUp(): void
    {
        // Skip this test as it requires getMockForTrait and vfsStream
        $this->markTestSkipped('This test requires getMockForTrait and vfsStream');
    }

    #[Test]
    public function loadResourceGetsFileResource(): void
    {
        // This test is skipped in setUp()
    }
}
