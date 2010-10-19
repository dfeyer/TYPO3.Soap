<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap;

/*                                                                        *
 * This script belongs to the FLOW3 package "ExtJS".                      *
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
class RequestHandler implements \F3\FLOW3\MVC\RequestHandlerInterface {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Handles a raw Ext Direct request and sends the respsonse.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$wsdlUri = NULL;
		$serverOptions = array(
			'soap_version' => SOAP_1_2,
			'encoding' => 'UTF-8',
			'uri' => (string)$this->environment->getBaseUri()
		);
		$soapServer = new \SoapServer($wsdlUri, $serverOptions);

		$service = $this->objectManager->get('F3\Test\Service\V1\CustomerService');
		$soapServer->setObject($service);
		
		$soapServer->handle($this->environment->getRawPostData());
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		if (!extension_loaded('soap') || $this->environment->getRequestMethod() !== 'POST' ) {
			return FALSE;
		}

		$url = substr($this->environment->getRequestUri(), strlen($this->environment->getBaseUri()));
		if ($url !== 'service/soap/v1/') {
			return FALSE;
		}
		return TRUE;
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