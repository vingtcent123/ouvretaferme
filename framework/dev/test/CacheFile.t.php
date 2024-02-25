<?php
namespace dev;


/**
 * File cache test
 */
class CacheFileTest extends CacheTest {

	public function init() {

		$this->finalize();

		exec('mkdir /tmp/cache-test');

		$this->cache = \Cache::file();
		$this->cache->setPath('/tmp/cache-test');
	}

	public function testIncrement() {
	}

	public function testDecrement() {
	}

	public function testAppend() {
	}

	public function testPrepend() {
	}

	public function finalize() {
		exec('rm -rf /tmp/cache-test');
	}

}

?>
