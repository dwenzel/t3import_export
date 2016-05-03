<?php
namespace CPSIT\T3importExport;

/**
 * Interface IdentifiableInterface
 *
 * @package CPSIT\T3importExport\Persistence\Factory
 */
interface IdentifiableInterface {
	/**
	 * Sets the identifier
	 *
	 * @param string $identifier
	 * @return mixed
	 */
	public function setIdentifier($identifier);

	/**
	 * Gets the identifier
	 *
	 * @return string
	 */
	public function getIdentifier();
}
