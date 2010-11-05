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
 * Dynamic WSDL Generator using reflection
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class WsdlGenerator {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @inject
	 * @var \F3\Fluid\Core\Parser\TemplateParser
	 */
	protected $templateParser;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $complexTypes;

	/**
	 * Map of PHP types to schema types
	 *
	 * @var array
	 */
	protected $typeMapping = array(
		'string' => 'xsd:string',
		'boolean' => 'xsd:boolean',
		'int' => 'xsd:integer',
		'float' => 'xsd:float'
	);

	/**
	 * @var array
	 */
	protected $messages;

	/**
	 * @var array
	 */
	protected $operations;

	/**
	 * Inject the settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Generate a WSDL file by reflecting the class methods and used types
	 *
	 * @param string $className
	 * @return string The WSDL XML
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateWsdl($className) {
		if (!preg_match('/Service$/', $className)) {
			throw new \F3\FLOW3\Exception('SOAP service class must end with "Service"', 1288984414);
		}
		$this->complexTypes = array();
		$this->messages = array();
		$this->operations = array();

		$serviceName = substr($className, strrpos($className, '\\') + 1);
		$servicePath = $this->getServicePath($className);

		$this->reflectOperations($className);

		return $this->renderTemplate('resource://Soap/Private/Templates/definitions.xml', array(
			'messages' => $this->messages,
			'complexTypes' => $this->complexTypes,
			'operations' => $this->operations,
			'serviceName' => $serviceName,
			'servicePath' => $servicePath
		));
	}

	/**
	 * Get the URI path of the service class
	 *
	 * @param string $className The service class name
	 * @return string The URI path
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function getServicePath($className) {
		$classParts = explode('\\', $className);
		$classParts[count($classParts) - 1] = substr($classParts[count($classParts) - 1], 0, -7);
		return $this->settings['endpointUriBasePath'] . strtolower($classParts[1] . '/' . implode('/', array_slice($classParts, 4)));
	}

	/**
	 * Reflects methods (operations) of the given class and sets
	 * information in the generator.
	 *
	 * @param string $className
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function reflectOperations($className) {
		$methodNames = $this->reflectionService->getClassMethodNames($className);
		foreach ($methodNames as $methodName) {
			if (!$this->reflectionService->isMethodPublic($className, $methodName)) continue;
			$this->operations[$methodName] = array(
				'name' => $methodName,
				'documentation' => $methodName
			);

			$messageName = $methodName . 'Request';
			$this->messages[$messageName] = array(
				'name' => $messageName,
				'parts' => array()
			);
			$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
			foreach ($methodParameters as $parameterName => $methodParameter) {
				$this->messages[$messageName]['parts'][$parameterName] = array(
					'name' => $parameterName,
					'type' => $this->getOrCreateType($methodParameter['type'])
				);
			}
			$returnType = $this->getMethodReturnType($className, $methodName);

			$messageName = $methodName . 'Response';
			$this->messages[$messageName] = array(
				'name' => $messageName,
				'parts' => array(
					'returnValue' => array(
						'name' => 'returnValue',
						'type' => $this->getOrCreateType($returnType)
					)
				)
			);
		}
	}

	/**
	 * Get the method return type
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return string The PHP type
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function getMethodReturnType($className, $methodName) {
		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['return']) && isset($methodTagsValues['return'][0])) {
			list($returnType) = \F3\FLOW3\Utility\Arrays::trimExplode(' ', $methodTagsValues['return'][0], TRUE);
			return $returnType;
		} else {
			throw new \F3\FLOW3\Exception('Could not get return value for ' . $className . '#' . $methodName, 1288984174);
		}
	}

	/**
	 * Get or create a schema type from a PHP type
	 *
	 * @param string $phpType The PHP type
	 * @return string The namespace prefixed schema type
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function getOrCreateType($phpType) {
		if (isset($this->typeMapping[$phpType])) {
			return $this->typeMapping[$phpType];
		}
		if (preg_match('/^array<(.+)>$/', $phpType, $matches)) {
			$typeName = strpos($matches[1], '\\') !== FALSE ? substr($matches[1], strrpos($matches[1], '\\') + 1) : $matches[1];
			$arrayTypeName = 'ArrayOf' . ucfirst($typeName);
			$this->typeMapping[$phpType] = 'tns:' . $arrayTypeName;
			$this->complexTypes[$arrayTypeName] = array(
				'name' => $arrayTypeName,
				'elements' => array(
					array(
						'name' => lcfirst($typeName),
						'type' => $this->getOrCreateType($matches[1]),
						'attributes' => 'maxOccurs="unbounded" '
					)
				)
			);
		} elseif (strpos($phpType, '\\') !== FALSE) {
			$typeName = substr($phpType, strrpos($phpType, '\\') + 1);
			$this->typeMapping[$phpType] = 'tns:' . $typeName;
			$this->complexTypes[$typeName] = array(
				'name' => $typeName,
				'elements' => array()
			);
			$methodNames = $this->reflectionService->getClassMethodNames($phpType);
			foreach ($methodNames as $methodName) {
				if (strpos($methodName, 'get') === 0 && $this->reflectionService->isMethodPublic($phpType, $methodName)) {
					$propertyName = lcfirst(substr($methodName, 3));

					$returnType = $this->getMethodReturnType($phpType, $methodName);
					$this->complexTypes[$typeName]['elements'][$propertyName] = array(
						'name' => $propertyName,
						'type' => $this->getOrCreateType($returnType)
					);
				}
			}
		} else {
			throw new \F3\FLOW3\Exception('Type ' . $phpType . ' not supported', 1288979369);
		}
		return $this->typeMapping[$phpType];
	}

	/**
	 * Render the given template file with the given variables
	 *
	 * @param string $templatePathAndFilename
	 * @param array $contextVariables
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function renderTemplate($templatePathAndFilename, array $contextVariables) {
		$templateSource = \F3\FLOW3\Utility\Files::getFileContents($templatePathAndFilename, FILE_TEXT);
		if ($templateSource === FALSE) {
			throw new \F3\Fluid\Core\Exception('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
		}
		$parsedTemplate = $this->templateParser->parse($templateSource);

		$renderingContext = $this->buildRenderingContext($contextVariables);

		return $parsedTemplate->render($renderingContext);
	}

	/**
	 * Build the rendering context
	 *
	 * @param array $contextVariables
	 * @return \F3\Fluid\Core\Rendering\RenderingContextInterface
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function buildRenderingContext(array $contextVariables) {
		$renderingContext = $this->objectManager->create('F3\Fluid\Core\Rendering\RenderingContextInterface');

		$renderingContext->injectTemplateVariableContainer($this->objectManager->create('F3\Fluid\Core\ViewHelper\TemplateVariableContainer', $contextVariables));
		$renderingContext->injectViewHelperVariableContainer($this->objectManager->create('F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer'));

		return $renderingContext;
	}
}

?>