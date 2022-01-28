<?php

namespace CPSIT\T3importExport\Component\Finisher;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
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

use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\LoggingTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;

/**
 * Class ValidateXML
 */
class ValidateXML extends AbstractFinisher
    implements FinisherInterface, LoggingInterface
{
    use ResourceTrait, LoggingTrait;

    /**
     * Notice by id
     * <unique id> => ['Title', ['Message']
     */
    const NOTICE_CODES = [
        1508776068 => ['Validation failed', 'XML is invalid. There %1s %d %2s.'],
        1508914030 => ['Validation succeed', 'XML is valid.'],
    ];

    /**
     * Error by id
     * <unique id> => ['Title', ['Message']
     */
    const ERROR_CODES = [
        1508774170 => ['Invalid type for target schema', 'config[\'target\'][\'schema\'] must be a string, %s given.'],
        1508914547 => ['Empty resource', 'Could not load resource or resource empty'],
    ];

    /**
     * @var \XMLReader
     */
    protected $xmlReader;

    /**
     * Returns error codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['errorTitle', 'errorDescription']
     * ]
     * 'errorDescription' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getErrorCodes()
    {
        return static::ERROR_CODES;
    }

    /**
     * Returns notice codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['title', 'description']
     * ]
     * 'description' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getNoticeCodes()
    {
        return static::NOTICE_CODES;
    }

    /**
     * Inject the XMLReader
     * @param \XMLReader $reader
     */
    public function injectXMLReader(\XMLReader $reader)
    {
        $this->xmlReader = $reader;
    }

    /**
     * Tells whether a given configuration is valid
     * Override this method in order to perform validation of
     * configuration
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!$this->pathValidator->isValid($configuration)) {
            return false;
        }
        if (isset($configuration['target']['schema'])
            && !is_string($configuration['target']['schema'])) {
            $this->logError(1508774170, [gettype($configuration['target']['schema'])]);
            return false;
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @param array $result Array with result records
     * @return bool
     */
    public function process($configuration, &$records, &$result)
    {

        $resource = $this->loadResource($configuration);
        if (empty($resource)) {
            $this->logError(1508914547, null, [$configuration]);

            return false;
        }
        libxml_use_internal_errors(true);

        $this->xmlReader->XML($resource, null, LIBXML_DTDVALID);
        $this->xmlReader->setParserProperty(\XMLReader::VALIDATE, true);

        if (!empty($configuration['schema']['file'])) {
            $schema = $this->getAbsoluteFilePath($configuration['schema']['file']);
            $this->xmlReader->setSchema($schema);
        }

        $this->xmlReader->read();
        $this->xmlReader->close();

        if (!$isValid = $this->xmlReader->isValid()) {
            $validationErrors = libxml_get_errors();
            $errorCount = count($validationErrors);
            $string1 = ($errorCount > 1)? 'were' : 'was';
            $string2 = ($errorCount > 1)? 'errors' : 'error';
            $this->logNotice(1508776068, [$string1, $errorCount, $string2], $validationErrors);
        } else {
            // notification about validation success
            $this->logNotice(1508914030);
        }
        //disable user error handling - will also clear any existing libxml errors
        libxml_use_internal_errors(false);


        return true;
    }
}
