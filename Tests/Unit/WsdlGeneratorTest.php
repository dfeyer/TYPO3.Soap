<?php
namespace TYPO3\Soap\Tests\Unit;

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
 * Unit test for WsdlGenerator
 */
class WsdlGeneratorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Exception
	 */
	public function generateWsdlAcceptsOnlyClassNamesWithServiceSuffix() {
		$wsdlGenerator = new \TYPO3\Soap\WsdlGenerator();
		$wsdlGenerator->generateWsdl('MyRandomClass');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Exception
	 */
	public function generateWsdlAcceptsOnlyReflectedClassNames() {
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->will($this->returnValue(FALSE));

		$wsdlGenerator = new \TYPO3\Soap\WsdlGenerator();
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'reflectionService', $mockReflectionService, TRUE);

		$wsdlGenerator->generateWsdl('TYPO3\Soap\Tests\Unit\Fixtures\MyUnknownService');
	}

	/**
	 * @test
	 */
	public function generateWsdlReflectsOperationsAndRendersTemplate() {
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getPackageKeyByObjectName')->will($this->returnValue('TYPO3.Soap'));

		$wsdlGenerator = $this->getMock('TYPO3\Soap\WsdlGenerator', array('reflectOperations', 'renderTemplate'));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'reflectionService', $mockReflectionService, TRUE);
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'objectManager', $mockObjectManager, TRUE);
		$wsdlGenerator->injectSettings(array(
			'endpointUriBasePath' => 'service/soap/',
			'generatorTemplate' => 'resource://TYPO3.Soap/Private/Templates/Definitions.xml'
		));

		$wsdlGenerator->expects($this->once())->method('reflectOperations')->will($this->returnValue(array(
			'messages' => array('foo'),
			'complexTypes' => array('bar'),
			'operations' => array('baz')
		)));
		$wsdlGenerator->expects($this->once())->method('renderTemplate')->with('resource://TYPO3.Soap/Private/Templates/Definitions.xml', array(
			'messages' => array('foo'),
			'complexTypes' => array('bar'),
			'operations' => array('baz'),
			'serviceName' => 'MyService',
			'servicePath' => 'service/soap/typo3.soap/tests/unit/fixtures/my',
			'namespace' => 'http://tempuri.org/service/soap/typo3.soap/tests/unit/fixtures/my'
		))->will($this->returnValue('<WSDL>'));
		$result = $wsdlGenerator->generateWsdl('TYPO3\Soap\Tests\Unit\Fixtures\MyService');
		$this->assertEquals('<WSDL>', $result);
	}

	/**
	 * @test
	 */
	public function reflectOperationsCollectsPublicMethodsAsOperations() {
		$mockReflectionService = $this->buildMockReflectionServiceForTestService();

		$wsdlGenerator = new \TYPO3\Soap\WsdlGenerator();
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'reflectionService', $mockReflectionService, TRUE);

		$schema = $wsdlGenerator->reflectOperations('TYPO3\Soap\Tests\Unit\Fixtures\MyService');
		$this->assertEquals(array(
			'bar' => array(
				'name' => 'bar',
				'documentation' => 'Bar operation'
			),
			'foo' => array(
				'name' => 'foo',
				'documentation' => 'Foo operation'
			)
		), $schema['operations']);
	}

	/**
	 * @test
	 */
	public function reflectOperationsCollectsOperationRequestResponseMessagesAndMapsTypes() {
		$mockReflectionService = $this->buildMockReflectionServiceForTestService();

		$wsdlGenerator = $this->getMock('TYPO3\Soap\WsdlGenerator', array('dummy'));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'reflectionService', $mockReflectionService, TRUE);

		$schema = $wsdlGenerator->reflectOperations('TYPO3\Soap\Tests\Unit\Fixtures\MyService');
		$this->assertEquals(array(
			'barRequest' => array(
				'name' => 'barRequest',
				'parts' => array(
					'objectParameter' => array(
						'name' => 'objectParameter',
						'type' => 'tns:MyType',
						'documentation' => NULL
					),
					'arrayParameter' => array(
						'name' => 'arrayParameter',
						'type' => 'tns:ArrayOfInteger',
						'documentation' => NULL
					)
				)
			),
			'barResponse' => array(
				'name' => 'barResponse',
				'parts' => array(
					'returnValue' => array(
						'name' => 'returnValue',
						'type' => 'tns:ArrayOfString',
						'documentation' => 'Array of strings'
					)
				)
			),
			'fooRequest' => array(
				'name' => 'fooRequest',
				'parts' => array(
					'stringParameter' => array(
						'name' => 'stringParameter',
						'type' => 'xsd:string',
						'documentation' => NULL
					)
				)
			),
			'fooResponse' => array(
				'name' => 'fooResponse',
				'parts' => array(
					'returnValue' => array(
						'name' => 'returnValue',
						'type' => 'xsd:string',
						'documentation' => 'The string result'
					)
				)
			)
		), $schema['messages']);
	}

	/**
	 * @test
	 */
	public function reflectOperationsCollectsComplexTypesForOperation() {
		$mockReflectionService = $this->buildMockReflectionServiceForTestService();

		$wsdlGenerator = $this->getMock('TYPO3\Soap\WsdlGenerator', array('dummy'));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($wsdlGenerator, 'reflectionService', $mockReflectionService, TRUE);

		$schema = $wsdlGenerator->reflectOperations('TYPO3\Soap\Tests\Unit\Fixtures\MyService');
		$this->assertEquals(array(
			'ArrayOfInteger' => array(
				'name' => 'ArrayOfInteger',
				'elements' => array(
					array(
						'name' => 'integer',
						'type' => 'xsd:integer',
						'attributes' => 'maxOccurs="unbounded" '
					)
				)
			),
			'ArrayOfString' => array(
				'name' => 'ArrayOfString',
				'elements' => array(
					array(
						'name' => 'string',
						'type' => 'xsd:string',
						'attributes' => 'maxOccurs="unbounded" '
					)
				)
			),
			'MyType' => array(
				'name' => 'MyType',
				'elements' => array(
					'name' => array(
						'name' => 'name',
						'type' => 'xsd:string',
						'attributes' => 'minOccurs="0" maxOccurs="1" ',
						'documentation' => 'Some name'
					)
				),
				'documentation' => 'Simple type fixture'
			)
		), $schema['complexTypes']);
	}

	/**
	 *
	 * @return \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected function buildMockReflectionServiceForTestService() {
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->any())->method('getClassMethodNames')->will($this->returnCallback(
			function($className) {
				switch ($className) {
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyService':
						return array('foo', 'bar', 'injectBaz', 'someProtected');
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyType':
						return array('getName', 'setName', 'doSomething', 'someProtected');
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('isMethodPublic')->will($this->returnCallback(
			function($className, $methodName) {
				switch ($className) {
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyService':
						switch ($methodName) {
							case 'foo':
							case 'bar':
							case 'injectBaz':
								return TRUE;
							default:
								return FALSE;
						}
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyType':
						switch ($methodName) {
							case 'getName':
							case 'setName':
							case 'doSomething':
								return TRUE;
							default:
								return FALSE;
						}
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnCallback(
			function($className, $methodName) {
				switch ($methodName) {
					case 'foo':
						return array(
							'stringParameter' => array(
								'position' => 0,
								'byReference' => FALSE,
								'array' => FALSE,
								'optional' => FALSE,
								'allowsNull' => TRUE,
								'class' => NULL,
								'type' => 'string'
							)
						);
					case 'bar':
						return array(
							'objectParameter' => array(
								'position' => 0,
								'byReference' => FALSE,
								'array' => FALSE,
								'optional' => FALSE,
								'allowsNull' => FALSE,
								'class' => 'TYPO3\Soap\Tests\Unit\Fixtures\MyType',
								'type' => 'TYPO3\Soap\Tests\Unit\Fixtures\MyType'
							),
							'arrayParameter' => array(
								'position' => 1,
								'byReference' => FALSE,
								'array' => TRUE,
								'optional' => FALSE,
								'allowsNull' => FALSE,
								'class' => NULL,
								'type' => 'array<integer>'
							)
						);
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('getMethodTagsValues')->will($this->returnCallback(
			function($className, $methodName) {
				switch ($className) {
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyService':
						switch ($methodName) {
							case 'foo':
								return array (
									'param' => array('string $stringParameter'),
									'return' => array('string The string result'),
									'author' => array('Christopher Hlubek <hlubek@networkteam.com>')
								);
							case 'bar':
								return array (
									'param' => array('\TYPO3\Soap\Tests\Unit\Fixtures\MyType $objectParameter', 'array<integer> $arrayParameter'),
									'return' => array('array<string> Array of strings'),
									'author' => array('Christopher Hlubek <hlubek@networkteam.com>')
								);
						}
					case 'TYPO3\Soap\Tests\Unit\Fixtures\MyType':
						switch ($methodName) {
							case 'getName':
								return array (
									'return' => array('string Some name'),
									'author' => array('Christopher Hlubek <hlubek@networkteam.com>')
								);
						}
				}
			}
		));
		return $mockReflectionService;
	}

}
?>