<?php
namespace TYPO3\Soap\Tests\Functional\Fixtures;

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
 * A wrapper for DTOs
 */
class DtoWrapper {

	/**
	 * @var array<\TYPO3\Soap\Tests\Functional\Fixtures\Dto>
	 */
	protected $dtos = array();

	/**
	 * @return array<\TYPO3\Soap\Tests\Functional\Fixtures\Dto>
	 */
	public function getDtos() {
		return $this->dtos;
	}

	/**
	 * @param array<\TYPO3\Soap\Tests\Functional\Fixtures\Dto> $dtos
	 * @return void
	 */
	public function setDtos(array $dtos) {
		$this->dtos = $dtos;
	}

}
?>