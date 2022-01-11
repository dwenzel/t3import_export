<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ObjectManagerTrait;
use FluidTYPO3\Flux\Form\Field\DateTime;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataTargetXMLStream extends DataTargetFileStream implements DataTargetInterface, ConfigurableInterface
{
    const DEFAULT_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';
    const DEFAULT_ROOT_NODE = 'rows';

    const TEMPLATE_CONTENT_PLACEHOLDER = '{{CONTENT}}';

    /**
     * @var \XMLWriter
     */
    protected $writer;

    /**
     * @param array|\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array|null $configuration
     * @return void
     * @throws FileOperationErrorException
     */
    public function persist($object, array $configuration = null)
    {
        // init XML
        $this->initFileIfNotExist($configuration);
        // write object data into array
        parent::persist($object, $configuration);
    }

    /**
     * @param array|null $result
     * @param array|\Iterator|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {
        if (isset($this->writer)) {
            // close file

            if ($this->existTemplate($configuration)) {
                $this->writeXMLEndTemplateBased($configuration);
            } else {
                $this->writer->endElement();
            }


            // remove writer from memory and remove possible access locks from files
            $this->writer->flush();
            unset($this->writer);
        }

        parent::persistAll($result, $configuration);
    }

    /**
     * @param $buffer
     * @throws FileOperationErrorException
     */
    protected function writeBuffer($buffer)
    {
        if (isset($this->writer)) {
            $this->writer->writeRaw($buffer);
            // write stuff into output
            $this->writer->flush();
        }
    }

    /**
     * @param $configuration
     * @throws FileOperationErrorException
     */
    protected function initFileIfNotExist($configuration)
    {
        if (!isset($this->writer)) {
            $this->writer = new \XMLWriter();

            if (isset($configuration['output']) && $configuration['output'] === 'file') {
                $this->tempFile = $this->createAnonymTempFile();
            } else {
                $this->tempFile = 'php://output';
            }

            $this->writer->openUri($this->tempFile);

            if ($this->existTemplate($configuration)) {
                $this->writeXMLTemplateBased($configuration);
            } else {
                $this->writeXMLDynamically($configuration);
            }

            $this->writer->flush();
        }
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function writeXMLTemplateBased($configuration)
    {
        $content = $this->getAboveContentTemplate($configuration);
        $this->writer->writeRaw($content);
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function writeXMLEndTemplateBased($configuration)
    {
        $content = $this->getBelowContentTemplate($configuration);
        $this->writer->writeRaw($content);
    }

    /**
     * @param array $configuration
     * @return void
     */
    protected function writeXMLDynamically($configuration)
    {
        $this->writer->writeRaw($this->getFileHeader($configuration));
        $this->writer->startElement($this->getRootNodeName($configuration));

        if (isset($configuration['rootAttributes']) && is_array($configuration['rootAttributes'])) {
            foreach ($configuration['rootAttributes'] as $name => $value) {
                $this->writer->writeAttribute($name, $value);
            }
        }
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getAboveContentTemplate($configuration)
    {
        $entireTemplate = $this->loadTemplate($configuration);
        $subString = substr($entireTemplate, 0, strpos($entireTemplate, static::TEMPLATE_CONTENT_PLACEHOLDER));
        return $this->computePlaceholder($subString, $configuration);
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getBelowContentTemplate($configuration)
    {
        $entireTemplate = $this->loadTemplate($configuration);
        $belowOffset = strpos($entireTemplate, static::TEMPLATE_CONTENT_PLACEHOLDER) + strlen(static::TEMPLATE_CONTENT_PLACEHOLDER);
        $subString = substr($entireTemplate, $belowOffset);
        return $this->computePlaceholder($subString, $configuration);
    }

    /**
     * @param string $content
     * @param array $configuration
     * @return string
     */
    protected function computePlaceholder($content, $configuration)
    {
        if (!empty($configuration['templateReplace'])) {
            foreach ($configuration['templateReplace'] as $placeholder => $replacement) {
                $content = $this->replacePlaceholder($content, $placeholder, $replacement);
            }
        }

        return $content;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param string $replacement
     * @return string
     */
    protected function replacePlaceholder($haystack, $needle, $replacement)
    {
        $replacement = $this->replaceKeyReplacement($replacement);

        return preg_replace('/{{(' . $needle . ')}}/', $replacement, $haystack);
    }

    /**
     * @param string $content
     * @return string
     */
    protected function removeLeftoverPlaceholder($content)
    {
        return preg_replace('/{{(.+?)}}/', '', $content);
    }

    /**
     * @param $replacement
     * @return string
     */
    protected function replaceKeyReplacement($replacement)
    {
        if ($replacement === 'NOW') {
            $dt = new \DateTime();

            return $dt->format(DATE_W3C);
        }

        return $replacement;
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getAbsTemplatePath($configuration)
    {
        $path = '';
        if (!empty($configuration['template'])) {
            $path = GeneralUtility::getFileAbsFileName($configuration['template']);
        }

        return $path;
    }

    /**
     * @param array $configuration
     * @return bool
     */
    protected function existTemplate($configuration)
    {
        $path = $this->getAbsTemplatePath($configuration);
        if (!empty($path) && file_exists($path)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function loadTemplate($configuration)
    {
        if ($this->existTemplate($configuration)) {
            $path = $this->getAbsTemplatePath($configuration);

            return file_get_contents($path);
        }

        return '';
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getFileHeader($configuration = null)
    {
        $header = self::DEFAULT_HEADER;
        if (isset($configuration) && isset($configuration['header'])) {
            $header = $configuration['header'];
        }

        return $header;
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getRootNodeName($configuration = null)
    {
        $nodeName = self::DEFAULT_ROOT_NODE;
        if (isset($configuration) && isset($configuration['rootNodeName'])) {
            $nodeName = $configuration['rootNodeName'];
        }

        return $nodeName;
    }
}
