<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\Soap\Tests\Unit;

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
 * Unit test for RequestHandler
 */
class RequestHandlerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Check if the PHP soap extension was loaded
	 */
	protected function setUp() {
		parent::setUp();

		if (!extension_loaded('soap')) {
			$this->markTestSkipped('Test does not run without SOAP extension');
		}
	}

	/**
	 * @test
	 */
	public function canHandleDoesNotRelyOnBaseUrl() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$requestHandler = new \TYPO3\Soap\RequestHandler();
		$requestHandler->injectEnvironment($mockEnvironment);

		$settings = array(
			'endpointUriBasePath' => 'service/soap'
		);
		$requestHandler->injectSettings($settings);

		$mockEnvironment->expects($this->any())->method('getRequestMethod')->will($this->returnValue('POST'));
		$mockEnvironment->expects($this->any())->method('getRequestUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('http://request-host/service/soap/test')));
		$mockEnvironment->expects($this->any())->method('getBaseUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('https://very-different/')));

		$result = $requestHandler->canHandleRequest();

		$this->assertGreaterThan(0, $result);
	}

}
?>