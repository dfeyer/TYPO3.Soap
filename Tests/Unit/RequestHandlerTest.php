<?php
namespace TYPO3\Soap\Tests\Unit;

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
 * Unit test for RequestHandler
 */
class RequestHandlerTest extends \TYPO3\Flow\Tests\UnitTestCase {

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
	public function canHandleHandlesPostRequestWithSoapactionHeader() {
		$mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', FALSE);
		$httpRequest = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://request-host/service/soap/test'), 'POST', array(), array(), array(), $server);
		$httpRequest->setHeader('Soapaction', 'Foo');

		$requestHandler = new \TYPO3\Soap\RequestHandler();
		$requestHandler->setHttpRequest($httpRequest);

		$result = $requestHandler->canHandleRequest();

		$this->assertGreaterThan(0, $result);
	}

}
?>