<?php
namespace TYPO3\Soap\Tests\Functional;

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
use TYPO3\Soap\ServiceWrapper;

/**
 * Testcase for the Service Wrapper
 */
class ServiceWrapperTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @return \TYPO3\Soap\ServiceWrapper
	 */
	protected function createServiceWrapper() {
		$serviceObject = $this->objectManager->get('TYPO3\Soap\Tests\Functional\Fixtures\TestService');
		$mockRequest = $this->getMockBuilder('TYPO3\Soap\Request')
			->disableOriginalConstructor()
			->getMock();
		$wrapper = new ServiceWrapper($serviceObject);
		$wrapper->setRequest($mockRequest);
		return $wrapper;
	}

	/**
	 * @test
	 */
	public function simpleServiceMethodIsWrapped() {
		$wrapper = $this->createServiceWrapper();
		$result = $wrapper->ping('Hello');
		$this->assertEquals('Hello', $result);
	}

	/**
	 * @test
	 */
	public function argumentToMethodIsMappedToObjectByParamAnnotation() {
		$wrapper = $this->createServiceWrapper();
		$argument = new \stdClass();
		$argument->name = 'Foo';
		$argument->size = 2;
		$result = $wrapper->multiply($argument);
		$this->assertEquals('FooFoo', $result);
	}

	/**
	 * @test
	 */
	public function mapperMapsToClassName() {
		$this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
		$value = array('name' => 'Foo', 'size' => 2);
		$type = 'TYPO3\Soap\Tests\Functional\Fixtures\Dto';
		$target = $this->propertyMapper->convert($value, $type);
		$this->assertInstanceOf('TYPO3\Soap\Tests\Functional\Fixtures\Dto', $target);
		$this->assertEquals('Foo', $target->getName());
	}

	/**
	 * @test
	 */
	public function simpleArrayTypeParametersAreConvertedFromStdClass() {
		$wrapper = $this->createServiceWrapper();
		$argument = new \stdClass();
		$argument->integer = array(17, 4);
		$result = $wrapper->sum($argument);
		$this->assertEquals(21, $result);
	}

	/**
	 * @test
	 */
	public function objectArrayTypeParametersAreConvertedFromStdClass() {
		$wrapper = $this->createServiceWrapper();
		$argument = (object)array(
			'dto' => array(
				(object)array('name' => 'Foo', 'size' => 1),
				(object)array('name' => 'Bar', 'size' => 2)
			)
		);
		$result = $wrapper->concat($argument);
		$this->assertEquals('FooBar', $result);
	}

	/**
	 * @test
	 */
	public function wrappedObjectArrayTypeParameterWithMultipleValuesIsConvertedFromStdClass() {
		$wrapper = $this->createServiceWrapper();
		$argument = (object)array(
			'dtos' => (object)array(
				'dto' => array(
					(object)array('name' => 'Foo', 'size' => 1),
					(object)array('name' => 'Bar', 'size' => 2)
				)
			)
		);
		$result = $wrapper->wrappedConcat($argument);
		$this->assertEquals('FooBar', $result);
	}

	/**
	 * @test
	 */
	public function wrappedObjectArrayTypeParameterWithSingleValueIsConvertedFromStdClass() {
		$wrapper = $this->createServiceWrapper();
		$argument = (object)array(
			'dtos' => (object)array(
				'dto' => (object)array('name' => 'Foo', 'size' => 1)
			)
		);
		$result = $wrapper->wrappedConcat($argument);
		$this->assertEquals('Foo', $result);
	}

	/**
	 * @test
	 */
	public function wrappedObjectArrayTypeParameterWithNoValueIsConvertedFromStdClass() {
		$wrapper = $this->createServiceWrapper();

		$argument = (object)array(
			'dtos' => (object)array()
		);
		$result = $wrapper->wrappedConcat($argument);
		$this->assertEquals('', $result);

		$argument = (object)array();
		$result = $wrapper->wrappedConcat($argument);
		$this->assertEquals('', $result);
	}

	/**
	 * @test
	 * @expectedException \SoapFault
	 */
	public function expectedExceptionsAreConvertedToClientSoapFault() {
		$wrapper = $this->createServiceWrapper();

		try {
			$result = $wrapper->ping('invalid');
		} catch(\Exception $exception) {
			$this->assertContains('SoapFault exception: [Client] Some expected exception occured', (string)$exception);
			throw $exception;
		}
	}

}
?>