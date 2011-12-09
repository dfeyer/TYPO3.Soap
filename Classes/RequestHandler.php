<?php
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
use TYPO3\FLOW3\Core\Booting\Step;

/**
 * The SOAP request handler
 */
class RequestHandler implements \TYPO3\FLOW3\Core\RequestHandlerInterface {

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
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

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
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 */
	public function __construct(\TYPO3\FLOW3\Core\Bootstrap $bootstrap = NULL) {
		$this->bootstrap = $bootstrap;
		if ($bootstrap !== NULL) {
			// TODO Use global environment or use HTTP request after refactoring
			$this->environment = new \TYPO3\FLOW3\Utility\Environment($bootstrap->getContext());
		}
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
		$request = $this->objectManager->get('TYPO3\Soap\RequestBuilder')->build();
		if ($request === FALSE) {
			header('HTTP/1.1 404 Not Found');
			echo 'Could not build request - probably no SOAP service matched the given endpoint URI.';
			return self::HANDLEREQUEST_NOVALIDREQUEST;
		}

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
	 * @return \TYPO3\FLOW3\Core\Booting\Sequence
	 */
	public function buildRuntimeSequence() {
		$sequence = $this->bootstrap->buildEssentialsSequence();
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:proxyclasses', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeProxyClasses')), 'typo3.flow3:systemlogger');
		$sequence->addStep(new Step('typo3.flow3:classloader:cache', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeClassLoaderClassesCache')), 'typo3.flow3:objectmanagement:proxyclasses');
		$sequence->addStep(new Step('typo3.flow3:reflectionservice', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow3:classloader:cache');
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:runtime', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeObjectManager')), 'typo3.flow3:reflectionservice');
		if ($this->bootstrap->getContext() !== 'Production') {
			$sequence->addStep(new Step('typo3.flow3:classfilemonitor', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeClassFileMonitor')), 'typo3.flow3:objectmanagement:runtime');
		}
		$sequence->addStep(new Step('typo3.flow3:persistence', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializePersistence')), 'typo3.flow3:objectmanagement:runtime');
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
		if ($this->environment->getRequestMethod() !== 'POST') {
			return self::CANHANDLEREQUEST_NOPOSTREQUEST;
		}
		$server = $this->environment->getRawServerEnvironment();
		if (!isset($server['HTTP_SOAPACTION'])) {
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
	 * @return \TYPO3\FLOW3\MVC\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 */
	public function setObjectManager($objectManager) {
		$this->objectManager = $objectManager;
	}

}
?>