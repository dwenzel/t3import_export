<?php
namespace CPSIT\T3importExport;

/**
 * Interface RenderContentInterface
 *
 * Provides methods for rendering TypoScript content
 *
 * @package CPSIT\T3importExport
 */
interface RenderContentInterface
{
    /**
     * @param array $record
     * @param array $configuration
     * @return mixed Rendered Content
     */
    public function renderContent(array $record, array $configuration);
}
