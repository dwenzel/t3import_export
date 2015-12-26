<?php
namespace CPSIT\T3import;

/**
 * Interface IdentifiableInterface
 *
 * @package CPSIT\T3import\Persistence\Factory
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