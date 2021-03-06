<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n;

use \lithium\core\Environment;
use \lithium\g11n\Message;
use \lithium\g11n\Catalog;
use \lithium\g11n\catalog\adapter\Memory;

class MessageTest extends \lithium\test\Unit {

	protected $_backups = array();

	public function setUp() {
		$this->_backups['catalogConfig'] = Catalog::config();
		Catalog::reset();
		Catalog::config(array(
			'runtime' => array('adapter' => new Memory())
		));
		$data = function($n) { return $n == 1 ? 0 : 1; };
		Catalog::write('message.plural', 'root', $data, array('name' => 'runtime'));
	}

	public function tearDown() {
		Catalog::reset();
		Catalog::config($this->_backups['catalogConfig']);
	}

	public function testTranslateBasic() {
		$data = array(
			'catalog' => 'Katalog',
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$expected = 'Katalog';
		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertEqual($expected, $result);
	}

	public function testTranslatePlural() {
		$data = array(
			'house' => array('Haus', 'Häuser')
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$expected = 'Haus';
		$result = Message::translate('house', array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', array('locale' => 'de', 'count' => 5));
		$this->assertEqual($expected, $result);
	}

	public function testTranslateFail() {
		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertNull($result);

		Catalog::reset();
		Catalog::config(array(
			'runtime' => array('adapter' => new Memory())
		));

		$data = array(
			'catalog' => 'Katalog',
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertNull($result);

		$data = 'not a valid pluralization function';
		Catalog::write('message.plural', 'root', $data, array('name' => 'runtime'));

		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertNull($result);
	}

	public function testTranslateScope() {
		$data = array(
			'catalog' => 'Katalog',
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime', 'scope' => 'test'));

		$data = function($n) { return $n == 1 ? 0 : 1; };
		Catalog::write('message.plural', 'root', $data, array(
			'name' => 'runtime', 'scope' => 'test'
		));

		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertNull($result);

		$expected = 'Katalog';
		$result = Message::translate('catalog', array('locale' => 'de', 'scope' => 'test'));
		$this->assertEqual($expected, $result);
	}

	public function testTranslateDefault() {
		$result = Message::translate('Here I am', array('locale' => 'de'));
		$this->assertNull($result);

		$result = Message::translate('Here I am', array(
			'locale' => 'de', 'default' => 'Here I am'
		));
		$expected = 'Here I am';
		$this->assertEqual($expected, $result);
	}

	public function testTranslatePlaceholders() {
		$data = array(
			'green' => 'grün',
			'The fish is {:color}.' => 'Der Fisch ist {:color}.',
			'{:count} bike' => array('{:count} Fahrrad', '{:count} Fahrräder'),
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$expected = 'Der Fisch ist grün.';
		$result = Message::translate('The fish is {:color}.', array(
			'locale' => 'de',
			'color' => Message::translate('green', array('locale' => 'de'))
		));
		$this->assertEqual($expected, $result);

		$expected = '1 Fahrrad';
		$result = Message::translate('{:count} bike', array('locale' => 'de', 'count' => 1));
		$this->assertEqual($expected, $result);

		$expected = '7 Fahrräder';
		$result = Message::translate('{:count} bike', array('locale' => 'de', 'count' => 7));
		$this->assertEqual($expected, $result);
	}

	public function testTranslateLocales() {
		$data = array(
			'catalog' => 'Katalog',
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));
		$data = array(
			'catalog' => 'catalogue',
		);
		Catalog::write('message', 'fr', $data, array('name' => 'runtime'));

		$expected = 'Katalog';
		$result = Message::translate('catalog', array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'catalogue';
		$result = Message::translate('catalog', array('locale' => 'fr'));
		$this->assertEqual($expected, $result);
	}

	public function testTranslateNoop() {
		$data = array(
			'catalog' => 'Katalog',
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$result = Message::translate('catalog', array('locale' => 'de', 'noop' => true));
		$this->assertNull($result);
	}

	public function testShortHandsBasic() {
		$data = array(
			'house' => array('Haus', 'Häuser')
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$filters = Message::shortHands();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = 'Haus';
		$result = $t('house', array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'Haus';
		$result = $tn('house', 'houses', 1, array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = $tn('house', 'houses', 3, array('locale' => 'de'));
		$this->assertEqual($expected, $result);
	}

	public function testShortHandsSymmetry() {
		$data = array(
			'house' => array('Haus', 'Häuser')
		);
		Catalog::write('message', 'de', $data, array('name' => 'runtime'));

		$filters = Message::shortHands();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = Message::translate('house', array('locale' => 'de'));
		$result = $t('house', array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = Message::translate('house', array('locale' => 'de', 'count' => 1));
		$result = $tn('house', 'houses', 1, array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = Message::translate('house', array('locale' => 'de', 'count' => 3));
		$result = $tn('house', 'houses', 3, array('locale' => 'de'));
		$this->assertEqual($expected, $result);
	}

	public function testShortHandsAsymmetry() {
		$filters = Message::shortHands();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = Message::translate('house', array('locale' => 'de'));
		$result = $t('house', array('locale' => 'de'));
		$this->assertNotEqual($expected, $result);

		$expected = Message::translate('house', array('locale' => 'de', 'count' => 3));
		$result = $tn('house', 'houses', array('locale' => 'de'));
		$this->assertNotEqual($expected, $result);
	}
}

?>