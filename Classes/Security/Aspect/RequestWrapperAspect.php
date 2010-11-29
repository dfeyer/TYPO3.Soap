<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Security\Aspect;

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
 * A security aspect for Soap to invoke security interceptors and initialize
 * the security framework before calling a service method. To allow the
 * setting of SOAP headers, it will advice the service wrapper and not the
 * request builder.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class RequestWrapperAspect {

	/**
	 * @var F3\FLOW3\Security\Context A reference to the security context
	 */
	protected $securityContext;

	/**
	 * Constructor
	 *
	 * @param F3\FLOW3\Security\Context $securityContext
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct(\F3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Advices the service wrapper to initialize the security framework
	 *
	 * @afterreturning method(F3\Soap\ServiceWrapper->initializeCall()) && setting(FLOW3.security.enable)
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function initializeSecurity(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$request = $joinPoint->getMethodArgument('request');
		$this->securityContext->initialize($request);
	}
 }
?>