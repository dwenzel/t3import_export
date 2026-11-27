<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Service;

use CPSIT\T3importExport\Service\TransferService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The service logs the warnings about options for files that are not read
 */
class TransferServiceLoggerTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function serviceGetsALogger(): void
    {
        $property = new \ReflectionProperty(TransferService::class, 'logger');

        self::assertInstanceOf(LoggerInterface::class, $property->getValue($this->get(TransferService::class)));
    }
}
