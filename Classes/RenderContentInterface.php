<?php
namespace CPSIT\T3import;

/**
 * Interface RenderContentInterface
 *
 * Provides methods for rendering TypoScript content
 *
 * @package CPSIT\T3import
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
