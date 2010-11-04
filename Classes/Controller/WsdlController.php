<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Controller;

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
 * A controller to serve static or generated WSDL
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class WsdlController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 *
	 * @param string $wsdlUri The WSDL URI part
	 */
	public function showAction($wsdlUri) {
		if (isset($this->settings['staticWsdlResources'][$wsdlUri])) {
			$this->response->setHeader('Content-type', 'application/xml');
			$wsdlContent = file_get_contents($this->settings['staticWsdlResources'][$wsdlUri]);
			$wsdlContent = str_replace('###BASE_URL###', $this->request->getBaseUri(), $wsdlContent);

			return $wsdlContent;
		} else {
			throw new \F3\FLOW3\Exception('WSDL URI not configured and dynamic WSDL generation not yet implemented', 1288877970);
		}
	}
}

?>