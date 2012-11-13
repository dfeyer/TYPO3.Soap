<?php
namespace TYPO3\Soap\Tests\Functional\Helper;

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
 * A test request builder
 */
class TestRequestBuilder extends \TYPO3\Soap\RequestBuilder {

	/**
	 * A prepared request
	 * @var \TYPO3\Soap\Request
	 */
	protected $request;

	/**
	 * @param string $wsdlUri
	 * @param string $serviceObjectName
	 * @param string $requestBody
	 */
	public function __construct($wsdlUri, $serviceObjectName, $requestBody) {
		$this->request = TestRequest::create(new \TYPO3\Flow\Http\Uri('http://localhost/soap'), 'POST');
		if (is_string($wsdlUri)) {
			$wsdlUri = new \TYPO3\Flow\Http\Uri($wsdlUri);
		}
		$this->request->setWsdlUri($wsdlUri);
		$this->request->setServiceObjectName($serviceObjectName);
		$this->request->setBody($requestBody);
	}

	/**
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return \TYPO3\Soap\Request
	 */
	public function build(\TYPO3\Flow\Http\Request $httpRequest) {
		return $this->request;
	}

	/**
	 * @return \TYPO3\Soap\Request
	 */
	public function getRequest() {
		return $this->request;
	}

}
?>