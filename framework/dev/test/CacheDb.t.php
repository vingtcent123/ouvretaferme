<?php
namespace dev;


/**
 * Db cache test
 */
class CacheDbTest extends CacheTest {

	public function init() {
		$this->cache = \Cache::db();
	}

	public function testIncrement() {
	}

	public function testDecrement() {
	}

	public function testAppend() {
	}

	public function testPrepend() {
	}

}

?>
