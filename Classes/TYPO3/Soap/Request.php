<?php
namespace TYPO3\Soap;

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
 * A SOAP request
 */
class Request extends \TYPO3\Flow\Http\Request {

	/**
	 * The fully qualified object name of the service this request refers to
	 *
	 * @var string
	 */
	protected $serviceObjectName;

	/**
	 * The base URI for this request - ie. the host and path leading to which all Flow URI paths are relative
	 *
	 * @var \TYPO3\Flow\Http\Uri
	 */
	protected $baseUri;

	/**
	 * URI pointing to the WSDL of the currently used service
	 *
	 * @var \TYPO3\Flow\Http\Uri
	 */
	protected $wsdlUri;

	/**
	 * SOAP XML body of this request. If none is set explicitly, HTTP raw POST data is used.
	 *
	 * @var string
	 */
	protected $body;

	/**
	 * @var array
	 */
	protected $soapHeaders;

	/**
	 * Sets the service object name
	 *
	 * @param string $serviceObjectName The fully qualified service object name
	 * @return void
	 */
	public function setServiceObjectName($serviceObjectName) {
		$this->serviceObjectName = $serviceObjectName;
	}

	/**
	 * Returns the service object name
	 *
	 * @return string
	 */
	public function getServiceObjectName() {
		return $this->serviceObjectName;
	}

	/**
	 * Sets the base URI
	 *
	 * @param \TYPO3\Flow\Http\Uri $baseUri
	 * @return void
	 */
	public function setBaseUri(\TYPO3\Flow\Http\Uri $baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return \TYPO3\Flow\Http\Uri URI of this web request
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the WSDL URI
	 *
	 * @param \TYPO3\Flow\Http\Uri $wsdlUri
	 * @return void
	 */
	public function setWsdlUri(\TYPO3\Flow\Http\Uri $wsdlUri) {
		$this->wsdlUri = $wsdlUri;
	}

	/**
	 * Returns the WSDL URI
	 *
	 * @return \TYPO3\Flow\Http\Uri URI pointing to the WSDL
	 */
	public function getWsdlUri() {
		return $this->wsdlUri;
	}

	/**
	 * Overrides the body of this request
	 *
	 * @param string $body The SOAP body XML
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * Returns the (SOAP) body of this request
	 *
	 * If it has been set via setBody(), the defined body is returned, otherwise
	 * this function uses the POST data sent by the client.
	 *
	 * @return string SOAP body
	 */
	public function getBody() {
		return isset($this->body) ? $this->body : $GLOBALS['HTTP_RAW_POST_DATA'];
	}

	/**
	 * Get SOAP headers that were sent in the request
	 *
	 * @return array
	 */
	public function getSoapHeaders() {
		return $this->soapHeaders;
	}

	/**
	 * Sets SOAP headers during handling of the SOAP message
	 *
	 * @param array $soapHeaders
	 * @return void
	 */
	public function setSoapHeaders($soapHeaders) {
		$this->soapHeaders = $soapHeaders;
	}

}
?>