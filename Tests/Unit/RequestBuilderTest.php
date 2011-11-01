<?php
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

use TYPO3\FLOW3\Reflection\ObjectAccess;

/**
 * Unit test for RequestBuilder
 */
class RequestBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function buildGetsServiceObjectNameFromUrl() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$requestBuilder = new \TYPO3\Soap\RequestBuilder();
		ObjectAccess::setProperty($requestBuilder, 'environment', $mockEnvironment, TRUE);
		ObjectAccess::setProperty($requestBuilder, 'objectManager', $mockObjectManager, TRUE);

		$settings = array(
			'endpointUriBasePath' => 'service/soap/'
		);
		$requestBuilder->injectSettings($settings);

		$mockEnvironment->expects($this->any())->method('getRequestUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('http://request-host/service/soap/testing/v1/test')));
		$mockEnvironment->expects($this->any())->method('getBaseUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('https://very-different/')));

		$mockObjectManager->expects($this->atLeastOnce())->method('getCaseSensitiveObjectName')->with('testing\Service\Soap\v1\testService')->will($this->returnValue('Testing\Service\Soap\V1\TestService'));

		$request = $requestBuilder->build();

		$this->assertEquals('Testing\Service\Soap\V1\TestService', $request->getServiceObjectName());
	}

	/**
	 * @test
	 */
	public function buildUsesBaseUrlForWsdlUri() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$requestBuilder = new \TYPO3\Soap\RequestBuilder();
		ObjectAccess::setProperty($requestBuilder, 'environment', $mockEnvironment, TRUE);
		ObjectAccess::setProperty($requestBuilder, 'objectManager', $mockObjectManager, TRUE);

		$settings = array(
			'endpointUriBasePath' => 'service/soap/'
		);
		$requestBuilder->injectSettings($settings);

		$mockEnvironment->expects($this->any())->method('getRequestUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('http://request-host/service/soap/testing/v1/test')));
		$mockEnvironment->expects($this->any())->method('getBaseUri')->will($this->returnValue(new \TYPO3\FLOW3\Property\DataType\Uri('https://very-different/')));
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('testing\Service\Soap\v1\testService')->will($this->returnValue('Testing\Service\Soap\V1\TestService'));

		$request = $requestBuilder->build();

		$this->assertEquals('https://very-different/service/soap/testing/v1/test.wsdl', (string)$request->getWsdlUri(), 'WSDL URI should use base URI');
		$this->assertEquals('https://very-different/', (string)$request->getBaseUri(), 'Base URI should not be modified');
	}

}
?>