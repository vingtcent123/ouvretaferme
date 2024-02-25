<?php
namespace dev;


/**
 * Memcache test
 */
class CacheMemTest extends CacheTest {

	public function init() {
		$this->cache = \Cache::mem();
	}

}

?>
