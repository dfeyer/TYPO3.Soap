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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A logging aspect
 *
 * @FLOW3\Aspect
 */
class LoggingAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Advice for logging calls of the request handler's canHandleRequest() method.
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface
	 * @return void
	 * @FLOW3\After("method(TYPO3\Soap\RequestHandler->canHandleRequest())")
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logCanHandleRequestCalls(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		switch ($joinPoint->getResult()) {
			case \TYPO3\Soap\RequestHandler::CANHANDLEREQUEST_OK :
				$message = 'Detected HTTP POST request at valid endpoint URI.';
			break;
			case \TYPO3\Soap\RequestHandler::CANHANDLEREQUEST_MISSINGSOAPEXTENSION :
				$message = 'PHP SOAP extension not installed.';
			break;
			case \TYPO3\Soap\RequestHandler::CANHANDLEREQUEST_NOPOSTREQUEST :
				$message = 'Won\'t handle request because it is not HTTP POST.';
			break;
			case \TYPO3\Soap\RequestHandler::CANHANDLEREQUEST_WRONGSERVICEURI :
				$message = 'Won\'t handle request because it is not the expected endpoint URI.';
			break;
			default :
				$message = 'Unknown method result code (' . $joinPoint->getResult() . ')';
		}
		$this->systemLogger->log('canHandleRequest(): ' . $message, LOG_DEBUG);
	}

	/**
	 * Advice for logging handleRequest() calls
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface
	 * @return void
	 * @FLOW3\Before("method(TYPO3\Soap\RequestHandler->handleRequest())")
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logBeforeHandleRequestCalls(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$this->systemLogger->log('Handling SOAP request.', LOG_DEBUG);
	}

	/**
	 * Advice for logging handleRequest() calls
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface
	 * @return void
	 * @FLOW3\After("method(TYPO3\Soap\RequestHandler->handleRequest())")
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logAfterHandleRequestCalls(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getResult();
		if ($result instanceof \Exception) {
			$this->systemLogger->log('handleRequest() exited with exception:' . $result, LOG_ERR);
		} else {
			switch ($result) {
				case \TYPO3\Soap\RequestHandler::CANHANDLEREQUEST_OK :
					$this->systemLogger->log('handleRequest() exited successfully', LOG_DEBUG);
				break;
				case \TYPO3\Soap\RequestHandler::HANDLEREQUEST_NOVALIDREQUEST :
					$this->systemLogger->log('Could not build request - probably no SOAP service matched the given endpoint URI', LOG_NOTICE);
				break;
				default :
			}
		}
	}
}

?>
