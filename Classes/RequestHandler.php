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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The SOAP request handler
 *
 * @FLOW3\Scope("singleton")
 */
class RequestHandler implements \TYPO3\FLOW3\MVC\RequestHandlerInterface {

	const HANDLEREQUEST_OK = 1;
	const HANDLEREQUEST_NOVALIDREQUEST = -1;

	const CANHANDLEREQUEST_OK = 1;
	const CANHANDLEREQUEST_MISSINGSOAPEXTENSION = -1;
	const CANHANDLEREQUEST_NOPOSTREQUEST = -2;
	const CANHANDLEREQUEST_WRONGSERVICEURI = -3;
	const CANHANDLEREQUEST_NOURIBASEPATH = -4;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Soap\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var mixed
	 */
	protected $lastOperationResult;

	/**
	 * @var \Exception
	 */
	protected $lastCatchedException;

	/**
	 * @var \TYPO3\FLOW3\MVC\RequestInterface
	 */
	protected $request;

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
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Handles a SOAP request and sends the response directly to the client.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		if ($request === FALSE) {
			header('HTTP/1.1 404 Not Found');
			echo 'Could not build request - probably no SOAP service matched the given endpoint URI.';
			return self::HANDLEREQUEST_NOVALIDREQUEST;
		}

		$this->request = $request;

		$this->lastOperationResult = NULL;
		$this->lastCatchedException = NULL;

		$serverOptions = array('soap_version' => SOAP_1_2, 'encoding' => 'UTF-8');
		$soapServer = new \SoapServer((string)$request->getWsdlUri(), $serverOptions);
		$serviceObject = $this->objectManager->get($request->getServiceObjectName());
		$serviceWrapper = new \TYPO3\Soap\ServiceWrapper($serviceObject);
		$serviceWrapper->setRequest($request);
		$soapServer->setObject($serviceWrapper);

		$soapServer->handle($request->getBody());
		if ($serviceWrapper->getCatchedException() !== NULL) {
			$this->lastCatchedException = $serviceWrapper->getCatchedException();
			throw new SoapFaultException('SOAP fault emitted', 1305541462, $serviceWrapper->getCatchedException());
		}

		$this->lastOperationResult = $serviceWrapper->getLastOperationResult();

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
		if ($this->environment->getRequestMethod() !== 'POST') {
			return self::CANHANDLEREQUEST_NOPOSTREQUEST;
		}
		if (!isset($this->settings['endpointUriBasePath'])) {
			return self::CANHANDLEREQUEST_NOURIBASEPATH;
		}

		$requestUriPath = $this->environment->getRequestUri()->getPath();
		$requestUriPath = ltrim($requestUriPath, '/');
		$baseUriPath = ltrim($this->settings['endpointUriBasePath']);
		if (strpos($requestUriPath, $baseUriPath) !== 0) {
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

	/**
	 * Get the result of the last operation
	 *
	 * Could be used in functional tests.
	 *
	 * @return mixed
	 */
	public function getLastOperationResult() {
		return $this->lastOperationResult;
	}

	/**
	 * Get the last catched exception
	 *
	 * Could be used in functional tests.
	 *
	 * @return \Exception
	 */
	public function getLastCatchedException() {
		return $this->lastCatchedException;
	}

	/**
	 * @return \TYPO3\FLOW3\MVC\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

}
?>