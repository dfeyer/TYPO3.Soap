<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\Soap;

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
 * The SOAP request handler
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\FLOW3\MVC\RequestHandlerInterface {

	const HANDLEREQUEST_OK = 1;
	const HANDLEREQUEST_NOVALIDREQUEST = -1;

	const CANHANDLEREQUEST_OK = 1;
	const CANHANDLEREQUEST_MISSINGSOAPEXTENSION = -1;
	const CANHANDLEREQUEST_NOPOSTREQUEST = -2;
	const CANHANDLEREQUEST_WRONGSERVICEURI = -3;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Soap\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @param array $settings
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Soap\RequestBuilder $requestBuilder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRequestBuilder(\TYPO3\Soap\RequestBuilder $requestBuilder) {
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * Handles a SOAP request and sends the response directly to the clien.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		if ($request === FALSE) {
			header('HTTP/1.1 404 Not Found');
			echo 'Could not build request - probably no SOAP service matched the given endpoint URI.';
			return self::HANDLEREQUEST_NOVALIDREQUEST;
		}

		$serverOptions = array(
			'soap_version' => SOAP_1_2,
			'encoding' => 'UTF-8'
		);

		$soapServer = new \SoapServer((string)$request->getWsdlUri(), $serverOptions);
		$serviceObject = $this->objectManager->get($request->getServiceObjectName());
		$serviceWrapper = $this->objectManager->create('TYPO3\Soap\ServiceWrapper', $serviceObject);
		$serviceWrapper->setRequest($request);
		$soapServer->setObject($serviceWrapper);

		$soapServer->handle($request->getBody());
		if ($serviceWrapper->getCatchedException() !== NULL) {
			throw new \TYPO3\FLOW3\Exception('SOAP fault emitted', 1305541462, $serviceWrapper->getCatchedException());
		}

		return self::HANDLEREQUEST_OK;
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		if (!extension_loaded('soap')) {
			return self::CANHANDLEREQUEST_MISSINGSOAPEXTENSION;
		}
		if ($this->environment->getRequestMethod() !== 'POST' ) {
			return self::CANHANDLEREQUEST_NOPOSTREQUEST;
		}

		$uriString = substr($this->environment->getRequestUri(), strlen($this->environment->getBaseUri()));
		if (strpos($uriString, $this->settings['endpointUriBasePath']) !== 0) {
			return self::CANHANDLEREQUEST_WRONGSERVICEURI;
		}
		return self::CANHANDLEREQUEST_OK;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 200;
	}
}
?>