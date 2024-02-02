<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\T3importExport\Component\Finisher;
use CPSIT\T3importExport\Exception\MissingResourceException;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\LoggingTrait;
use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Resource\ResourceTrait;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use DOMDocument;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class ValidateXML
 *
 * Validates the XML of given fields
 * @todo this class is very similar to
 * @see Finisher\ValidateXML it might be better to merge them.
 */
class ValidateXML extends AbstractPreProcessor implements
    PreProcessorInterface,
    LoggingInterface
{
    use ResourceTrait,
        LoggingTrait;

    final public const KEY_FIELDS = 'fields';
    final public const KEY_IDENTIFIER = 'identifier';
    final public const KEY_SCHEMA = 'schema';
    final public const KEY_VALIDATION_FAILED = 'xmlValidationFailed';
    final public const DEFAULT_IDENTIFIER_FIELD = 'uid';

    /**
     * [
     *  <id> => ['errorTitle', 'errorDescription']
     * ]
     */
    final public const ERROR_CODES = [
        1_646_304_431 => ['Validation Error', 'XML ist invalid']
    ];
    final public const SEPARATOR = ',';
    final public const DEFAULT_XML_VERSION = '1.0';
    final public const DEFAULT_XML_ENCODING = 'utf-8';
    final public const MISSING_RESOURCE_MESSAGE = 'Resource for %s i empty or can not be loaded from file or url.';
    final public const MISSING_RESOURCE_CODE = 1_646_301_113;
    final public const TEMPLATE_ERROR_MESSAGE = 'Error validating content of field %s:
    Record ID %s
    Error Code (lib xml): %s
    Level: %s 
    Message: %s 
    Line: %s 
    Column: %s';
    protected DOMDocument $document;
    protected ResourcePathConfigurationValidator $pathConfigurationValidator;
    protected string $schema = '';

    public function __construct(
        DOMDocument $document = null,
        ResourcePathConfigurationValidator $pathConfigurationValidator = null,
        MessageContainer $messageContainer = null
    )
    {
        $this->document = $document ?? new DOMDocument(
                self::DEFAULT_XML_VERSION,
                self::DEFAULT_XML_ENCODING
            );

        $this->pathConfigurationValidator = $pathConfigurationValidator ?? GeneralUtility::makeInstance(
                ResourcePathConfigurationValidator::class
            );
        $this->messageContainer = $messageContainer ?? GeneralUtility::makeInstance(
                MessageContainer::class
            );
    }

    public function isConfigurationValid(array $configuration): bool
    {
        if (
            empty($configuration[self::KEY_FIELDS])
            || !is_string($configuration[self::KEY_FIELDS])
        ) {
            return false;
        }

        if (!empty($configuration[self::KEY_SCHEMA])) {
            $pathConfiguration = $configuration[self::KEY_SCHEMA];
            return $this->pathConfigurationValidator->isValid($pathConfiguration);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function process($configuration, &$record)
    {
        $fields = GeneralUtility::trimExplode(
            self::SEPARATOR,
            $configuration[self::KEY_FIELDS],
            true
        );

        if (!empty($configuration[self::KEY_SCHEMA])) {
            $schema = $this->loadResource($configuration[self::KEY_SCHEMA]);

            if (empty($schema)) {
                $message = sprintf(
                    self::MISSING_RESOURCE_MESSAGE,
                    self::KEY_SCHEMA
                );
                throw new MissingResourceException(
                    $message,
                    self::MISSING_RESOURCE_CODE
                );
            }

            $this->schema = $schema;
        }

        foreach ($fields as $field) {
            if (!isset($record[$field])) {
                // todo log error or throw exception
            }
            if (!$this->isValidXML($record, $field, $configuration)) {
                $record[self::KEY_VALIDATION_FAILED] = true;
                return false;
            }
        }

        return true;
    }

    protected function isValidXML(array $record, string $fieldName, array $configuration): bool
    {
        $xml = '';
        if ($record[$fieldName] instanceof DOMDocument) {
            $xml = $record[$fieldName]->saveXML();
        }
        if (is_string($record[$fieldName])) {
            $xml = $record[$fieldName];
        }

        $identifierField = $configuration[self::KEY_IDENTIFIER] ?? self::DEFAULT_IDENTIFIER_FIELD;
        if (trim($xml) === '') {
            return false;
        }

        // todo make sure identifier is set in record
        $identifier = $record[$identifierField];

        libxml_use_internal_errors(true);

        $this->document->loadXML($xml);

        if (!empty($this->schema)) {
            $this->document->schemaValidateSource($this->schema);
        }
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $message = sprintf(
                self::TEMPLATE_ERROR_MESSAGE,
                $fieldName,
                $identifier,
                $error->code,
                $error->level,
                $error->message,
                $error->line,
                $error->column
            );
            $this->logError(
                1_646_304_431,
                null,
                [$message]
            );
        }
        libxml_clear_errors();

        return empty($errors);
    }

}
