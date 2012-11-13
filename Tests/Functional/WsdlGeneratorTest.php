<?php
namespace TYPO3\Soap\Tests\Functional;

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
 * Testcase for the WSDL generator
 */
class WsdlGeneratorTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function wsdlForSimpleServiceIsCorrect() {
		$wsdlGenerator = $this->objectManager->get('TYPO3\Soap\WsdlGenerator');

		$wsdl = $wsdlGenerator->generateWsdl('TYPO3\Soap\Tests\Functional\Fixtures\TestService');

		$wsdlFixture = \TYPO3\Flow\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestService.wsdl', FILE_TEXT);

			// Clean whitespace and linebreaks for better comparison and diff
		$wsdl = preg_replace('/>\\s*</', ">\n<", $wsdl);
		$wsdlFixture = preg_replace('/>\\s*</', ">\n<", $wsdlFixture);

		$this->assertEquals($wsdlFixture, $wsdl);
	}

	// TODO Add test for path and namespace mapping

}
?>