<?php
namespace TYPO3\Soap\Controller;

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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A controller to serve static or generated WSDL
 */
class WsdlController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Soap\WsdlGenerator
	 */
	protected $wsdlGenerator;

	/**
	 * @var array
	 */
	protected $pathToObjectNameMapping;

	/**
	 * Initialize mappings
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->pathToObjectNameMapping = array();
		if (isset($this->settings['mapping'])) {
			foreach ($this->settings['mapping'] as $objectName => $mapping) {
				$this->pathToObjectNameMapping[$mapping['path']] = $objectName;
			}
		}
	}

	/**
	 * Get the WSDL for a WSDL URI part (e.g. 'mypackage/someservicename').
	 * The WSDL will be generated using reflection on the service class or it
	 * will use the configured file from staticWsdlResources.
	 *
	 * The endpoint will be dynamically changed to the current base URL by using
	 * the {baseUrl} marker inside the file.
	 *
	 * @param string $wsdlUri The WSDL URI part
	 * @return string
	 * @todo Implement functional test for route handler and this action
	 */
	public function showAction($wsdlUri) {
		if (isset($this->settings['staticWsdlResources'][$wsdlUri])) {
			$wsdlContent = file_get_contents($this->settings['staticWsdlResources'][$wsdlUri]);
		} else {
			if (isset($this->pathToObjectNameMapping[$wsdlUri])) {
				$serviceObjectName = $this->pathToObjectNameMapping[$wsdlUri];
			} else {
				list($packageKey, $servicePath) = explode('/', $wsdlUri, 2);
				$servicePath = str_replace('/', '\\', $servicePath);

				$serviceObjectName = sprintf("%s\Service\Soap\%sService", implode('\\', explode('.', $packageKey)), $servicePath);
			}
			$serviceObjectName = $this->objectManager->getCaseSensitiveObjectName($serviceObjectName);

			if ($serviceObjectName === FALSE) {
				$this->response->setStatus(404);
				return '404 Not Found (No WSDL resource found at this URI)';
			}

			$wsdlContent = $this->wsdlGenerator->generateWsdl($serviceObjectName);
		}

		$this->response->setHeader('Content-type', 'application/xml');
		$wsdlContent = str_replace('{baseUrl}', $this->request->getHttpRequest()->getBaseUri(), $wsdlContent);
		return $wsdlContent;
	}
}

?>