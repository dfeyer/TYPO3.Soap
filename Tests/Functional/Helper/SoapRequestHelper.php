<?php
namespace TYPO3\Soap\Tests\Functional\Helper;

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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A helper to test SOAP requests
 */
class SoapRequestHelper {

	/**
	 * @var mixed
	 */
	protected $lastOperationResult;

	/**
	 * @var \Exception
	 */
	protected $lastCatchedException;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Simulate a SOAP request
	 *
	 * @param string $wsdlUri The URI of the WSDL (could be a file resource)
	 * @param string $serviceObjectName The name of the service object
	 * @param string $requestBody The request body (SOAP request)
	 * @param \TYPO3\FLOW3\Tests\FunctionalTestRequestHandler $testRequestHandler The current test request handler for global object mangling
	 * @return string The SOAP response
	 */
	public function sendSoapRequest($wsdlUri, $serviceObjectName, $requestBody, \TYPO3\FLOW3\Tests\FunctionalTestRequestHandler $testRequestHandler = NULL) {
		$requestHandler = new \TYPO3\Soap\RequestHandler();
		$requestHandler->setObjectManager($this->objectManager);

		$testRequestBuilder = new TestRequestBuilder($wsdlUri, $serviceObjectName, $requestBody);
		$request = $testRequestBuilder->getRequest();

		if ($testRequestHandler !== NULL) {
			$previousRequest = $testRequestHandler->getRequest();
			$testRequestHandler->setRequest($request);
		}

		ob_start();

			// Suppress errors since headers might not be settable during a PHPUnit run
		@$requestHandler->processRequest($request);

		$response = ob_get_contents();
		ob_end_clean();

		$this->lastOperationResult = $requestHandler->getLastOperationResult();
		$this->lastCatchedException = $requestHandler->getLastCatchedException();

		if ($testRequestHandler !== NULL) {
			$testRequestHandler->setRequest($previousRequest);
		}

		return $response;
	}

	/**
	 * @return mixed
	 */
	public function getLastOperationResult() {
		return $this->lastOperationResult;
	}

	/**
	 * @return \Exception
	 */
	public function getLastCatchedException() {
		return $this->lastCatchedException;
	}

}
?>