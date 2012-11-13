<?php
namespace TYPO3\Soap;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Soap".                  *
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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Booting\Step;

/**
 * The SOAP request handler
 */
class RequestHandler implements \TYPO3\Flow\Core\RequestHandlerInterface {

	const HANDLEREQUEST_OK = 1;
	const HANDLEREQUEST_NOVALIDREQUEST = -1;

	const CANHANDLEREQUEST_OK = 1;
	const CANHANDLEREQUEST_MISSINGSOAPEXTENSION = -1;
	const CANHANDLEREQUEST_NOPOSTREQUEST = -2;
	const CANHANDLEREQUEST_WRONGSERVICEURI = -3;
	const CANHANDLEREQUEST_NOURIBASEPATH = -4;
	const CANHANDLEREQUEST_NOSOAPACTION = -5;

	/**
	 * @var \TYPO3\Soap\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $httpRequest;

	/**
	 * @var mixed
	 */
	protected $lastOperationResult;

	/**
	 * @var \Exception
	 */
	protected $lastCatchedException;

	/**
	 * @var \TYPO3\Flow\Mvc\RequestInterface
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	public function __construct(\TYPO3\Flow\Core\Bootstrap $bootstrap = NULL) {
		$this->bootstrap = $bootstrap;
		$this->httpRequest = \TYPO3\Flow\Http\Request::createFromEnvironment();
	}

	/**
	 * Handles a SOAP request and sends the response directly to the client.
	 *
	 * @return void
	 */
	public function handleRequest() {
		$sequence = $this->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);

		$this->objectManager = $this->bootstrap->getObjectManager();
		$request = $this->objectManager->get('TYPO3\Soap\RequestBuilder')->build($this->httpRequest);
		if ($request === FALSE) {
			header('HTTP/1.1 404 Not Found');
			echo 'Could not build request - probably no SOAP service matched the given endpoint URI.';
			return self::HANDLEREQUEST_NOVALIDREQUEST;
		}

		$securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
		$securityContext->setRequest($request->createActionRequest());

		$this->processRequest($request);

		$this->bootstrap->shutdown('Runtime');
	}

	/**
	 * Process a SOAP Request and invoke the SoapServer with a ServiceWrapper wrapping
	 * the SOAP service object.
	 *
	 * @param \TYPO3\Soap\Request $request
	 * @return void
	 */
	public function processRequest(Request $request) {
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
			throw $serviceWrapper->getCatchedException();
		}

		$this->lastOperationResult = $serviceWrapper->getLastOperationResult();
	}

	/**
	 * Builds a boot sequence for SOAP requests leaving out
	 * resource and session management.
	 *
	 * @return \TYPO3\Flow\Core\Booting\Sequence
	 */
	public function buildRuntimeSequence() {
		$sequence = $this->bootstrap->buildEssentialsSequence('runtime');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:proxyclasses', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeProxyClasses')), 'typo3.flow:systemlogger');
		$sequence->addStep(new Step('typo3.flow:classloader:cache', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeClassLoaderClassesCache')), 'typo3.flow:objectmanagement:proxyclasses');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:runtime', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeObjectManager')), 'typo3.flow:classloader:cache');

		if ($this->bootstrap->getContext() !== 'Production') {
			$sequence->addStep(new Step('typo3.flow:systemfilemonitor', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeSystemFileMonitor')), 'typo3.flow:objectmanagement:runtime');
			$sequence->addStep(new Step('typo3.flow:objectmanagement:recompile', array('TYPO3\Flow\Core\Booting\Scripts', 'recompileClasses')), 'typo3.flow:systemfilemonitor');
		}
		$sequence->addStep(new Step('typo3.flow:reflectionservice', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow:classloader:cache');
		$sequence->addStep(new Step('typo3.flow:persistence', array('TYPO3\Flow\Core\Booting\Scripts', 'initializePersistence')), 'typo3.flow:objectmanagement:runtime');
		return $sequence;
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 */
	public function canHandleRequest() {
		if (!extension_loaded('soap')) {
			return self::CANHANDLEREQUEST_MISSINGSOAPEXTENSION;
		}
		if ($this->httpRequest->getMethod() !== 'POST') {
			return self::CANHANDLEREQUEST_NOPOSTREQUEST;
		}
		if (!$this->httpRequest->getHeaders()->has('Soapaction')) {
			return self::CANHANDLEREQUEST_NOSOAPACTION;
		}
		return self::CANHANDLEREQUEST_OK;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler
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
	 * @return \TYPO3\Flow\Mvc\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 */
	public function setObjectManager($objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Override the HTTP request
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 */
	public function setHttpRequest($httpRequest) {
		$this->httpRequest = $httpRequest;
	}

}
?>