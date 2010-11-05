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
	 * The SoapServer will call methods with parameters as stdClass instances.
	 * This magic call method will convert the parameters to the object types
	 * specified in the SOAP service class.
	 *
	 * @param string $name Method name
	 * @param array $arguments Arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		$className = ($this->service instanceof \F3\FLOW3\AOP\ProxyInterface) ? $this->service->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($this->service);
		$methodParameters = $this->reflectionService->getMethodParameters($className, $name);
		foreach ($methodParameters as $parameterName => $parameterOptions) {
			if (isset($parameterOptions['class'])) {
				$className = $parameterOptions['class'];
				if ($this->reflectionService->isClassReflected($className)) {
					$arguments[$parameterOptions['position']] = $this->convertStdClassToObject($arguments[$parameterOptions['position']], $className, $parameterName);
				}
			} elseif (isset($parameterOptions['type'])) {
				$type = $parameterOptions['type'];
				if (preg_match('/^array<(.+)>$/', $type, $matches)) {
					$className = $matches[1];
					$typeName = strpos($className, '\\') !== FALSE ? substr($className, strrpos($className, '\\') + 1) : $className;
					$arrayValues = $arguments[$parameterOptions['position']]->$typeName;
					if ($this->reflectionService->isClassReflected($className)) {
						$result = array();
						foreach ($arrayValues as $arrayValue) {
							$result[] = $this->convertStdClassToObject($arrayValue, $className, $parameterName);
						}
					} else {
						$arguments[$parameterOptions['position']] = $arrayValues;
					}
				}
			}
		}
		return call_user_func_array(array($this->service, $name), $arguments);
	}

	/**
	 * Convert the given argument from stdClass to a FLOW3 object with the
	 * specified class name.
	 *
	 * @param \stdClass $argument The argument
	 * @param string $className The class name of the target object
	 * @param string $parameterName The parameter name of the argument
	 * @return object The converted object
	 */
	protected function convertStdClassToObject($argument, $className, $parameterName) {
		$target = $this->objectManager->create($className);
		if ($this->propertyMapper->map($this->reflectionService->getClassPropertyNames($className), \F3\FLOW3\Utility\Arrays::convertObjectToArray($argument), $target)) {
			return $target;
		} else {
			throw new \F3\Soap\MappingException('Could not map argument ' . $parameterName . ' to type ' . $className, $this->propertyMapper->getMappingResults());
		}
	}
}
?>