<?php
declare(ENCODING = 'utf-8');
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

/**
 * A helper to test SOAP requests
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SoapRequestHelper {

	/**
	 * @inject
	 * @var \TYPO3\Soap\RequestHandler
	 */
	protected $requestHandler;

	/**
	 * @var mixed
	 */
	protected $lastOperationResult;

	/**
	 * @var \Exception
	 */
	protected $lastCatchedException;

	/**
	 * Simulate a SOAP request
	 *
	 * @param string $wsdlUri
	 * @param string $serviceObjectName
	 * @param string $requestBody
	 * @return string The SOAP response
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function sendSoapRequest($wsdlUri, $serviceObjectName, $requestBody) {
		$requestHandler = clone $this->requestHandler;
		$testRequestBuilder = new TestRequestBuilder($wsdlUri, $serviceObjectName, $requestBody);
		$requestHandler->injectRequestBuilder($testRequestBuilder);

		ob_start();
		try {
			$requestHandler->handleRequest();
		} catch(\TYPO3\Soap\SoapFaultException $exception) {
			// Ignore SOAP fault exceptions
		}
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