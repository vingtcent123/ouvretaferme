<?php

/**
 * Abstract class for cache handling
 * Timeouts observe the following rule: http://php.net/manual/fr/memcached.expiration.php
 */
abstract class Cache {

	/**
	 * Current static instances of the different cache implementations
	 *
	 * @var array
	 */
	private static $instances = [];

	private static $status = TRUE;

	protected $override;

	protected static $debug = FALSE;

	/**
	 * Returns a Memcache instance
	 *
	 * @return MemCacheCache
	 */
	public static function mem() {
		return self::getInstance('mem');
	}

	/**
	 * Returns a Redis instance
	 *
	 * @return RedisCache
	 */
	public static function redis() {
		return self::getInstance('redis');
	}

	/**
	 * Returns a DbCache instance
	 *
	 * @return DbCache
	 */
	public static function db() {
		return self::getInstance('db');
	}

	/**
	 * Returns an File cache instance
	 *
	 * @return FileCache
	 */
	public static function file() {
		return self::getInstance('file');
	}

	/**
	 * Get a cache object
	 *
	 * @param string $cacheType 'file', 'mem', 'db'
	 * @return Cache
	 */
	private static function getInstance(string $cacheType) {

		if(self::isDisabled()) {
			return new EmptyCache();
		}

		if(isset(self::$instances[$cacheType]) === FALSE) {

			switch($cacheType) {

				case 'file' :
					$class = 'FileCache';
					break;
				case 'mem' :
					$class = 'MemCacheCache';
					break;
				case 'db' :
					$class = 'DbCache';
					break;
				case 'redis' :
					$class = 'RedisCache';
					break;

			}

			self::$instances[$cacheType] = new $class();

		}

		return self::$instances[$cacheType];

	}

	/**
	 * Disable cache
	 */
	public static function disable() {
		self::$status = FALSE;
	}

	/**
	 * Enable cache
	 */
	public static function enable() {
		self::$status = TRUE;
	}

	/**
	 * Check if cache is enabled
	 */
	public static function isEnabled(): bool {
		return self::$status;
	}

	/**
	 * Check if cache is disabled
	 */
	public static function isDisabled(): bool {
		return !self::$status;
	}

	public function override($object, int $timeout, string $key = NULL) {

		if(LIME_ENV !== 'prod' and $timeout > 86400) {
			trigger_error("Cache::override() timeout can not exceed 86400 seconds");
		}

		$this->override = [$object, $timeout, $key];
		return $this;
	}

	/**
	 * Checks if a key exists
	 */
	abstract public function exists(string $key);

	/**
	 * Returns the value associated with this key, or FALSE
	 */
	abstract public function get(string $key);

	/**
	 * Sets a value associated with a key
	 */
	abstract public function set(string $key, $value, int $timeout = NULL);

	/**
	 * Change timeout of a key
	 */
	abstract public function setTimeout(string $key, int $newTimeout): bool;

	/**
	 * Delete a key
	 */
	abstract public function delete(string $key);

	/**
	 * Create a counter in the cache
	 * It allows use of increment() and decrement()
	 *
	 * Create a new counter if $value is provided, returns the current value of the counter otherwise
	 *
	 * @param string $key
	 * @param int $value
	 * @param int $timeout Timeout for the key
	 */
	public function counter(string $key, $value = NULL, int $timeout = NULL) {

		if($value === NULL) {
			return $this->get($key);
		}

		return $this->set($key, $value, $timeout);
	}

	/**
	 * Increment a value in the cache
	 * Create the key if it does not exist
	 *
	 * @param string $key
	 * @param int $value
	 * @param int $timeout Update timeout for the key
	 *
	 * @return int/bool Current increment value
	 */
	public function increment(string $key, int $value = 1, int $timeout = NULL) {
		throw new Exception("Not implemented");
	}

	/**
	 * Decrement a value in the cache
	 * Create the key if it does not exist
	 * Some drivers can't decrement under 0 (like Memcached)
	 *
	 * @param string $key
	 * @param int $value
	 * @param int $timeout Update timeout for the key
	 *
	 * @return int/bool Current increment value
	 */
	public function decrement(string $key, int $value = 1, int $timeout = NULL) {
		throw new Exception("Not implemented");
	}

	/**
	 * Append data to an existing item
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function append(string $key, $value) {
		throw new Exception("Not implemented");
	}

	/**
	 * Prepend data to an existing item
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function prepend(string $key, $value) {
		throw new Exception("Not implemented");
	}

	/**
	 * Get a cache from the cache if it exists, set it otherwise
	 *
	 */
	public function query(string $key, Closure $callback, int $timeout = 0): mixed {

		$result = $this->get($key);

		if($result === FALSE) {

			$result = $callback();

			$this->set($key, $result, $timeout);

		}

		return $result;

	}

	/**
	 * Get a cache from the cache if it exists, set it otherwise
	 *
	 */
	public function lock(string $key, Closure $onSuccess, Closure $onLocked, int $timeout = 0): mixed {

		if($this->add($key, 1, $timeout)) {
			return $onSuccess();
		} else {
			return $onLocked();
		}

	}

	/**
	 * Flush entire cache
	 */
	abstract public function flush();

	public static function setDebug($debug) {
		self::$debug = (bool)$debug;
	}

	private static $count = 0;

	protected function displayDebug(string $class, string $function, array $args, array $backtrace) {

		foreach($args as $key => $arg) {
			if(is_scalar($arg) === FALSE or strlen((string)$arg) > 250) {
				$args[$key] = '...';
			}
		}

		self::$count++;

		switch(Route::getRequestedWith()) {

			case 'cli' :
				echo "\033[01m".$class."\033[\00m::".$function."(".implode(', ', $args).")\n";
				break;

			default :

				$h = '';

				if(Route::getRequestedWith() === 'http') {
					$h .= '<div class="dev-cache">';
				}
				$h .= '<div class="dev-block">';
				$h .= '<div class="dev-extends" onclick="Dev.extends(this)"><button class="btn btn-primary btn-xs">'.self::$count.'</button> '.$class.'::'.$function.'('.implode(', ', $args).')</div>';
				$h .= '<div class="dev-trace">';
				$h .= \dev\TraceLib::getHttp($backtrace);
				$h .= '</div>';
				$h .= '</div>';

				if(Route::getRequestedWith() === 'http') {
					$h .= '</div>';
				}

				register_shutdown_function(function() use ($h) {
					echo $h;
				});

				break;

		}

	}

	protected function overrideGet(string $key) {
		return $this->override[0]->get($this->overrideKey($key));
	}

	protected function overrideSet(string $key, $value) {
		$this->override[0]->set($this->overrideKey($key), $value, $this->override[1]);
		$this->override = NULL;
	}

	protected function overrideKey(string $key) {

		if($this->override[2] !== NULL) {
			return $this->override[2];
		} else {
			return 'override-'.$key;
		}

	}

}

class EmptyCache extends Cache {

	use ServerCache;

	public function newInstance() {
		return NULL;
	}

	public function exists(string $key) {
		return FALSE;
	}

	public function get(string $key) {
		return FALSE;
	}

	public function set(string $key, $value, int $timeout = NULL) {
		return FALSE;
	}

	public function setTimeout(string $key, int $newTimeout): bool {
		return FALSE;
	}

	public function delete(string $key) {
		return FALSE;
	}

	public function flush() {
		return FALSE;
	}

}

/**
 * Cache system using a server such as Redis or Memcache
 */
trait ServerCache {

	/**
	 * List of servers
	 *
	 * @var array
	 */
	protected static $servers = [];

	/**
	 * Selected server
	 *
	 * @var string
	 */
	private $server;

	/**
	 * Static instances
	 */
	private static $clients = [];

	private static $stats = [];


	/**
	 * Register a new server
	 *
	 * @param string $name Server name
	 * @param string $host Server host
	 * @param string $port Server port
	 * @param array $options Options ('timeout' => int[2])
	 */
	public static function addServer(string $name, string $host, string $port, array $options = []) {
		self::$servers[$name] = ['host' => $host, 'port' => $port, 'options' => $options];
	}

	/**
	 * Close all open clients
	 */
	public static function closeClients() {
		foreach(self::$clients as $client) {
			$client->close();
		}
		self::$clients = [];
	}

	/**
	 * Change selected server
	 *
	 * @param string $server
	 */
	public function setServer(string $server) {
		$this->server = self::$servers[$server];
		return $this;
	}

	/**
	 * Restore default server
	 *
	 */
	public function restoreServer() {
		$this->server = self::$servers['default'];
		return $this;
	}

	/**
	 * Return cache instance from a server
	 *
	 * @return type
	 */
	public function getClient() {

		$key = $this->server['host'].':'.$this->server['port'];

		if(isset(self::$clients[$key]) === FALSE) {

			$timeout = $this->server['options']['timeout'] ?? 2;

			$client = $this->newInstance();
			$this->connect($client, $this->server['host'], $this->server['port'], $timeout);

			self::$clients[$key] = $client;

		}

		return self::$clients[$key];

	}

	abstract public function newInstance();

	public function debug() {

		if(self::$debug) {
			$backtrace = array_slice(debug_backtrace(), 1);
			$args = $backtrace[0]['args'] ?? [];
			$function = $backtrace[0]['function'];
			$class = __CLASS__;
			$this->displayDebug($class, $function, $args, $backtrace);
		}

	}

}

/**
 * Local RAM cache handling using MemCache
 * http://www.danga.com/memcached/
 */
class MemCacheCache extends Cache {

	use ServerCache;

	public function __construct() {

		if(class_exists("Memcached") === FALSE) {
			throw new Exception("Memcached is not installed...");
		}

		$this->setServer('default');

	}

	public function newInstance() {
		$instance = new Memcached();
		$instance->setOption(Memcached::OPT_BINARY_PROTOCOL, TRUE);
		$instance->setOption(Memcached::OPT_COMPRESSION, FALSE);
		return $instance;
	}

	public function connect($client, string $host, string $port, int $timeout) {
		$client->addServer($host, $port);
		$client->setOption(Memcached::OPT_CONNECT_TIMEOUT, $timeout * 1000);
	}

	public function close() {
		$this->getClient()->quit();
	}

	public function exists(string $key) {

		$this->get($key);

		return ($this->getClient()->getResultCode() === Memcached::RES_SUCCESS);

	}

	public function get(string $key) {

		if($this->override) {

			$result = $this->overrideGet($key);

			if($result !== FALSE) {
				$this->override = NULL;
				return $result;
			}

		}

		if(is_array($key)) {

			$values = $this->getClient()->getMulti($key);

			if(count($values) !== count($key)) {
				$values += array_combine($key, array_fill(0, count($key), FALSE));
			}

			return $values;

		} else {

			$result = $this->getClient()->get($key);

			$this->debug();

			if($this->override) {
				$this->overrideSet($key, $result);
			}

			return $result;
		}
	}

	public function set(string $key, $value, int $timeout = NULL) {
		return $this->write('set', $key, $value, $timeout);

	}

	public function setTimeout(string $key, int $newTimeout): bool {
		return $this->getClient()->touch($key, $newTimeout);
	}

	public function add(string $key, $value, int $timeout = NULL) {

		return $this->write('add', $key, $value, $timeout);

	}

	public function append(string $key, $value) {

		$this->getClient()->append($key, $value);
		$this->debug();

		return ($this->getClient()->getResultCode() === Memcached::RES_SUCCESS);

	}

	public function prepend(string $key, $value) {

		$this->getClient()->prepend($key, $value);
		$this->debug();

		return ($this->getClient()->getResultCode() === Memcached::RES_SUCCESS);

	}

	private function write(string $function, string $key, $value, int $timeout = NULL): bool {

		$timeout = max(0, is_null($timeout) ? 0 : $timeout);

		$result = $this->getClient()->$function($key, $value, $timeout);
		$this->debug();

		return $result;

	}

	public function delete(string $key) {

		$result = $this->getClient()->delete($key, 0);
		$this->debug();

		return $result;

	}

	public function increment(string $key, int $value = 1, int $timeout = NULL) {

		$result = $this->getClient()->increment($key, $value);

		if($result === FALSE) {
			$this->getClient()->add($key, $value);
		}

		$this->debug();

		if($timeout !== NULL) {
			$this->setTimeout($key, $timeout);
		}

		return $result;
	}

	public function decrement(string $key, int $value = 1, int $timeout = NULL) {

		$result = $this->getClient()->decrement($key, $value);

		if($result === FALSE) {
			$this->getClient()->add($key, -$value);
		}

		$this->debug();

		if($timeout !== NULL) {
			$this->setTimeout($key, $timeout);
		}

		return $result;
	}

	public function flush() {
		return $this->getClient()->flush();
	}

}

/**
 * Local RAM cache handling using Redis
 * http://www.redis.io/
 */
class RedisCache extends Cache {

	use ServerCache;

	public function __construct() {

		if(class_exists("Redis") === FALSE) {
			throw new Exception("Redis is not installed...");
		}

		$this->setServer('default');

	}

	public function newInstance() {
		return new Redis();
	}

	public function connect($client, string $host, string $port, int $timeout) {
		$client->connect($host, $port, $timeout);
		$client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
	}

	public function close() {
		$this->getClient()->close();
	}

	public function exists(string $key) {
		return ($this->getClient()->exists($key) !== FALSE);
	}

	public function get(string $key) {
		if($this->override) {

			$result = $this->overrideGet($key);

			if($result !== FALSE) {
				$this->override = NULL;
				return $result;
			}

		}

		if(is_array($key)) {
			return $this->getMulti($key);
		} else {

			$result = $this->getClient()->get($key);

			$this->debug();

			if($this->override) {
				$this->overrideSet($key, $result);
			}

			return $result;
		}
	}

	protected function getMulti(array $keys) {

		$client = $this->getClient();

		$values = array_fill(0, count($keys), FALSE);

		if($client) {

			$results = (array)$client->mGet($keys);

			$values = array_combine($keys, $results);

		}

		return $values;
	}

	public function set(string $key, $value, int $timeout = NULL) {
		return $this->write('set', $key, $value, $timeout);
	}

	public function setTimeout(string $key, int $newTimeout): bool {

		$newTimeout = (int)$newTimeout;

		if($newTimeout === 0) {
			return $this->getClient()->persist($key);
		} else if($newTimeout < 60 * 60 * 24 * 30) {
			return $this->getClient()->expire($key, $newTimeout);
		} else {
			return $this->getClient()->expireAt($key, $newTimeout);
		}

	}

	public function add(string $key, $value, int $timeout = NULL): bool {
		return $this->write('setnx', $key, $value, $timeout);
	}

	private function write(string $function, string $key, $value, int $timeout = NULL): bool {

		if(is_bool($value)) {
			trigger_error("Redis: can not put boolean values in cache", E_USER_NOTICE);
			return FALSE;
		}

		$result = $this->getClient()->$function($key, $value);

		if($result and $timeout) {
			$this->setTimeout($key, $timeout);
		}

		$this->debug();

		return $result;

	}

	public function delete(string $key) {

		$result = $this->getClient()->del($key);
		$this->debug();

		return $result === 1;

	}
	public function counter(string $key, $value = NULL, int $timeout = NULL) {

		if($value === NULL) {
			return (int)$this->get($key);
		}

		if($this->exists($key)) {
			$this->delete($key);
		}

		$this->getClient()->incrBy($key, $value);

		if($timeout !== NULL) {
			$this->setTimeout($key, $timeout);
		}

		return TRUE;

	}

	public function increment(string $key, int $value = 1, int $timeout = NULL) {

		$result = $this->getClient()->incrBy($key, $value);
		$this->debug();

		if($timeout !== NULL) {
			$this->setTimeout($key, $timeout);
		}

		return $result;
	}

	public function decrement(string $key, int $value = 1, int $timeout = NULL) {

		$result = $this->getClient()->decrBy($key, $value);
		$this->debug();

		if($timeout !== NULL) {
			$this->setTimeout($key, $timeout);
		}

		return $result;
	}

	public function flush() {
		return $this->getClient()->flushAll();
	}

}

/**
 * db cache handling using a module
 */
class DbCache extends Cache {

	protected $mCache;

	public function exists(string $key): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		$preparedKey = md5($key);

		return $this->mCache
			->whereKey($preparedKey)
			->where('(expireAt > NOW() OR expireAt IS NULL)')
			->exists();

	}

	public function get(string $key) {

		if($this->override) {

			$result = $this->overrideGet($key);

			if($result !== FALSE) {
				$this->override = NULL;
				return $result;
			}

		}

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		if(is_array($key)) {

			$preparedKeys = array_map('md5', $key);

			$cCache = $this->mCache
				->select('key', 'value')
				->whereKey('IN', $preparedKeys)
				->where('(expireAt > NOW() OR expireAt IS NULL)')
				->getCollection(NULL, NULL, 'key');

			$values = [];

			foreach($key as $position => $entry) {

				if(isset($cCache[$preparedKeys[$position]])) {
					$values[$entry] = unserialize($cCache[$preparedKeys[$position]]['value']);
				} else {
					$values[$entry] = FALSE;
				}

			}

			return $values;

		} else {

			$preparedKey = md5($key);

			$value = \util\Cache::model()
				->whereKey($preparedKey)
				->where('(expireAt > NOW() OR expireAt IS NULL)')
				->getValue('value');

			if($value !== NULL) {
				$result = unserialize($value);
			} else {
				return FALSE;
			}

			if($this->override) {
				$this->overrideSet($key, $result);
			}

			return $result;

		}

	}

	public function set(string $key, $value, int $timeout = NULL): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		$eCache = $this->getWrite($key, $value, $timeout);

		if($eCache !== FALSE) {
			$this->mCache
				->option('add-replace')
				->insert($eCache);

			return TRUE;
		} else {
			return FALSE;
		}

	}

	public function setTimeout(string $key, int $newTimeout): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		return $this->mCache
			->whereKey(md5($key))
			->update([
				'expireAt' => $this->getWriteTimeout($newTimeout)
			]) > 0;


	}

	public function add(string $key, $value, int $timeout = NULL): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		if($this->exists($key) === FALSE) {

			$eCache = $this->getWrite($key, $value, $timeout);

			if($eCache !== FALSE) {

				$this->mCache
					->option('add-replace')
					->insert($eCache);

				return TRUE;

			} else {
				return FALSE;
			}

		} else {
			return FALSE;
		}

	}

	protected function getWrite(string $key, $value, int $timeout = NULL): \util\Cache {

		$timeout = (int)$timeout;

		if($timeout < 0) {
			$this->delete($key);
			return FALSE;
		}

		if(luck(0.01)) {
			$this->clean();
		}

		$eCache = new \util\Cache([
			'key' => md5($key),
			'value' => serialize($value),
			'expireAt' => $this->getWriteTimeout($timeout),
		]);

		return $eCache;

	}

	protected function getWriteTimeout(int $timeout): ?Sql {

		$timeout = (int)$timeout;

		if($timeout === 0) {
			return NULL;
		} else if($timeout < 60 * 60 * 24 * 30) {
			return new \Sql('NOW() + INTERVAL '.$timeout.' SECOND');
		} else {
			return new \Sql('FROM_UNIXTIME('.$timeout.')');
		}

	}

	public function delete(string $key): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		$preparedKey = md5($key);

		return $this->mCache
				->whereKey($preparedKey)
				->delete() > 0;

	}

	public function flush(): bool {

		if($this->mCache === NULL) {
			$this->mCache = \util\Cache::model();
		}

		$this->mCache
			->all()
			->delete();

		return TRUE;

	}

	protected function clean() {

		$this->mCache
			->where('expireAt <= NOW()')
			->delete();

		$this->mCache->optimize();

	}

}

/**
 * local ram cache handling using file
 */
class FileCache extends Cache {

	/**
	 * Cache path
	 *
	 * @var string
	 */
	protected $path;

	public function __construct() {

		$this->setPath('/tmp/lime-cache');

	}

	/**
	 * Update cache path
	 *
	 * @param string $path
	 */
	public function setPath(string $path) {
		$this->path = $path;
		if(is_dir($path) === FALSE) {
			mkdir($path, 0777);
		}
		return $this;
	}

	public function exists(string $key): bool {
		return is_file($this->path.'/'.md5($key));
	}

	public function get(string $key) {

		if(is_array($key)) {

			$values = [];

			foreach($key as $entry) {
				$values[$entry] = $this->get($entry);
			}

			return $values;

		} else {

			if($this->exists($key)) {

				$content = file_get_contents($this->path.'/'.md5($key));
				list($timeout, $value) = unserialize($content);

				if($timeout !== NULL and time() <= $timeout) {
					$this->delete($key);
					return FALSE;
				}

				return $value;

			} else {
				return FALSE;
			}

		}

	}

	public function set(string $key, $value, int $timeout = NULL): bool {

		$content = serialize([$this->getWriteTimeout($timeout), $value]);
		file_put_contents($this->path.'/'.md5($key), $content);

		return TRUE;

	}

	public function add(string $key, $value, int $timeout = NULL): bool {

		if($this->exists($key)) {
			return FALSE;
		}

		$this->set($key, $value, $timeout);

		return TRUE;

	}

	public function setTimeout(string $key, int $newTimeout): bool {

		$value = $this->get($key);

		if($value !== FALSE) {
			$this->set($key, $value, $newTimeout);
		}

		return TRUE;

	}

	protected function getWriteTimeout(int $timeout): ?int {

		$timeout = (int)$timeout;

		if($timeout === 0) {
			return NULL;
		} else if($timeout < 60 * 60 * 24 * 30) {
			return time() + $timeout;
		} else {
			return $timeout;
		}

	}

	public function delete(string $key): bool {

		if($this->exists($key)) {
			return unlink($this->path.'/'.md5($key));
		} else {
			return FALSE;
		}

	}

	public function flush(): bool {

		exec('rm -rf '.$this->path.'/*');
		clearstatcache();
		return TRUE;

	}

}
?>
