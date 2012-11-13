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
 * Testcase for the Soap Request Handler
 */
class RequestHandlerTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\Soap\Tests\Functional\Helper\SoapRequestHelper
	 */
	protected $soapRequestHelper;

	/**
	 * Set up test dependencies
	 */
	public function setUp() {
		parent::setUp();
		$this->soapRequestHelper = new Helper\SoapRequestHelper();
	}

	/**
	 * @test
	 */
	public function pingRespondsWithEcho() {
		$response = $this->soapRequestHelper->sendSoapRequest(
			__DIR__ . '/Fixtures/TestService.wsdl',
			'TYPO3\Soap\Tests\Functional\Fixtures\TestService',
			\TYPO3\Flow\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingRequest.xml', FILE_TEXT)
		);
		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/TestServicePingResponse.xml', $response);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Exception
	 */
	public function pingWithExceptionRespondsWithSoapFaultAndException() {
		$response = $this->soapRequestHelper->sendSoapRequest(
			__DIR__ . '/Fixtures/TestService.wsdl',
			'TYPO3\Soap\Tests\Functional\Fixtures\TestService',
			\TYPO3\Flow\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingWithExceptionRequest.xml', FILE_TEXT)
		);
		$this->assertStringStartsWith(\TYPO3\Flow\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingWithExceptionResponse.xml'), $response);
	}

}
?>