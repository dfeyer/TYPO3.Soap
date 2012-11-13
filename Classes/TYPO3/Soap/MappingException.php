<?php
namespace TYPO3\Soap;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Soap".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A mapping exception
 */
class MappingException extends \TYPO3\Flow\Exception {

	/**
	 * @var \TYPO3\Flow\Property\MappingResults
	 */
	protected $mappingResults;

	/**
	 * Constructs this exception
	 *
	 * @param string $message The exception message
	 * @param \TYPO3\Flow\Property\MappingResults $mappingResults
	 */
	public function __construct($message, \TYPO3\Flow\Property\MappingResults $mappingResults) {
		parent::__construct($message, '1288952996');
		$this->mappingResults = $mappingResults;
	}

	/**
	 * @return \TYPO3\Flow\Property\MappingResults
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}
}

?>