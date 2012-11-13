<?php
namespace TYPO3\Soap\Tests\Functional\Helper;

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

use TYPO3\Flow\Annotations as Flow;

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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Simulate a SOAP request
	 *
	 * @param string $wsdlUri The URI of the WSDL (could be a file resource)
	 * @param string $serviceObjectName The name of the service object
	 * @param string $requestBody The request body (SOAP request)
	 * @return string The SOAP response
	 */
	public function sendSoapRequest($wsdlUri, $serviceObjectName, $requestBody) {
		$requestHandler = new \TYPO3\Soap\RequestHandler();
		$requestHandler->setObjectManager($this->objectManager);

		$testRequestBuilder = new TestRequestBuilder($wsdlUri, $serviceObjectName, $requestBody);
		$request = $testRequestBuilder->getRequest();

		$securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
		$securityContext->setRequest($request->createActionRequest());

		ob_start();

			// Suppress errors since headers might not be settable during a PHPUnit run
		@$requestHandler->processRequest($request);

		$response = ob_get_contents();
		ob_end_clean();

		$this->lastOperationResult = $requestHandler->getLastOperationResult();
		$this->lastCatchedException = $requestHandler->getLastCatchedException();

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