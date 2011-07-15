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
 * The SOAP request builder
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestBuilder {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
	 * Builds a new SOAP request
	 *
	 * Parses the endpoint URI found in the current HTTP request and resolves the
	 * responsible service object name accordingly.
	 *
	 * @return \TYPO3\Soap\Request The request object or FALSE if the service object name could not be resolved
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$requestUri = $this->environment->getRequestUri();
		$endpointPath = substr($requestUri, strpos($requestUri, $this->settings['endpointUriBasePath']) + strlen($this->settings['endpointUriBasePath']));
		if (substr_count($endpointPath, '/') < 2) {
			return FALSE;
		}
		list($packageKey, $servicePath) = explode('/', $endpointPath, 2);
		$servicePath = str_replace('/', '\\', $servicePath);

		$serviceObjectName = sprintf("TYPO3\CouchDB\%s\Service\Soap\%sService", $packageKey, $servicePath);
		$serviceObjectName = $this->objectManager->getCaseSensitiveObjectName($serviceObjectName);
		if ($serviceObjectName === FALSE) {
			return FALSE;
		}

		$request = $this->objectManager->create('TYPO3\Soap\Request');
		$request->setServiceObjectName($serviceObjectName);
		$request->setBaseUri($this->environment->getBaseUri());
		$request->setWsdlUri(new \TYPO3\FLOW3\Property\DataType\Uri($requestUri . '.wsdl'));
		return $request;
	}
}

?>
