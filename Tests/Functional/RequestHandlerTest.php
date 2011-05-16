<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Tests\Functional;

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
 * Testcase for the Soap Request Handler
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandlerTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function pingRespondsWithEcho() {
		$requestHandler = $this->objectManager->get('F3\Soap\RequestHandler');
		$mockRequestBuilder = $this->getMock('F3\Soap\RequestBuilder', array('build'));
		$requestHandler->injectRequestBuilder($mockRequestBuilder);

		$mockRequest = $this->getMock('F3\Soap\Request');
		$mockRequestBuilder->expects($this->any())->method('build')->will($this->returnValue($mockRequest));

		$mockRequest->expects($this->any())->method('getWsdlUri')->will($this->returnValue(__DIR__ . '/Fixtures/TestService.wsdl'));
		$mockRequest->expects($this->any())->method('getServiceObjectName')->will($this->returnValue('F3\Soap\Tests\Functional\Fixtures\TestService'));
		$mockRequest->expects($this->any())->method('getBody')->will($this->returnValue(\F3\FLOW3\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingRequest.xml', FILE_TEXT)));

		ob_start();
		$requestHandler->handleRequest();
		$response = ob_get_contents();
		ob_end_clean();

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/TestServicePingResponse.xml', $response);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function pingWithExceptionRespondsWithSoapFaultAndException() {
		$requestHandler = $this->objectManager->get('F3\Soap\RequestHandler');
		$mockRequestBuilder = $this->getMock('F3\Soap\RequestBuilder', array('build'));
		$requestHandler->injectRequestBuilder($mockRequestBuilder);

		$mockRequest = $this->getMock('F3\Soap\Request');
		$mockRequestBuilder->expects($this->any())->method('build')->will($this->returnValue($mockRequest));

		$mockRequest->expects($this->any())->method('getWsdlUri')->will($this->returnValue(__DIR__ . '/Fixtures/TestService.wsdl'));
		$mockRequest->expects($this->any())->method('getServiceObjectName')->will($this->returnValue('F3\Soap\Tests\Functional\Fixtures\TestService'));
		$mockRequest->expects($this->any())->method('getBody')->will($this->returnValue(\F3\FLOW3\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingWithExceptionRequest.xml', FILE_TEXT)));

		ob_start();
		try {
			$requestHandler->handleRequest();
			$this->fail('Request handler should throw exception in addition to SOAP fault response');
		} catch(\F3\FLOW3\Exception $e) {
			$this->assertEquals('Some exception occured', $e->getPrevious()->getMessage(), 'Handled exception should be rethrown');
		}
		$response = ob_get_contents();
		ob_end_clean();

		$this->assertStringStartsWith(\F3\FLOW3\Utility\Files::getFileContents(__DIR__ . '/Fixtures/TestServicePingWithExceptionResponse.xml'), $response);
	}

}
?>