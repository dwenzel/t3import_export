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
     * @var \XMLReader
     */
    protected $xmlReader;


    /**
     * Error by id
     * <unique id> => ['Title', ['Message']
     * @var array
     */
    protected static $errors = [

    ];

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
        return static::$errors;
    }

    /**
     * Inject the XMLReader
     * @param \XMLReader $reader
     */
    public function injectXMLReader(\XMLReader $reader) {
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
        if (!$this->pathValidator->validate($configuration)) {
            return false;
        }
        if (isset($configuration['target']['schema'])
           && !is_string($configuration['target']['schema']) ) {
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
            return false;
        }
        libxml_use_internal_errors(true);

        //$this->xmlReader->XML($resource, null, LIBXML_DTDVALID);
        $this->xmlReader->XML($resource);
        $this->xmlReader->setParserProperty(\XMLReader::VALIDATE, true);

        if (!empty($configuration['schema']['file'])) {
            $schema = $this->getAbsoluteFilePath($configuration['schema']['file']);
            $this->xmlReader->setSchema($schema);
        }

        $this->xmlReader->read();
        $this->xmlReader->close();

        if (!$isValid = $this->xmlReader->isValid()) {
            $errors = libxml_get_errors();
        }
        //disable user error handling - will also clear any existing libxml errors
        libxml_use_internal_errors(false);

        return true;
    }
}
