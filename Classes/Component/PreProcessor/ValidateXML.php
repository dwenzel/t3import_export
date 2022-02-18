<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

use CPSIT\T3importExport\LoggingTrait;
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
 */
class ValidateXML extends AbstractPreProcessor implements PreProcessorInterface
{
    use LoggingTrait;

    public const KEY_FIELDS = 'fields';
    public const SEPARATOR = ',';
    public const DEFAULT_XML_VERSION = '1.0';
    public const DEFAULT_XML_ENCODING = 'utf-8';
    public const TEMPLATE_ERROR_MESSAGE = 'Error validating content of field %s:
    Level: %s 
    Message: %s 
    Line: %s 
    Column: %s';
    protected DOMDocument $document;

    public function __construct(DOMDocument $document = null)
    {
        $this->document = $document ?? new DOMDocument(
                self::DEFAULT_XML_VERSION,
                self::DEFAULT_XML_ENCODING
            );
    }

    public function isConfigurationValid(array $configuration): bool
    {
        /** @noinspection IfReturnReturnSimplificationInspection */
        if (
            empty($configuration[self::KEY_FIELDS])
            || !is_string($configuration[self::KEY_FIELDS])
        ) {
            return false;
        }

        // todo allow file and url as alternative sources, make errorhandling configurable (log, skipRecord, throw Exception)

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

        foreach ($fields as $field)
        {
            if(!isset($record[$field])) {
                // todo log error or throw exception
            }

            if (!$this->isValidXML($record[$field], $field))
            {
                return false;
            }
        }

        return true;
    }

    protected function isValidXML(string $xml, $fieldName): bool
    {
        if (trim($xml) === '') {
            return false;
        }

        libxml_use_internal_errors(true);

        $this->document->loadXML($xml);

        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $message = sprintf(
                self::TEMPLATE_ERROR_MESSAGE,
                $fieldName,
                $error->level,
                $error->message,
                $error->line,
                $error->column
            );
            $this->logError(
                $error->code,
                $message
            );
        }
        libxml_clear_errors();

        return empty($errors);
    }

}
