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

/**
 * The SOAP request builder
 *
 * @FLOW3\Scope("singleton")
 */
class RequestBuilder {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var array
	 */
	protected $pathToObjectNameMapping;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;

		$this->pathToObjectNameMapping = array();
		if (isset($this->settings['mapping'])) {
			foreach ($this->settings['mapping'] as $objectName => $mapping) {
				$this->pathToObjectNameMapping[$mapping['path']] = $objectName;
			}
		}
	}

	/**
	 * Builds a new SOAP request
	 *
	 * Parses the endpoint URI found in the current HTTP request and resolves the
	 * responsible service object name accordingly.
	 *
	 * @return \TYPO3\Soap\Request The request object or FALSE if the service object name could not be resolved
	 */
	public function build() {
		$requestUri = $this->environment->getRequestUri();
		$baseUri = $this->environment->getBaseUri();

		$servicePath = $this->servicePathForRequestUri($requestUri);
		$serviceObjectName = $this->serviceObjectNameForServicePath($servicePath);

		$wsdlUri = clone $baseUri;
		$wsdlUri->setPath($requestUri->getPath() . '.wsdl');

		$request = new \TYPO3\Soap\Request();
		$request->setServiceObjectName($serviceObjectName);
		$request->setBaseUri($baseUri);
		$request->setWsdlUri($wsdlUri);
		return $request;
	}

	/**
	 * Get the path for the service definition from the request URI
	 *
	 * E.g. "http://host/service/soap/mypackage/v1/test" would return "mypackage/v1/test".
	 *
	 * @param string $requestUri
	 * @return string The endpoint path
	 */
	protected function servicePathForRequestUri($requestUri) {
		$servicePath = substr($requestUri, strpos($requestUri, $this->settings['endpointUriBasePath']) + strlen($this->settings['endpointUriBasePath']));
		if (substr_count($servicePath, '/') < 2) {
			throw new InvalidSoapRequestException('Request service path "' . $servicePath . '" is not a valid service endpoint path', 1320164802);
		}
		return $servicePath;
	}

	/**
	 * Get the service object name from a service path
	 *
	 * E.g. "mypackage/v1/test" would return "Mypackage\Service\Soap\V1\TestService" if the object name is registered.
	 *
	 * @param string $servicePath
	 * @return string The object name
	 */
	protected function serviceObjectNameForServicePath($servicePath) {
		if (isset($this->pathToObjectNameMapping[$servicePath])) {
			$serviceObjectNameCandidate = $this->pathToObjectNameMapping[$servicePath];
		} else {
			list($packageKey, $servicePath) = explode('/', $servicePath, 2);
			$servicePath = str_replace('/', '\\', $servicePath);
			$serviceObjectNameCandidate = sprintf("%s\Service\Soap\%sService", implode('\\', explode('.', $packageKey)), $servicePath);
		}

		$serviceObjectName = $this->objectManager->getCaseSensitiveObjectName($serviceObjectNameCandidate);
		if (!is_string($serviceObjectName)) {
			throw new InvalidSoapRequestException('Service object with name "' . $serviceObjectNameCandidate . '" not registered', 1320164740);
		}

		return $serviceObjectName;
	}

}
?>