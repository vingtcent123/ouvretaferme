<?php
namespace util;

/**
 * CSV package test page
 *
 * @author Laurent Bardin
 */
class CsvTest extends \Test {

	public $csv;

	public function init() {
		$this->csv = new \util\CsvLib();
	}

	public function testCsv() {
		$this->assertEquals("foo\n", $this->csv->toCsv([['foo']]));
		$this->assertEquals("foo;bar\n", $this->csv->toCsv([['foo','bar']]));
		$this->assertEquals("foo\nbar\n", $this->csv->toCsv([['foo'], ['bar']]));

		$this->assertEquals("\"foo;bar\"\n", $this->csv->toCsv([['foo;bar']]));
		$this->assertEquals("\"foo ;bar\"\n", $this->csv->toCsv([['foo ;bar']]));
		$this->assertEquals("\"foo;b ar\"\n", $this->csv->toCsv([['foo;b ar']]));
		$this->assertEquals("foo;\"bar \"\n", $this->csv->toCsv([['foo','bar ']]));
		$this->assertEquals("\"fo\"\"o\"\nbar\n", $this->csv->toCsv([['fo"o'], ['bar']]));

		$this->assertEquals("\"foo\nbar\"\n", $this->csv->toCsv([["foo\nbar"]]));
		$this->assertEquals("\"foo\r\nbar\"\n", $this->csv->toCsv([["foo\r\nbar"]]));
		$this->assertEquals("\"foo;b\"\"ar\"\n", $this->csv->toCsv([['foo;b"ar']]));
	}

}

?>
