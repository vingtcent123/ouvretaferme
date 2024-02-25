<?php
namespace dev;


/**
 * Redis test
 */
class CacheRedisTest extends CacheTest {

	public function init() {
		$this->cache = \Cache::redis();
	}

}

?>
