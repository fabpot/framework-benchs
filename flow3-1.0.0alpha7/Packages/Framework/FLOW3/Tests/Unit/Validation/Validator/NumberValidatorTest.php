<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * Testcase for the number validator
 *
 * @version $Id: NumberValidatorTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$numberValidator = new \F3\FLOW3\Validation\Validator\NumberValidator();
		$this->assertTrue($numberValidator->isValid(1029437));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorReturnsFalseForAString() {
		$numberValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($numberValidator->isValid('not a number'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$numberValidator = $this->getMock('F3\FLOW3\Validation\Validator\NumberValidator', array('addError'), array(), '', FALSE);
		$numberValidator->expects($this->once())->method('addError')->with('The given subject was not a valid number. Got: "this is not a number"', 1221563685);
		$numberValidator->isValid('this is not a number');
	}
}

?>