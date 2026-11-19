<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component;

use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\ImportExportCore\Persistence\NullDataSource;
use CPSIT\ImportExportCore\Persistence\NullDataTarget;
use CPSIT\ImportExportCore\Processor\SetValuesPreProcessor;
use CPSIT\ImportExportCore\Service\DataTransferProcessor;
use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Components disabled by expressions, created as by the component factory (#77)
 */
class ExpressionConditionTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function componentIsDisabledByExpression(): void
    {
        $subject = GeneralUtility::makeInstance(LookUpDB::class);
        $configuration = [
            'disable' => [
                'expression' => "record['type'] == 'news'",
            ],
        ];

        self::assertTrue($subject->isDisabled($configuration, ['type' => 'news']));
        self::assertFalse($subject->isDisabled($configuration, ['type' => 'event']));
    }

    #[Test]
    public function corePreProcessorIsDisabledByExpression(): void
    {
        $task = $this->get(TransferTaskFactory::class)->create('task', [
            'source' => ['class' => NullDataSource::class],
            'target' => ['class' => NullDataTarget::class],
            'preProcessors' => [
                10 => [
                    'class' => SetValuesPreProcessor::class,
                    'config' => [
                        'values' => ['pid' => 12],
                        'disable' => ['expression' => "record['type'] == 'news'"],
                    ],
                ],
            ],
        ]);
        $task->setSource(new class () implements DataSourceInterface {
            public function getRecords(array $configuration): iterable
            {
                return [['type' => 'news'], ['type' => 'event']];
            }
        });

        $result = $this->get(DataTransferProcessor::class)->process($task);

        self::assertSame([['type' => 'news'], ['type' => 'event', 'pid' => 12]], $result->toArray());
    }
}
