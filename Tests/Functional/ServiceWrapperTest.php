<?php
declare(ENCODING = 'utf-8');
namespace F3\Soap\Tests\Functional;

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
 * Testcase for the Service Wrapper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ServiceWrapperTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var \F3\FLOW3\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function simpleServiceMethodIsWrapped() {
		$serviceObject = $this->objectManager->get('F3\Soap\Tests\Functional\Fixtures\TestService');
		$wrapper = $this->objectManager->create('F3\Soap\ServiceWrapper', $serviceObject);
		$mockRequest = $this->getMock('F3\Soap\Request');
		$wrapper->setRequest($mockRequest);
		$result = $wrapper->ping('Hello');
		$this->assertEquals('Hello', $result);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function argumentToMethodIsMappedToObjectByParamAnnotation() {
		$serviceObject = $this->objectManager->get('F3\Soap\Tests\Functional\Fixtures\TestService');
		$wrapper = $this->objectManager->create('F3\Soap\ServiceWrapper', $serviceObject);
		$mockRequest = $this->getMock('F3\Soap\Request');
		$wrapper->setRequest($mockRequest);
		$argument = new \stdClass();
		$argument->name = 'Foo';
		$argument->size = 2;
		$result = $wrapper->multiply($argument);
		$this->assertEquals('FooFoo', $result);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function mapperMapsToClassName() {
		$this->propertyMapper = $this->objectManager->get('F3\FLOW3\Property\PropertyMapper');
		$value = array('name' => 'Foo', 'size' => 2);
		$type = 'F3\Soap\Tests\Functional\Fixtures\Dto';
		$target = $this->propertyMapper->convert($value, $type);
		$this->assertType('F3\Soap\Tests\Functional\Fixtures\Dto', $target);
		$this->assertEquals('Foo', $target->getName());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function simpleArrayTypeParametersAreConvertedFromStdClass() {
		$serviceObject = $this->objectManager->get('F3\Soap\Tests\Functional\Fixtures\TestService');
		$wrapper = $this->objectManager->create('F3\Soap\ServiceWrapper', $serviceObject);
		$mockRequest = $this->getMock('F3\Soap\Request');
		$wrapper->setRequest($mockRequest);
		$argument = new \stdClass();
		$argument->integer = array(17, 4);
		$result = $wrapper->sum($argument);
		$this->assertEquals(21, $result);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function objectArrayTypeParametersAreConvertedFromStdClass() {
		$serviceObject = $this->objectManager->get('F3\Soap\Tests\Functional\Fixtures\TestService');
		$wrapper = $this->objectManager->create('F3\Soap\ServiceWrapper', $serviceObject);
		$mockRequest = $this->getMock('F3\Soap\Request');
		$wrapper->setRequest($mockRequest);
		$argument = (object)array(
			'Dto' => array(
				(object)array('name' => 'Foo', 'size' => 1),
				(object)array('name' => 'Bar', 'size' => 2)
			)
		);
		$result = $wrapper->concat($argument);
		$this->assertEquals('FooBar', $result);
	}
}
?>