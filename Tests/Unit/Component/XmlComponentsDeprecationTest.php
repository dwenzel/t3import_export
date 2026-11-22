<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component;

use CPSIT\ImportExportCore\File\ValidateXMLFinisher;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\ImportExportCore\Processor\ValidateXMLPreProcessor;
use CPSIT\ImportExportCore\Processor\XMLMapperPreProcessor;
use CPSIT\T3importExport\Component\Finisher\ValidateXML as ValidateXMLFinisherOfTheExtension;
use CPSIT\T3importExport\Component\PreProcessor\ValidateXML;
use CPSIT\T3importExport\Component\PreProcessor\XMLMapper;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The XML components of the extension are deprecated in favour of those of
 * the core (#154); they keep working until 5.0.
 */
#[CoversClass(ValidateXML::class)]
#[CoversClass(ValidateXMLFinisherOfTheExtension::class)]
#[CoversClass(XMLMapper::class)]
class XmlComponentsDeprecationTest extends TestCase
{
    /**
     * @var list<string>
     */
    protected array $deprecations = [];

    #[\Override]
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

    #[\Override]
    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
    }

    private function assertDeprecationNames(string $deprecated, string $replacement): void
    {
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString($deprecated . ' is deprecated since 4.0', $this->deprecations[0]);
        self::assertStringContainsString('Use ' . $replacement . '.', $this->deprecations[0]);
        self::assertStringContainsString(DeprecationUtility::XML_COMPONENTS_ISSUE_URL, $this->deprecations[0]);
    }

    #[Test]
    public function validateXmlPreProcessorTriggersDeprecationOnceAndStillValidates(): void
    {
        $subject = new ValidateXML(
            null,
            $this->createStub(ResourcePathConfigurationValidator::class),
            new MessageContainer()
        );
        $configuration = ['fields' => 'xml', 'identifier' => 'id'];
        $valid = ['id' => 1, 'xml' => '<course/>'];
        $invalid = ['id' => 2, 'xml' => '<course>'];

        self::assertTrue($subject->process($configuration, $valid));
        self::assertFalse($subject->process($configuration, $invalid));
        self::assertTrue($invalid[ValidateXML::KEY_VALIDATION_FAILED]);
        $this->assertDeprecationNames(ValidateXML::class, ValidateXMLPreProcessor::class);
    }

    #[Test]
    public function validateXmlFinisherTriggersDeprecationAndStillLogsMissingFile(): void
    {
        $messageContainer = new MessageContainer();
        $subject = new ValidateXMLFinisherOfTheExtension(
            null,
            $this->createStub(ResourcePathConfigurationValidator::class),
            $messageContainer
        );
        $records = [];
        $result = [];

        self::assertFalse($subject->process([], $records, $result));
        self::assertSame(1_508_914_547, $subject->getMessages()[0]->getId());
        $this->assertDeprecationNames(ValidateXMLFinisherOfTheExtension::class, ValidateXMLFinisher::class);
    }

    #[Test]
    public function xmlMapperTriggersDeprecationOnceAndStillMapsFields(): void
    {
        $subject = new XMLMapper();
        $configuration = ['fields' => ['id' => '@attribute']];
        $first = ['id' => 1, 'title' => 'First'];
        $second = ['id' => 2, 'title' => 'Second'];

        self::assertTrue($subject->process($configuration, $first));
        self::assertTrue($subject->process($configuration, $second));
        self::assertSame(['title' => 'First', '@attribute' => ['id' => 1]], $first);
        $this->assertDeprecationNames(XMLMapper::class, XMLMapperPreProcessor::class);
    }
}
