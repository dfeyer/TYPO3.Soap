<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\Soap\Security\Aspect;

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
 * A security aspect for Soap to invoke security interceptors and initialize
 * the security framework before calling a service method. To allow the
 * setting of SOAP headers, it will advice the service wrapper and not the
 * request builder.
 *
 * @FLOW3\Aspect
 */
class RequestWrapperAspect {

	/**
	 * @var TYPO3\FLOW3\Security\Context A reference to the security context
	 */
	protected $securityContext;

	/**
	 * Constructor
	 *
	 * @param TYPO3\FLOW3\Security\Context $securityContext
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct(\TYPO3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Advices the service wrapper to initialize the security framework
	 *
	 * @FLOW3\AfterReturning("method(TYPO3\Soap\ServiceWrapper->initializeCall()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function initializeSecurity(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$this->securityContext->clearContext();
	}

}
?>