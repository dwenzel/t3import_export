<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\T3importExport\Resource\ResourceTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Concrete class for testing ResourceTrait
 */
class ResourceTraitTestClass
{
    use ResourceTrait;
}

/**
 * Class ResourceTraitTest
 */
class ResourceTraitTest extends TestCase
{
    protected ResourceTraitTestClass $subject;

    protected function setUp(): void
    {
        $this->subject = new ResourceTraitTestClass();
    }

    #[Test]
    public function loadResourceReturnsNullForEmptyConfiguration(): void
    {
        $result = $this->subject->loadResource([]);
        $this->assertNull($result);
    }

    #[Test]
    public function loadResourceReturnsNullForNonExistentFile(): void
    {
        $configuration = ['file' => 'nonexistent/file.txt'];
        $result = $this->subject->loadResource($configuration);
        $this->assertNull($result);
    }
}
