<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Tests\Unit\Fixtures;

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
 * Simple service fixture
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MyService {

	/**
	 * Foo operation
	 *
	 * @param string $stringParameter
	 * @return string The string result
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function foo($stringParameter) {

	}

	/**
	 * Bar operation
	 *
	 * @param \F3\Soap\Tests\Unit\Fixtures\MyType $objectParameter
	 * @param array<integer> $arrayParameter
	 * @return array<string> Array of strings
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function bar(\F3\Soap\Tests\Unit\Fixtures\MyType $objectParameter, array $arrayParameter) {

	}

}
?>