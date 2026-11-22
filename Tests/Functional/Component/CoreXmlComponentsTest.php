<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\ImportExportCore\File\ValidateXMLFinisher;
use CPSIT\ImportExportCore\Messaging\Message;
use CPSIT\ImportExportCore\Processor\ValidateXMLPreProcessor;
use CPSIT\ImportExportCore\Processor\XMLMapperPreProcessor;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\ImportExportCore\Service\DataTransferProcessor;
use CPSIT\T3importExport\Component\PreProcessor\ValidateXML;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use CPSIT\T3importExport\Resource\XmlSchema;
use CPSIT\T3importExport\Service\TransferService;
use CPSIT\T3importExport\Tests\Functional\Fixtures\Classes\ArrayDataSource;
use CPSIT\T3importExport\Tests\Functional\Fixtures\Classes\CollectingDataTarget;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The XML components of the core in tasks, with the resource loader and the
 * file rules of the extension. They replace the deprecated `ValidateXML`
 * (pre-processor and finisher) and `XMLMapper` of the extension.
 */
class CoreXmlComponentsTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    // XML schemas are not in the allowed file extensions of TYPO3, which
    // are enforced by default (security.system.enforceAllowedFileExtensions)
    protected array $configurationToUseInTestInstance = [
        'SYS' => ['miscfile_ext' => 'xsd'],
    ];

    private const string FIXTURES = 'EXT:t3import_export/Tests/Functional/Fixtures/Files/';
    private const string SCHEMAS = self::FIXTURES . 'Schema/';

    private const string VALID_COURSE = '<course id="1"><title>First</title></course>';
    private const string INVALID_COURSE = '<course><name>Second</name></course>';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CollectingDataTarget::$records = [];
        // a valid schema outside of the allowed directories
        // (by default fileadmin/ and var/import/)
        file_put_contents(
            Environment::getPublicPath() . '/typo3temp/outside.xsd',
            '<?xml version="1.0"?><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">'
            . '<xs:element name="outside" type="xs:string"/></xs:schema>'
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        @unlink(Environment::getPublicPath() . '/typo3temp/outside.xsd');
        CollectingDataTarget::$records = [];
        parent::tearDown();
    }

    /**
     * @param array<array-key, mixed> $settings
     */
    private function runTask(array $settings): TaskResult
    {
        $task = $this->get(TransferTaskFactory::class)->create('xml', $settings);

        $result = $this->get(DataTransferProcessor::class)->process($task, new TaskResult());
        self::assertInstanceOf(TaskResult::class, $result);

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $records
     * @param array<string, mixed> $schema
     * @return array<array-key, mixed>
     */
    private function validationTask(array $records, array $schema): array
    {
        return [
            'source' => [
                'class' => ArrayDataSource::class,
                'config' => ['records' => $records],
            ],
            'preProcessors' => [
                10 => [
                    'class' => ValidateXMLPreProcessor::class,
                    'config' => ['fields' => 'xml', 'identifier' => 'id', 'schema' => $schema],
                ],
            ],
            'target' => ['class' => CollectingDataTarget::class],
        ];
    }

    /**
     * @return list<int|null>
     */
    private static function messageIds(TaskResult $result, int $severity): array
    {
        $ids = [];
        foreach ($result->getMessages() as $message) {
            if ($message->getSeverity() === $severity) {
                $ids[] = $message->getId();
            }
        }

        return $ids;
    }

    #[Test]
    public function validateXmlPreProcessorValidatesFieldsAgainstSchemaWithExtensionPath(): void
    {
        $result = $this->runTask($this->validationTask(
            [
                ['id' => 1, 'xml' => self::VALID_COURSE],
                ['id' => 2, 'xml' => self::INVALID_COURSE],
            ],
            ['file' => self::SCHEMAS . 'course.xsd']
        ));

        self::assertCount(2, CollectingDataTarget::$records);
        self::assertArrayNotHasKey(ValidateXMLPreProcessor::KEY_VALIDATION_FAILED, CollectingDataTarget::$records[0]);
        self::assertTrue(CollectingDataTarget::$records[1][ValidateXMLPreProcessor::KEY_VALIDATION_FAILED]);
        self::assertContains(ValidateXMLPreProcessor::ERROR_INVALID_XML, self::messageIds($result, Message::SEVERITY_ERROR));
    }

    #[Test]
    public function validateXmlPreProcessorRunsWithTheTransferServiceOfTheExtension(): void
    {
        $task = $this->get(TransferTaskFactory::class)->create('xml', $this->validationTask(
            [['id' => 1, 'xml' => self::INVALID_COURSE]],
            ['file' => self::SCHEMAS . 'course.xsd']
        ));

        $this->get(TransferService::class)->run([$task]);

        self::assertTrue(CollectingDataTarget::$records[0][ValidateXMLPreProcessor::KEY_VALIDATION_FAILED]);
    }

    #[Test]
    public function preProcessorsOfOneTaskKeepTheirOwnSchemas(): void
    {
        $settings = $this->validationTask(
            [
                ['id' => 1, 'xml' => self::VALID_COURSE, 'list' => '<courses>' . self::VALID_COURSE . '</courses>'],
                ['id' => 2, 'xml' => self::VALID_COURSE, 'list' => '<courses>' . self::VALID_COURSE . '</courses>'],
            ],
            ['file' => self::SCHEMAS . 'course.xsd']
        );
        $settings['preProcessors'][20] = [
            'class' => ValidateXMLPreProcessor::class,
            'config' => ['fields' => 'list', 'identifier' => 'id', 'schema' => ['file' => self::SCHEMAS . 'courses.xsd']],
        ];

        $result = $this->runTask($settings);

        self::assertSame([], self::messageIds($result, Message::SEVERITY_ERROR));
        foreach (CollectingDataTarget::$records as $record) {
            self::assertArrayNotHasKey(ValidateXMLPreProcessor::KEY_VALIDATION_FAILED, $record);
        }
    }

    #[Test]
    public function validateXmlPreProcessorRejectsSchemaIncludeOutsideOfTheAllowedDirectories(): void
    {
        try {
            $this->runTask($this->validationTask(
                [['id' => 1, 'xml' => '<outside/>']],
                ['file' => self::SCHEMAS . 'include-outside.xsd']
            ));
            self::fail('The include outside of the allowed directories was loaded.');
        } catch (MissingResourceException $exception) {
            self::assertSame(ValidateXMLPreProcessor::ERROR_MISSING_SCHEMA, $exception->getCode());
            $previous = $exception->getPrevious();
            self::assertInstanceOf(MissingResourceException::class, $previous);
            self::assertSame(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED, $previous->getCode());
        }
        self::assertSame([], CollectingDataTarget::$records);
    }

    #[Test]
    public function deprecatedValidateXmlRejectsTheSameSchemaInclude(): void
    {
        $subject = new ValidateXML();
        $record = ['id' => 1, 'xml' => '<outside/>'];

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(XmlSchema::ERROR_INCLUDE_NOT_ALLOWED);

        @$subject->process(
            ['fields' => 'xml', 'identifier' => 'id', 'schema' => ['file' => self::SCHEMAS . 'include-outside.xsd']],
            $record
        );
    }

    #[Test]
    public function validateXmlPreProcessorDoesNotLoadSchemaImportsFromHostsThatAreNotAllowed(): void
    {
        try {
            $this->runTask($this->validationTask(
                [['id' => 1, 'xml' => self::VALID_COURSE]],
                ['file' => self::SCHEMAS . 'import-url.xsd']
            ));
            self::fail('The schema import of a host that is not allowed was loaded.');
        } catch (MissingResourceException $exception) {
            self::assertSame(ValidateXMLPreProcessor::ERROR_MISSING_SCHEMA, $exception->getCode());
            $previous = $exception->getPrevious();
            self::assertInstanceOf(MissingResourceException::class, $previous);
            self::assertSame(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED, $previous->getCode());
        }
    }

    #[Test]
    public function xmlMapperPreProcessorMarksFields(): void
    {
        $this->runTask([
            'source' => [
                'class' => ArrayDataSource::class,
                'config' => ['records' => [['id' => 1, 'title' => 'First', 'teaser' => 'Text']]],
            ],
            'preProcessors' => [
                10 => [
                    'class' => XMLMapperPreProcessor::class,
                    'config' => ['fields' => ['id' => '@attribute', 'teaser' => '@cdata']],
                ],
            ],
            'target' => ['class' => CollectingDataTarget::class],
        ]);

        self::assertSame(
            [[
                'title' => 'First',
                'teaser' => ['@value' => 'Text', '@cdata' => true],
                '@attribute' => ['id' => 1],
            ]],
            CollectingDataTarget::$records
        );
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<array-key, mixed>
     */
    private function finisherTask(string $file, array $schema): array
    {
        return [
            'source' => ['class' => ArrayDataSource::class, 'config' => ['records' => [['id' => 1]]]],
            'target' => ['class' => CollectingDataTarget::class],
            'finishers' => [
                10 => [
                    'class' => ValidateXMLFinisher::class,
                    'config' => ['file' => $file, 'schema' => $schema],
                ],
            ],
        ];
    }

    #[Test]
    public function validateXmlFinisherValidatesFileAgainstSchemaWithExtensionPath(): void
    {
        $result = $this->runTask($this->finisherTask(self::FIXTURES . 'courses.xml', ['file' => self::SCHEMAS . 'courses.xsd']));

        self::assertSame([ValidateXMLFinisher::NOTICE_VALID], self::messageIds($result, Message::SEVERITY_NOTICE));
    }

    #[Test]
    public function validateXmlFinisherReportsInvalidFile(): void
    {
        $result = $this->runTask($this->finisherTask(self::FIXTURES . 'courses.xml', ['file' => self::SCHEMAS . 'course.xsd']));

        self::assertSame([ValidateXMLFinisher::NOTICE_INVALID], self::messageIds($result, Message::SEVERITY_NOTICE));
    }

    #[Test]
    public function validateXmlFinisherValidatesFileInTheImportDirectory(): void
    {
        $directory = Environment::getVarPath() . '/import/tests';
        @mkdir($directory, 0777, true);
        $file = $directory . '/courses.xml';
        copy(Environment::getExtensionsPath() . '/t3import_export/Tests/Functional/Fixtures/Files/courses.xml', $file);

        try {
            $result = $this->runTask($this->finisherTask($file, ['file' => self::SCHEMAS . 'courses.xsd']));
        } finally {
            unlink($file);
        }

        self::assertSame([ValidateXMLFinisher::NOTICE_VALID], self::messageIds($result, Message::SEVERITY_NOTICE));
    }

    #[Test]
    public function validateXmlFinisherRejectsSchemaIncludeOutsideOfTheAllowedDirectories(): void
    {
        try {
            $this->runTask($this->finisherTask(self::FIXTURES . 'courses.xml', ['file' => self::SCHEMAS . 'include-outside.xsd']));
            self::fail('The include outside of the allowed directories was loaded.');
        } catch (MissingResourceException $exception) {
            self::assertSame(ValidateXMLFinisher::ERROR_MISSING_SCHEMA, $exception->getCode());
            $previous = $exception->getPrevious();
            self::assertInstanceOf(MissingResourceException::class, $previous);
            self::assertSame(Typo3PathResolver::ERROR_DIRECTORY_NOT_ALLOWED, $previous->getCode());
        }
    }

    #[Test]
    public function validateXmlFinisherDoesNotReadFilesOutsideOfTheAllowedDirectories(): void
    {
        file_put_contents(Environment::getPublicPath() . '/typo3temp/outside.xml', '<outside/>');

        try {
            $result = $this->runTask($this->finisherTask('typo3temp/outside.xml', ['file' => self::SCHEMAS . 'course.xsd']));
        } finally {
            unlink(Environment::getPublicPath() . '/typo3temp/outside.xml');
        }

        self::assertSame([ValidateXMLFinisher::ERROR_EMPTY_RESOURCE], self::messageIds($result, Message::SEVERITY_ERROR));
        self::assertSame([], self::messageIds($result, Message::SEVERITY_NOTICE));
    }
}
