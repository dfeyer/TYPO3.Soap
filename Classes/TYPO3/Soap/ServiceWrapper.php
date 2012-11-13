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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A wrapper for services to map arguments and handle exceptions in a
 * SoapServer friendly way.
 */
class ServiceWrapper {

	/**
	 * The wrapped service object
	 *
	 * @var object
	 */
	protected $service;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\Soap\Request
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Store any exception that occured during the service execution
	 * @var \Exception
	 */
	protected $catchedException;

	/**
	 * Store the result of an operation for later inspection
	 * @var mixed
	 */
	protected $lastOperationResult;

	/**
	 * Inject the reflection service
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Inject the property mapper
	 *
	 * @param \TYPO3\Flow\Property\PropertyMapper $propertyMapper
	 * @return void
	 */
	public function injectPropertyMapper(\TYPO3\Flow\Property\PropertyMapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Inject the object manager
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param object $service The service object to wrap
	 */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 * The SoapServer will call methods with parameters as stdClass instances.
	 * This magic call method will convert the parameters to the object types
	 * specified in the SOAP service class.
	 *
	 * @param string $methodName Method name called
	 * @param object $arguments Arguments of the call
	 * @return mixed
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 9) === 'Flow_AOP') return;
		if (!$this->request instanceof \TYPO3\Soap\Request) throw new \TYPO3\Flow\Exception('No SOAP request set', 1297091911);
		$this->lastOperationResult = NULL;
		$this->initializeCall($this->request);
		$className = get_class($this->service);
		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		try {
			foreach ($methodParameters as $parameterName => $parameterOptions) {
				if (isset($parameterOptions['class'])) {
					if ($this->reflectionService->isClassReflected($parameterOptions['class'])) {
						$arguments[$parameterOptions['position']] = $this->convertStdClassToObject($arguments[$parameterOptions['position']], $parameterOptions['class'], $parameterName);
					}
				} elseif ($parameterOptions['array']) {
					$arguments[$parameterOptions['position']] = $this->convertArrayArgument($arguments[$parameterOptions['position']], $methodName, $parameterName, $parameterOptions['type']);
				}
			}
			$this->lastOperationResult = call_user_func_array(array($this->service, $methodName), $arguments);
			return $this->lastOperationResult;
		} catch(\Exception $exception) {
			$this->handleException($exception, $className, $methodName);
		}
	}

	/**
	 * Convert an array argument from a SOAP value (stdObject with type name
	 * holding the array) to an array for the service.
	 *
	 * @param mixed $argument
	 * @param string $methodName
	 * @param string $parameterName
	 * @param string $parameterType
	 * @return array
	 */
	protected function convertArrayArgument($argument, $methodName, $parameterName, $parameterType) {
		if (preg_match('/^array<(.+)>$/', $parameterType, $matches)) {
			$className = trim($matches[1], '\\');
			$typeName = lcfirst(strpos($className, '\\') !== FALSE ? substr($className, strrpos($className, '\\') + 1) : $className);
			if (!isset($argument->$typeName)) {
				return array();
			}
			$arrayValues = $argument->$typeName;
			if (!is_array($arrayValues)) {
				$arrayValues = array($arrayValues);
			}
			if ($this->reflectionService->isClassReflected($className)) {
				$result = array();
				foreach ($arrayValues as $arrayValue) {
					$result[] = $this->convertStdClassToObject($arrayValue, $className, $parameterName);
				}
				return $result;
			} else {
				return $arrayValues;
			}
		} else {
			throw new \TYPO3\Flow\Exception('Could not parse array type for parameter ' . $parameterName . ' from type "' . $parameterType . '"', 1297166416);
		}
	}

	/**
	 * AOP method template to intercept a SOAP request and
	 * set headers before initializing security
	 *
	 * @param \TYPO3\Soap\Request $request
	 * @return void
	 */
	protected function initializeCall(\TYPO3\Soap\Request $request) {}

	/**
	 * Sets SOAP headers from the <headers> SOAP header. Will be indirectly
	 * called from RequestHandler by SoapServer->handle().
	 *
	 * Custom SOAP headers should be nested in a <headers></headers> element.
	 *
	 * @param object $arguments
	 * @return void
	 */
	public function headers($arguments) {
		$headers = \TYPO3\Flow\Utility\Arrays::convertObjectToArray($arguments);
		$this->request->setSoapHeaders($headers);
	}

	/**
	 * Convert the thrown exception to a corresponding SOAP fault,
	 * respecting expected and unexpected Exceptions by looking at the
	 * throws annotation of the method declaration.
	 *
	 * @param \Exception $exception The exception that was thrown in the service call
	 * @param string $className The class name of the service
	 * @param string $methodName The method name of the service that was called
	 * @return void
	 * @throws \SoapFault The exception converted to a SoapFault
	 */
	protected function handleException($exception, $className, $methodName) {
		$this->catchedException = $exception;
		$exceptionClassName = get_class($exception);
		if ($exception instanceof \TYPO3\Flow\Security\Exception\AuthenticationRequiredException) {
			throw new \SoapFault('Client', 'Authentication required', NULL, 'Security_AuthenticationRequired');
		}
		if ($exception instanceof \TYPO3\Flow\Security\Exception\AccessDeniedException) {
			throw new \SoapFault('Client', 'Access denied', NULL, 'Security_AccessDenied');
		}
		$expectedException = $this->methodThrowsException($className, $methodName, $exceptionClassName);
		if ($expectedException) {
			$exceptionName = implode('_', array_slice(explode('\\', $exceptionClassName), 4));
			throw new \SoapFault('Client', $exception->getMessage(), NULL, $exceptionName);
		} else {
			if (!$exception instanceof \TYPO3\Flow\Exception) {
				$exception = new \TYPO3\Flow\Exception($exception->getMessage(), $exception->getCode(), $exception);
			}
			$identifier = $exception->getReferenceCode();
			if ($this->settings['exposeExceptionInformation'] === TRUE) {
				$message = $exceptionClassName . ' (' . $exception->getCode() . '): ' . $exception->getMessage();
				$stackTrace = $exception->getTraceAsString();
				$details = $stackTrace;
			} else {
				// TODO Move gethostname to Environment
				$message = 'Internal server error. The error was logged as ' . $identifier . ' on ' . gethostname() . '.';
				$details = $identifier;
			}
			if ($this->settings['logDetailedExceptions'] === TRUE) {
				$this->logException($exception, $identifier);
			}

			throw new \SoapFault('Server', $message, NULL, $details);
		}
	}

	/**
	 * Check if the given method throws the specified exception in a
	 * "throws" annotation.
	 *
	 * @param string $className The class name for the method
	 * @param string $methodName The method name
	 * @param string $exceptionClassName The exception class name
	 * @return boolean
	 */
	protected function methodThrowsException($className, $methodName, $exceptionClassName) {
		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['throws'])) {
			if (is_array($methodTagsValues['throws'])) {
				foreach ($methodTagsValues['throws'] as $throwsDefinition) {
					list($throwsType,) = \TYPO3\Flow\Utility\Arrays::trimExplode(' ', $throwsDefinition, TRUE);
					if (ltrim($exceptionClassName, '\\') == ltrim($throwsType, '\\')) {
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Logs the given exception through the system logger which
	 * should log an exception file with details.
	 *
	 * @param \Exception $exception The exception object
	 * @param string $identifier
	 * @return void
	 */
	public function logException(\Exception $exception, $identifier = NULL) {
		if (is_object($this->systemLogger)) {
			$this->systemLogger->logException($exception);
		}
	}

	/**
	 * Convert the given argument from stdClass to a Flow object with the
	 * specified class name. XML arrays with duplicated type as property name
	 * are converted to an array that is supported by the property mapper.
	 *
	 * @param \stdClass $argument The argument
	 * @param string $className The class name of the target object
	 * @param string $parameterName The parameter name of the argument
	 * @return object The converted object
	 */
	protected function convertStdClassToObject($argument, $className, $parameterName) {
		$source = \TYPO3\Flow\Utility\Arrays::convertObjectToArray($argument);

		foreach ($source as $propertyName => $propertyValue) {
			$annotation = $this->getMethodReturnAnnotation($className, 'get' . ucfirst($propertyName));
			$propertyType = $annotation['type'];
			if (preg_match('/^array<(.+)>$/', $propertyType, $matches)) {
				$source[$propertyName] = $this->convertArrayArgument($argument->$propertyName, '', $propertyName, $propertyType);
			}
		}

		$target = $this->propertyMapper->convert($source, $className);
		if ($target !== NULL) {
			return $target;
		} else {
			throw new \TYPO3\Soap\MappingException('Could not map argument ' . $parameterName . ' to type ' . $className, $this->propertyMapper->getMessages());
		}
	}

	/**
	 * Get the return type and description of a method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return array The method return type and description
	 */
	protected function getMethodReturnAnnotation($className, $methodName) {
		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['return']) && isset($methodTagsValues['return'][0])) {
			$returnAnnotations = explode(' ', $methodTagsValues['return'][0], 2);
			return array(
				'type' => $returnAnnotations[0],
				'description' => count($returnAnnotations) > 1 ? $returnAnnotations[1] : NULL
			);
		} else {
			throw new \TYPO3\Flow\Exception('Could not get return value for ' . $className . '::' . $methodName, 1305130721);
		}
	}

	/**
	 * Set the current SOAP request
	 *
	 * @param \TYPO3\Soap\Request $request
	 * @return void
	 */
	public function setRequest($request) {
		$this->request = $request;
	}

	/**
	 * Get any exception that occured during the service execution
	 *
	 * @return \Exception
	 */
	public function getCatchedException() {
		return $this->catchedException;
	}

	/**
	 * Get the result of the last operation
	 *
	 * @return mixed
	 */
	public function getLastOperationResult() {
		return $this->lastOperationResult;
	}

}
?>