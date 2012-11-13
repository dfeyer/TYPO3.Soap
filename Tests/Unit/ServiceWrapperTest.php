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
 * Unit test for ServiceWrapper
 */
class ServiceWrapperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var object
	 */
	protected $mockService;

	/**
	 * @var \TYPO3\Soap\Request
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $mockPropertyMapper;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\Soap\ServiceWrapper
	 */
	protected $serviceWrapper;

	/**
	 * Set up test dependencies
	 *
	 */
	public function setUp() {
		$serviceClassName = 'Test' . uniqid() . 'Service';
		eval('
			class ' . $serviceClassName . ' {
				public function hello($name) {
					return "Hello " . $name;
				}
				public function helloObject($name) {
					return "Hello " . $name->getValue();
				}
				public function sum(array $values) {
					return array_sum($values);
				}
				public function sumNumbers(array $numbers) {
					$sum = 0;
					foreach ($numbers as $number) {
						$sum += $number->getValue();
					}
					return $sum;
				}
			}
		');
		$this->mockService = $this->getMock($serviceClassName, array('dummy'));
		$this->mockRequest = $this->getMock('TYPO3\Soap\Request', array(), array(), '', FALSE);
		$this->mockReflectionService = $this->buildMockReflectionServiceForTestService();
		$this->mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');
		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->serviceWrapper = $this->getMock('TYPO3\Soap\ServiceWrapper', array('initializeCall', 'convertStdClassToObject'), array($this->mockService));
		$this->serviceWrapper->injectReflectionService($this->mockReflectionService);
		$this->serviceWrapper->injectPropertyMapper($this->mockPropertyMapper);
		$this->serviceWrapper->injectObjectManager($this->mockObjectManager);
		$this->serviceWrapper->setRequest($this->mockRequest);
	}

	/**
	 * @test
	 */
	public function methodCallInitializesRequest() {
		$this->serviceWrapper->expects($this->once())->method('initializeCall')->with($this->mockRequest);
		$this->serviceWrapper->hello('World');
	}

	/**
	 * @test
	 */
	public function methodCallInvokesServiceMethod() {
		$result = $this->serviceWrapper->hello('World');
		$this->assertEquals('Hello World', $result);
	}

	/**
	 * @test
	 */
	public function methodCallConvertsObjectValue() {
		$testClassName = uniqid('TestObject');
		eval('
			class ' . $testClassName . ' {
				protected $value;
				public function getValue() { return $this->value; }
				public function setValue($value) { $this->value = $value; }
			}
		');
		$this->serviceWrapper->expects($this->once())->method('convertStdClassToObject')->will($this->returnCallback(
			function($argument, $className, $parameterName) use ($testClassName) {
				switch ($className) {
					case 'TestObject':
						$number = new $testClassName();
						$number->setValue($argument->value);
						return $number;
				}
			}
		));
		$argument = (object)array('value' => 'World');
		$result = $this->serviceWrapper->helloObject($argument);
		$this->assertEquals('Hello World', $result);
	}

	/**
	 * @test
	 */
	public function methodCallConvertsSimpleArrayValues() {
		$argument = (object)array(
			'int' => array(1, 2, 3, 4)
		);
		$result = $this->serviceWrapper->sum($argument);
		$this->assertEquals(10, $result);
	}

	/**
	 * @test
	 */
	public function methodCallConvertsObjectArrayValues() {
		$testClassName = uniqid('TestObject');
		eval('
			class ' . $testClassName . ' {
				protected $value;
				public function getValue() { return $this->value; }
				public function setValue($value) { $this->value = $value; }
			}
		');
		$this->serviceWrapper->expects($this->exactly(4))->method('convertStdClassToObject')->will($this->returnCallback(
			function($argument, $className, $parameterName) use ($testClassName) {
				switch ($className) {
					case 'TestObject':
						$number = new $testClassName();
						$number->setValue($argument->value);
						return $number;
				}
			}
		));
		$argument = (object)array(
			'testObject' => array(
				(object)array('value' => 1),
				(object)array('value' => 2),
				(object)array('value' => 3),
				(object)array('value' => 4)
			)
		);
		$result = $this->serviceWrapper->sumNumbers($argument);
		$this->assertEquals(10, $result);
	}

	/**
	 * @return \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected function buildMockReflectionServiceForTestService() {
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->will($this->returnCallback(
			function($className) {
				switch ($className) {
					case 'TestObject':
						return TRUE;
					default:
						return FALSE;
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnCallback(
			function($className, $methodName) {
				switch ($methodName) {
					case 'hello':
						return array(
							'name' => array(
								'position' => 0,
								'byReference' => FALSE,
								'array' => FALSE,
								'optional' => FALSE,
								'allowsNull' => TRUE,
								'class' => NULL,
								'type' => 'string'
							)
						);
					case 'helloObject':
						return array(
							'name' => array(
								'position' => 0,
								'byReference' => TRUE,
								'array' => FALSE,
								'optional' => FALSE,
								'allowsNull' => FALSE,
								'class' => 'TestObject',
								'type' => 'TestObject'
							)
						);
					case 'sum':
						return array(
							'name' => array(
								'position' => 0,
								'byReference' => FALSE,
								'array' => TRUE,
								'optional' => FALSE,
								'allowsNull' => FALSE,
								'class' => NULL,
								'type' => 'array<int>'
							)
						);
					case 'sumNumbers':
						return array(
							'name' => array(
								'position' => 0,
								'byReference' => FALSE,
								'array' => TRUE,
								'optional' => FALSE,
								'allowsNull' => FALSE,
								'class' => NULL,
								'type' => 'array<TestObject>'
							)
						);
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('getMethodTagsValues')->will($this->returnCallback(
			function($className, $methodName) {
				switch ($methodName) {
					case 'hello':
						return array(
							'param' => array('string $name Your name'),
							'return' => array('string A nice greeting')
						);
					case 'helloObject':
						return array(
							'param' => array('TestObject $name Your name object'),
							'return' => array('string A nice greeting')
						);
					case 'sum':
						return array(
							'param' => array('array<int> $values Values'),
							'return' => array('int The sum')
						);
					case 'sumNumbers':
						return array(
							'param' => array('array<TestObject> $values Values'),
							'return' => array('int The sum')
						);
				}
			}
		));
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnCallback(
			function($className) {
				switch ($className) {
					case 'TestObject':
						return array('value');
				}
			}
		));
		return $mockReflectionService;
	}

}
?>