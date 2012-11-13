<?php
namespace TYPO3\Soap\Routing;

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

/**
 * A route part handler for WSDL "files"
 */
class WsdlRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Return the value as URI
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}

		$endpointUriBasePath = $this->settings['endpointUriBasePath'];
		$pattern = '/^' . \preg_quote($endpointUriBasePath, '/') . '(.+)\.wsdl$/';
		if (preg_match($pattern, $value, $matches) !== 1) {
			return FALSE;
		}

		$this->value = $matches[1];
		return TRUE;
	}

	/**
	 * This route part handler tries to match the full route path.
	 *
	 * @param string $routePath The current route path
	 * @return string
	 */
	protected function findValueToMatch($routePath) {
		return $routePath;
	}

}

?>