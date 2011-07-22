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
 * A SOAP request
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Request implements \TYPO3\FLOW3\MVC\RequestInterface {

	/**
	 * The fully qualified object name of the service this request refers to
	 *
	 * @var string
	 */
	protected $serviceObjectName;

	/**
	 * The base URI for this request - ie. the host and path leading to which all FLOW3 URI paths are relative
	 *
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
	 */
	protected $baseUri;

	/**
	 * URI pointing to the WSDL of the currently used service
	 *
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
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
	 * If this request has been changed and needs to be dispatched again
	 *
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 * Sets the service object name
	 *
	 * @param string $serviceObjectName The fully qualified service object name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setServiceObjectName($serviceObjectName) {
		$this->serviceObjectName = $serviceObjectName;
	}

	/**
	 * Returns the service object name
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getServiceObjectName() {
		return $this->serviceObjectName;
	}

	/**
	 * Sets the base URI
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $baseUri
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseUri(\TYPO3\FLOW3\Property\DataType\Uri $baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the WSDL URI
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $wsdlUri
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWsdlUri(\TYPO3\FLOW3\Property\DataType\Uri $wsdlUri) {
		$this->wsdlUri = $wsdlUri;
	}

	/**
	 * Returns the WSDL URI
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI pointing to the WSDL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWsdlUri() {
		return $this->wsdlUri;
	}

	/**
	 * Overrides the body of this request
	 *
	 * @param string $body The SOAP body XML
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 * @api
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached successfully
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

}
?>