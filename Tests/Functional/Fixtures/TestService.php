<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Tests\Functional\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 package "Soap".                       *
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
 * A sample service which is used for basic functional testing
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TestService {

	/**
	 * Responds with the given value
	 *
	 * @param string $value
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ping($value) {
		return $value;
	}

	/**
	 * Concatenate the name multiple times
	 *
	 * @param \F3\Soap\Tests\Functional\Fixtures\Dto $value
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function multiply(\F3\Soap\Tests\Functional\Fixtures\Dto $value) {
		$result = '';
		for ($i = 0; $i < $value->getSize(); $i++) {
			$result .= $value->getName();
		}
		return $result;
	}

	/**
	 * Concatenate the given names
	 *
	 * @param array<\F3\Soap\Tests\Functional\Fixtures\Dto> $values
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function concat(array $values) {
		$result = '';
		foreach ($values as $value) {
			$result .= $value->getName();
		}
		return $result;
	}

	/**
	 * Sum the numbers
	 *
	 * @param array<int> $values
	 * @return int
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function sum(array $values) {
		return array_sum($values);
	}
}
?>