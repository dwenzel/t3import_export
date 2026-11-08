<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Controller;

use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImportControllerTest extends TestCase
{
    #[Test]
    public function setsAreBuiltWithTypoScriptSettings(): void
    {
        $settings = [
            'import' => [
                'tasks' => ['foo' => ['label' => 'Foo']],
                'sets' => ['bar' => ['tasks' => 'foo']],
            ],
        ];
        $transferSet = $this->createMock(TransferSet::class);
        $transferSetFactory = $this->createMock(TransferSetFactory::class);
        $transferSetFactory->expects(self::once())
            ->method('withSettings')
            ->with($settings)
            ->willReturnSelf();
        $transferSetFactory->expects(self::once())
            ->method('get')
            ->with(['tasks' => 'foo'], 'bar')
            ->willReturn($transferSet);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectTransferSetFactory($transferSetFactory);
        $reflection->getProperty('settings')->setValue($subject, $settings);

        $sets = $reflection->getMethod('buildSetsFromSettings')
            ->invoke($subject, $settings['import']['sets']);

        self::assertSame(['bar' => $transferSet], $sets);
    }
}
