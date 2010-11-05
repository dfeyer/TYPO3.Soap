<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap;

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
 * A wrapper for services to map arguments
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ServiceWrapper {

	/**
	 * @var object
	 */
	protected $service;

	/**
	 * @inject
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @inject
	 * @var \F3\FLOW3\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param object $service The service to wrap
	 */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * @param string $name Method name
	 * @param array $arguments Arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		$className = ($this->service instanceof \F3\FLOW3\AOP\ProxyInterface) ? $this->service->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($this->service);
		$methodParameters = $this->reflectionService->getMethodParameters($className, $name);
		foreach ($methodParameters as $parameterName => $parameterOptions) {
			if (isset($parameterOptions['class']) && $this->reflectionService->isClassReflected($parameterOptions['class'])) {
				$argument = $arguments[$parameterOptions['position']];
				$target = $this->objectManager->create($parameterOptions['class']);
				if ($this->propertyMapper->map($this->reflectionService->getClassPropertyNames($parameterOptions['class']), \F3\FLOW3\Utility\Arrays::convertObjectToArray($argument), $target)) {
					$arguments[$parameterOptions['position']] = $target;
				} else {
					throw new \F3\Soap\MappingException('Could not map argument ' . $parameterName, $this->propertyMapper->getMappingResults());
				}
			}
		}
		return call_user_func_array(array($this->service, $name), $arguments);
	}
}

?>