<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Controller;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Domain\Model\TransferSet;
use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class ImportControllerTest extends TestCase
{
    #[Test]
    public function setsAreBuiltWithTransferSetFactory(): void
    {
        $transferSet = $this->createStub(TransferSet::class);
        $transferSetFactory = $this->createMock(TransferSetFactory::class);
        $transferSetFactory->expects(self::once())
            ->method('get')
            ->with(['tasks' => 'foo'], 'bar')
            ->willReturn($transferSet);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectTransferSetFactory($transferSetFactory);

        $sets = $reflection->getMethod('buildSetsFromSettings')
            ->invoke($subject, ['bar' => ['tasks' => 'foo']]);

        self::assertSame(['bar' => $transferSet], $sets);
    }

    #[Test]
    public function getConfigurationReturnsTasksAndSetsOfModuleFromYaml(): void
    {
        $yaml = [
            'import' => [
                'tasks' => ['yaml' => ['label' => 'YAML']],
                'sets' => ['yamlSet' => ['tasks' => 'yaml']],
            ],
            'export' => ['tasks' => ['ignored' => []]],
        ];
        $configurationHandler = $this->createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn($yaml);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);

        self::assertSame($yaml['import'], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    #[Test]
    public function getConfigurationIgnoresTypoScriptSettings(): void
    {
        $configurationHandler = $this->createStub(ConfigurationHandlerInterface::class);
        $configurationHandler->method('getFullConfiguration')->willReturn([]);

        $reflection = new \ReflectionClass(ImportController::class);
        /** @var ImportController $subject */
        $subject = $reflection->newInstanceWithoutConstructor();
        $subject->injectConfigurationHandler($configurationHandler);
        $reflection->getProperty('settings')->setValue(
            $subject,
            ['import' => ['tasks' => ['typoScript' => ['label' => 'TypoScript']]]]
        );

        self::assertSame([], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    #[Test]
    public function getConfigurationReturnsEmptyArrayWithoutConfigurationHandler(): void
    {
        $reflection = new \ReflectionClass(ImportController::class);
        $subject = $reflection->newInstanceWithoutConstructor();

        self::assertSame([], $reflection->getMethod('getConfiguration')->invoke($subject));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function actionsForUnknownIdentifiersDataProvider(): array
    {
        return [
            'task' => ['taskAction', 'No task with the identifier "missing" is configured.'],
            'set' => ['setAction', 'No set with the identifier "missing" is configured.'],
        ];
    }

    #[Test]
    #[DataProvider('actionsForUnknownIdentifiersDataProvider')]
    public function actionsRedirectToIndexForUnknownIdentifier(string $action, string $message): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageQueue->expects(self::once())
            ->method('enqueue')
            ->with(self::callback(
                static fn(FlashMessage $flashMessage): bool => $flashMessage->getMessage() === $message
                    && $flashMessage->getSeverity() === ContextualFeedbackSeverity::ERROR
            ));
        $subject = $this->getMockBuilder(ImportController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['redirect', 'getFlashMessageQueue'])
            ->getMock();
        $subject->expects(self::once())->method('getFlashMessageQueue')
            ->with('module.t3import_export')
            ->willReturn($flashMessageQueue);
        $subject->expects(self::once())->method('redirect')->with('index')->willReturn($response);
        $reflection = new \ReflectionClass(ImportController::class);
        $reflection->getProperty('configuration')->setValue($subject, ['tasks' => ['foo' => []], 'sets' => ['bar' => []]]);

        try {
            $reflection->getMethod($action)->invoke($subject, 'missing');
            self::fail('No PropagateResponseException was thrown');
        } catch (PropagateResponseException $exception) {
            self::assertSame(1795300481, $exception->getCode());
            self::assertSame($response, $exception->getResponse());
        }
    }

    #[Test]
    public function redirectToIndexIfNotConfiguredDoesNothingForConfiguredIdentifier(): void
    {
        $subject = $this->getMockBuilder(ImportController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['redirect', 'getFlashMessageQueue'])
            ->getMock();
        $subject->expects(self::never())->method('redirect');
        $subject->expects(self::never())->method('getFlashMessageQueue');
        $reflection = new \ReflectionClass(ImportController::class);
        $reflection->getProperty('configuration')->setValue($subject, ['tasks' => ['foo' => []]]);

        $reflection->getMethod('redirectToIndexIfNotConfigured')->invoke($subject, 'tasks', 'foo');
    }
}
