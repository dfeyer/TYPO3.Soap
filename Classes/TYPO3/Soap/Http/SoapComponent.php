<?php
namespace TYPO3\Soap\Http;

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
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Soap\Request as SoapRequest;
use TYPO3\Flow\Mvc\DispatchComponent;
use TYPO3\Soap\RequestBuilder;
use TYPO3\Soap\ServiceWrapper;
use TYPO3\Soap\SoapServer;

/**
 * Soap Request Handling
 */
class SoapComponent extends DispatchComponent {

	const CANHANDLEREQUEST_OK = 1;
	const CANHANDLEREQUEST_MISSINGSOAPEXTENSION = -1;
	const CANHANDLEREQUEST_NOPOSTREQUEST = -2;
	const CANHANDLEREQUEST_WRONGSERVICEURI = -3;
	const CANHANDLEREQUEST_NOURIBASEPATH = -4;
	const CANHANDLEREQUEST_NOSOAPACTION = -5;

	/**
	 * @Flow\Inject
	 * @var RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @Flow\Inject(setting="soapResponseContentType")
	 * @var string
	 */
	protected $responseContentType;

	/**
	 * @param ComponentContext $componentContext
	 * @return void
	 */
	public function handle(ComponentContext $componentContext) {
		$httpRequest = $componentContext->getHttpRequest();
		if ($this->canHandleRequest($httpRequest) !== self::CANHANDLEREQUEST_OK) {
			return;
		}

		$request = $this->requestBuilder->build($httpRequest);
		$responseContent = $this->processRequest($request);

		$response = $componentContext->getHttpResponse();
		$response->setHeader('Content-Type', $this->responseContentType, TRUE);
		$response->setContent($responseContent);

		$componentContext->setParameter('TYPO3\Flow\Http\Component\ComponentChain', 'cancel', TRUE);
	}

	/**
	 * Process a SOAP Request and invoke the SoapServer with a ServiceWrapper wrapping
	 * the SOAP service object.
	 *
	 * @param SoapRequest $request
	 * @throws \Exception
	 * @return string
	 */
	public function processRequest(SoapRequest $request) {
		$serverOptions = array(
			'soap_version' => SOAP_1_2,
			'encoding' => 'UTF-8',
			'cache_wsdl' => WSDL_CACHE_MEMORY
		);

		/** @var $actionRequest ActionRequest */
		$actionRequest = $this->objectManager->get('TYPO3\Flow\Mvc\ActionRequest', $request);
		$this->securityContext->setRequest($actionRequest);

		$soapServer = new SoapServer((string)$request->getWsdlUri(), $serverOptions);

		$serviceObject = $this->objectManager->get($request->getServiceObjectName());

		$serviceWrapper = new ServiceWrapper($serviceObject);
		$serviceWrapper->setRequest($request);

		$soapServer->setObject($serviceWrapper);
		$response = $soapServer->handle($request->getBody());

		$this->objectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->persistAll();

		return $response;
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @param Request $request
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 */
	public function canHandleRequest(Request $request) {
		if (!extension_loaded('soap')) {
			return self::CANHANDLEREQUEST_MISSINGSOAPEXTENSION;
		}
		if ($request->getMethod() !== 'POST') {
			return self::CANHANDLEREQUEST_NOPOSTREQUEST;
		}
		if (!$request->getHeaders()->has('Soapaction')) {
			return self::CANHANDLEREQUEST_NOSOAPACTION;
		}
		return self::CANHANDLEREQUEST_OK;
	}

}