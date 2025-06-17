<?php
namespace dev;


/**
 * Filter test page
 */
class FilterTest extends \Test {

	/**
	 * 'int' test
	 */
	public function testInt() {

		// Test integrity
		$this->assertFalse(\Filter::check('int', NULL));
		$this->assertFalse(\Filter::check('int', '0x'));
		$this->assertFalse(\Filter::check('int', 'lskdf'));
		$this->assertFalse(\Filter::check('int', '12.3'));
		$this->assertFalse(\Filter::check('int', -4.9));
		$this->assertFalse(\Filter::check('int', '-4.9'));
		$this->assertFalse(\Filter::check('int', ['-4']));
		$this->assertFalse(\Filter::check('int', new \stdClass));

		$this->assertTrue(\Filter::check('int', 0xFF));

		$this->intPositive('int', TRUE);
		$this->intZero('int', TRUE);
		$this->intNegative('int', TRUE);

		// Test intervals
		$int12More = ['int', 'min' => 12, 'max' => NULL];

		$this->intPositive($int12More, TRUE);
		$this->intZero($int12More, FALSE);
		$this->intNegative($int12More, FALSE);

		$int0Less = ['int', 'min' => NULL, 'max' => 0];

		$this->intPositive($int0Less, FALSE);
		$this->intZero($int0Less, TRUE);
		$this->intNegative($int0Less, TRUE);

		$intMinus2To10 = ['int', 'min' => -2, 'max' => 10];

		$this->intPositive($intMinus2To10, FALSE);
		$this->intZero($intMinus2To10, TRUE);
		$this->intNegative($intMinus2To10, FALSE);

		$intNull = ['int', 'null' => TRUE];

		$this->assertTrue(\Filter::check($intNull, NULL));

	}

	private function intPositive($name, $ok) {

		$this->assertTrue(\Filter::check($name, 12) === $ok);
		$this->assertTrue(\Filter::check($name, '12') === $ok);
		$this->assertTrue(\Filter::check($name, 12.0) === $ok);

	}

	private function intZero($name, $ok) {

		$this->assertTrue(\Filter::check($name, 0) === $ok);
		$this->assertTrue(\Filter::check($name, '0') === $ok);
		$this->assertTrue(\Filter::check($name, 0.0) === $ok);

	}

	private function intNegative($name, $ok) {

		$this->assertTrue(\Filter::check($name, -4) === $ok);
		$this->assertTrue(\Filter::check($name, '-4') === $ok);
		$this->assertTrue(\Filter::check($name, -4.0) === $ok);

	}

	/**
	 * 'float' test
	 */
	public function testFloat() {

		// Test integrity
		$this->assertFalse(\Filter::check('float', NULL));
		$this->assertFalse(\Filter::check('float', '0x'));
		$this->assertFalse(\Filter::check('float', '0.4/'));
		$this->assertFalse(\Filter::check('float', 'lskdf'));
		$this->assertFalse(\Filter::check('float', ['-4']));
		$this->assertFalse(\Filter::check('float', new \stdClass));

		$this->assertTrue(\Filter::check('float', 0xFF));

		$this->floatPositive('float', TRUE);
		$this->floatZero('float', TRUE);
		$this->floatNegative('float', TRUE);

		// Test intervals
		$float12More = ['float', 'min' => 12.0, 'max' => NULL];

		$this->floatPositive($float12More, TRUE);
		$this->floatZero($float12More, FALSE);
		$this->floatNegative($float12More, FALSE);

		$float0Less = ['float', 'min' => NULL, 'max' => 0.0];

		$this->floatPositive($float0Less, FALSE);
		$this->floatZero($float0Less, TRUE);
		$this->floatNegative($float0Less, TRUE);

		$floatMinus2To10 = ['float', 'min' => -2.0, 'max' => 10.0];

		$this->floatPositive($floatMinus2To10, FALSE);
		$this->floatZero($floatMinus2To10, TRUE);
		$this->floatNegative($floatMinus2To10, FALSE);

		$floatNull = ['float', 'null' => TRUE];

		$this->assertTrue(\Filter::check($floatNull, NULL));

	}

	private function floatPositive($name, $ok) {

		$this->assertTrue(\Filter::check($name, 12) === $ok);
		$this->assertTrue(\Filter::check($name, '12') === $ok);
		$this->assertTrue(\Filter::check($name, 12.0) === $ok);
		$this->assertTrue(\Filter::check($name, 12.3) === $ok);
		$this->assertTrue(\Filter::check($name, '12.3') === $ok);

	}

	private function floatZero($name, $ok) {

		$this->assertTrue(\Filter::check($name, 0) === $ok);
		$this->assertTrue(\Filter::check($name, '0') === $ok);
		$this->assertTrue(\Filter::check($name, 0.0) === $ok);

	}

	private function floatNegative($name, $ok) {

		$this->assertTrue(\Filter::check($name, -4) === $ok);
		$this->assertTrue(\Filter::check($name, '-4') === $ok);
		$this->assertTrue(\Filter::check($name, -4.0) === $ok);
		$this->assertTrue(\Filter::check($name, '-4.4') === $ok);
		$this->assertTrue(\Filter::check($name, -4.4) === $ok);

	}

	/**
	 * 'text' test
	 */
	public function testText() {

		// Test integrity
		$this->assertFalse(\Filter::check('text', NULL));
		$this->assertFalse(\Filter::check('text', ['-4']));
		$this->assertFalse(\Filter::check('text', new \stdClass));

		$this->assertTrue(\Filter::check('text', 0xFF));

		// Test intervals
		$text12 = ['text', 'min' => 12, 'max' => NULL];

		$this->assertFalse(\Filter::check($text12, ''));
		$this->assertTrue(\Filter::check($text12, "abcdefghijkl"));
		$this->assertTrue(\Filter::check($text12, "abcdefghijklaaaaaaaa"));
		$this->assertFalse(\Filter::check($text12, "abcdefghijk"));

		$text0 = ['text', 'min' => NULL, 'max' => 0];

		$this->assertTrue(\Filter::check($text0, ''));
		$this->assertFalse(\Filter::check($text0, 'a'));
		$this->assertFalse(\Filter::check($text0, "\0"));

		$text2To10 = ['text', 'min' => 2, 'max' => 10];

		$this->assertFalse(\Filter::check($text2To10, ''));
		$this->assertTrue(\Filter::check($text2To10, "ab"));
		$this->assertTrue(\Filter::check($text2To10, "abcdefghij"));
		$this->assertFalse(\Filter::check($text2To10, "abcdefghijk"));

		$textNull = ['text', 'null' => TRUE];

		$this->assertTrue(\Filter::check($textNull, NULL));

	}

	/**
	 * 'date' test
	 */
	public function testDate() {

		// Test integrity
		$this->assertFalse(\Filter::check('date', NULL));
		$this->assertFalse(\Filter::check('date', 12355));
		$this->assertFalse(\Filter::check('date', new \stdClass));
		$this->assertFalse(\Filter::check('date', 'hjkjhkh'));
		$this->assertFalse(\Filter::check('date', '1990-32-23'));
		$this->assertFalse(\Filter::check('date', '1990-12-33'));
		$this->assertFalse(\Filter::check('date', '1990-00-10'));
		$this->assertFalse(\Filter::check('date', '1990-10-00'));
		$this->assertFalse(\Filter::check('date', '1990-00-00'));
		$this->assertFalse(\Filter::check('date', '1990-13-30'));
		$this->assertFalse(\Filter::check('date', '1990-12-32'));
		$this->assertFalse(\Filter::check('date', '2-12-33'));

		$this->assertFalse(\Filter::check('date', '1990-04-31'));
		$this->assertFalse(\Filter::check('date', '0007-04-31'));

		$this->assertTrue(\Filter::check('date', '1990-12-23'));
		$this->assertTrue(\Filter::check('date', '2019-12-23'));

		// Test intervals
		$dateMax = ['date', 'min' => NULL, 'max' => '2 day ago'];

		$this->assertFalse(\Filter::check($dateMax, date('Y-m-d')));
		$this->assertTrue(\Filter::check($dateMax, date('Y-m-d', time() - 86400 * 3)));
		$this->assertTrue(\Filter::check($dateMax, '2007-06-04'));

		$dateInterval = ['date', 'min' => '@'.(time() - 5), 'max' => '@'.time()];

		$this->assertTrue(\Filter::check($dateInterval, date('Y-m-d')));
		$this->assertFalse(\Filter::check($dateInterval, date('Y-m-d', time() - 86400)));
		$this->assertFalse(\Filter::check($dateInterval, date('Y-m-d', time() + 86400)));

		$dateMin = ['date', 'min' => '2004-05-06', 'max' => NULL];

		$this->assertTrue(\Filter::check($dateMin, '2004-05-06'));
		$this->assertTrue(\Filter::check($dateMin, date('Y-m-d')));
		$this->assertFalse(\Filter::check($dateMin, '2004-05-05'));

		$dateNull = ['date', 'null' => TRUE];

		$this->assertTrue(\Filter::check($dateNull, NULL));

	}

	/**
	 * 'datetime' test
	 */
	public function testDatetime() {

		// Test integrity
		$this->assertFalse(\Filter::check('datetime', NULL));
		$this->assertFalse(\Filter::check('datetime', 12355));
		$this->assertFalse(\Filter::check('datetime', new \stdClass));
		$this->assertFalse(\Filter::check('datetime', 'hjkjhkh'));
		$this->assertFalse(\Filter::check('datetime', '1990-32-23 00:00:00'));
		$this->assertFalse(\Filter::check('datetime', '1990-12-33 00:00:00'));
		$this->assertFalse(\Filter::check('datetime', '2-12-33'));
		$this->assertFalse(\Filter::check('datetime', '1990-12-23 25:00:00'));

		$this->assertFalse(\Filter::check('datetime', '1990-04-31 00:00:00'));

		$this->assertTrue(\Filter::check('datetime', '1990-12-23 01:23:45'));
		$this->assertTrue(\Filter::check('datetime', '2019-12-23 06:23:45'));

		// Test intervals
		$datetimeMax = ['datetime', 'min' => NULL, 'max' => '2 second ago'];

		$this->assertFalse(\Filter::check($datetimeMax, date('Y-m-d H:i:s')));
		$this->assertTrue(\Filter::check($datetimeMax, date('Y-m-d H:i:s', time() - 2)));
		$this->assertTrue(\Filter::check($datetimeMax, '2007-06-04 01:34:34'));

		$datetimeInterval = ['datetime', 'min' =>  '@'.(time() - 5), 'max' => '@'.(time() + 1)];

		$datetime = new \DateTime('@'.time());
		$time = $datetime->format("Y-m-d H:i:s");
		$this->assertTrue(\Filter::check($datetimeInterval, $time));
		$this->assertFalse(\Filter::check($datetimeInterval, date('Y-m-d H:i:s', time() - 6)));
		$this->assertFalse(\Filter::check($datetimeInterval, date('Y-m-d H:i:s', time() + 3)));

		$datetimeMin = ['datetime', 'min' => '2004-05-06 01:02:03', 'max' => NULL];

		$this->assertTrue(\Filter::check($datetimeMin, '2004-05-06 01:02:03'));
		$this->assertTrue(\Filter::check($datetimeMin, date('Y-m-d H:i:s')));
		$this->assertFalse(\Filter::check($datetimeMin, '2004-05-06 01:02:02'));

		$datetimeNull = ['datetime', 'null' => TRUE];

		$this->assertTrue(\Filter::check($datetimeNull, NULL));

	}

	/**
	 * 'email' test
	 */
	public function testEmail() {

		$this->assertFalse(\Filter::check('email', NULL));
		$this->assertFalse(\Filter::check('email', 12355));
		$this->assertFalse(\Filter::check('email', new \stdClass));
		$this->assertFalse(\Filter::check('email', 'hjkjhkh'));
		$this->assertFalse(\Filter::check('email', 'dd,df@dfsf.com'));
		$this->assertFalse(\Filter::check('email', 'ddsf@.com'));
		$this->assertFalse(\Filter::check('email', 'sdfsf@sfsf.c'));
		$this->assertFalse(\Filter::check('email', 'sdfsf@sfsf.cddddddd'));
		$this->assertFalse(\Filter::check('email', 'sdfsdf@sfsf.sf%'));
		$this->assertFalse(\Filter::check('email', 'a adda@owlient.eu'));
		$this->assertFalse(\Filter::check('email', 'a.addé@owlient.eu'));
		$this->assertFalse(\Filter::check('email', '.x.@.eu'));
		$this->assertFalse(\Filter::check('email', '.x.@foo.eu'));
		$this->assertFalse(\Filter::check('email', '.@foo.eu'));
		$this->assertFalse(\Filter::check('email', '..@foo.eu'));
		$this->assertFalse(\Filter::check('email', '...@foo.eu'));

		$this->assertTrue(\Filter::check('email', 'sdfdsf@sfsf.sf'));
		$this->assertTrue(\Filter::check('email', 'dfsfsdf.sfsf.sf@sdfsf.sf.sdfsf.sf'));
		$this->assertTrue(\Filter::check('email', 'foo@bar-baz.com'));
		$this->assertTrue(\Filter::check('email', 'foo@bar-baz-quux.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar@bar.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar@bar-baz.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar@bar-baz-quux.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar-baz@bar.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar-baz@bar-baz.com'));
		$this->assertTrue(\Filter::check('email', 'foo-bar-baz@bar-baz-quux.com'));

		$emailNull = ['email', 'null' => TRUE];

		$this->assertTrue(\Filter::check($emailNull, NULL));

	}

	/**
	 * 'url' test
	 */
	public function testUrl() {

		$this->assertFalse(\Filter::check('url', NULL));
		$this->assertFalse(\Filter::check('url', 12355));
		$this->assertFalse(\Filter::check('url', new \stdClass));
		$this->assertFalse(\Filter::check('url', 'hjkjhkh'));
		$this->assertFalse(\Filter::check('url', 'dfdf://dfsf.sf'));
		$this->assertFalse(\Filter::check('url', 'http:/sd.sd'));
		$this->assertFalse(\Filter::check('url', 'http://dfd".fr'));
		$this->assertFalse(\Filter::check('url', 'http://dfd.fr%'));

		$this->assertTrue(\Filter::check('url', 'http://www'));
		$this->assertTrue(\Filter::check('url', 'http://www.dd.dd.dd'));
		$this->assertTrue(\Filter::check('url', 'http://www.dd.dd.dd/'));
		$this->assertTrue(\Filter::check('url', 'http://www.dd.dd.dd/%M3"'));

		$urlNull = ['url', 'null' => TRUE];

		$this->assertTrue(\Filter::check($urlNull, NULL));

	}

	/**
	 * 'fqn' test
	 */
	public function testFqn() {
		$this->assertFalse(\Filter::check('fqn', NULL));
		$this->assertFalse(\Filter::check('fqn', new \stdClass));
		$this->assertFalse(\Filter::check('fqn', 'ddsf@com'));
		$this->assertFalse(\Filter::check('fqn', 'sdsd_sds'));
		$this->assertFalse(\Filter::check('fqn', "d\0s"));
		$this->assertFalse(\Filter::check('fqn', str_repeat('a', 256)));

		$this->assertTrue(\Filter::check('fqn', 0xFF));
		$this->assertTrue(\Filter::check('fqn', 12355));
		$this->assertTrue(\Filter::check('fqn', 'sdfdsfsfsfsf'));
		$this->assertTrue(\Filter::check('fqn', 'sf-'));
		$this->assertTrue(\Filter::check('fqn', 'sdkfjnsdkfjhdsflhsflsdfhslf987'));

		$fqnNull = ['fqn', 'null' => TRUE];

		$this->assertTrue(\Filter::check($fqnNull, NULL));

	}

	/**
	 * 'bool' test
	 */
	public function testBool() {

		$this->assertFalse(\Filter::check('bool', NULL));

		$this->assertTrue(\Filter::check('bool', 0xFF));
		$this->assertTrue(\Filter::check('bool', 1));
		$this->assertTrue(\Filter::check('bool', '1443'));
		$this->assertTrue(\Filter::check('bool', '0jksd'));
		$this->assertTrue(\Filter::check('bool', FALSE));
		$this->assertTrue(\Filter::check('bool', TRUE));
		$this->assertTrue(\Filter::check('bool', ['ok']));

		$boolNull = ['bool', 'null' => TRUE];

		$this->assertTrue(\Filter::check($boolNull, NULL));

	}

	/**
	 * 'element' test
	 */
	public function testElement() {

		$element = ['element', 'user\User'];

		$this->assertFalse(\Filter::check($element, new \stdClass));
		$this->assertFalse(\Filter::check($element, 'aaa'));
		$this->assertFalse(\Filter::check($element, '12'));
		$this->assertFalse(\Filter::check($element, 12));
		$this->assertFalse(\Filter::check($element, ['id']));
		$this->assertFalse(\Filter::check($element, ['id' => 'sst']));
		$this->assertFalse(\Filter::check($element, ['id' => '124567891234']));
		$this->assertFalse(\Filter::check($element, ['id' => 0]));

		$this->assertFalse(\Filter::check($element, ['id' => NULL]));
		$this->assertTrue(\Filter::check($element, ['id' => 12]));

		$elementNull = ['element', 'user\User', 'null' => TRUE];

		$this->assertTrue(\Filter::check($elementNull, ['id' => NULL]));
		$this->assertTrue(\Filter::check($elementNull, NULL));

	}

	/**
	 * 'element64' test
	 */
	public function testElement64() {

		$this->assertFalse(\Filter::check(['element64', 'user\User'], new \stdClass));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], 'aaa'));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], '12'));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], 12));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], ['id']));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], ['id' => 'sst']));
		$this->assertFalse(\Filter::check(['element64', 'user\User'], ['id' => 0]));

		$this->assertFalse(\Filter::check(['element64', 'user\User'], ['id' => NULL]));
		$this->assertTrue(\Filter::check(['element64', 'user\User'], ['id' => 12]));
		$this->assertTrue(\Filter::check(['element64', 'user\User'], ['id' => '124567891234']));

		$elementNull = ['element64', 'user\User', 'null' => TRUE];

		$this->assertTrue(\Filter::check($elementNull, ['id' => NULL]));
		$this->assertTrue(\Filter::check($elementNull, NULL));

	}

	/**
	 * 'enum' test
	 */
	public function testEnum() {

		$enum = ['enum', [1, '2', [3]]];

		$this->assertFalse(\Filter::check($enum, NULL));
		$this->assertFalse(\Filter::check($enum, 3));
		$this->assertFalse(\Filter::check($enum, 12));
		$this->assertFalse(\Filter::check($enum, '2x'));
		$this->assertFalse(\Filter::check($enum, new \stdClass));
		$this->assertFalse(\Filter::check($enum, 'iiiiiii'));
		$this->assertFalse(\Filter::check($enum, ['3']));
		$this->assertFalse(\Filter::check($enum, '1'));
		$this->assertFalse(\Filter::check($enum, 2));

		$this->assertTrue(\Filter::check($enum, 1));
		$this->assertTrue(\Filter::check($enum, '2'));
		$this->assertTrue(\Filter::check($enum, [3]));

		$enumNull = ['enum', [1], 'null' => TRUE];

		$this->assertTrue(\Filter::check($enumNull, NULL));


	}

	/**
	 * 'set' test
	 */
	public function testSet() {

		$set = ['set', [1, 2, 4]];

		$this->assertFalse(\Filter::check($set, -1));
		$this->assertFalse(\Filter::check($set, 8));
		$this->assertFalse(\Filter::check($set, 5.3));
		$this->assertFalse(\Filter::check($set, 72));
		$this->assertFalse(\Filter::check($set, '2x'));
		$this->assertFalse(\Filter::check($set, new \stdClass));
		$this->assertFalse(\Filter::check($set, ['3']));
		$this->assertFalse(\Filter::check($set, '9'));

		$this->assertTrue(\Filter::check($set, '1'));
		$this->assertTrue(\Filter::check($set, 1));
		$this->assertTrue(\Filter::check($set, 7));
		$this->assertTrue(\Filter::check($set, 0));

		$setNull = ['set', [1], 'null' => TRUE];

		$this->assertTrue(\Filter::check($setNull, NULL));


	}

	/**
	 * 'ip' test
	 */
	public function testIp() {

		$this->assertFalse(\Filter::check('ip', NULL));

		$this->ipV4('ip');
		$this->ipV6('ip');
		$this->ipV4('ipv4');
		$this->ipV6('ipv6');

		$ipNull = ['ip', 'null' => TRUE];

		$this->assertTrue(\Filter::check($ipNull, NULL));

	}

	private function ipV4($name) {

		$this->assertTrue(\Filter::check($name, '1.1.1.1'));
		$this->assertTrue(\Filter::check($name, '1.255.0.5'));
		$this->assertFalse(\Filter::check($name, '1.1.1.'));
		$this->assertFalse(\Filter::check($name, '1.1.1.d'));
		$this->assertFalse(\Filter::check($name, '-1.1.1.256'));

	}

	private function ipV6($name) {

		$this->assertTrue(\Filter::check($name, '0000:1100:2002:D00D:F00F:000F:9009:8008'));
		$this->assertFalse(\Filter::check($name, '0000:0011:2002:0033:F00F:9900:8008'));
		$this->assertFalse(\Filter::check($name, '0000:0011:2002:0033:F00F:DD00:D00D:0099:8008'));
		$this->assertFalse(\Filter::check($name, '0000:0011:2002:3003:F00G:D00D:9009:8008'));
		$this->assertFalse(\Filter::check($name, '0000:0011:2200:3003:F00:D00D:9900:8800'));

	}

}

?>
