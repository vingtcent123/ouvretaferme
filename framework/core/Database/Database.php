<?php
/**
 * This class is a factory for database connections.>
 */

class Database extends PDO {

	/**
	 * List of registered servers
	 *
	 * @var array
	 */
	protected static $servers = [];

	/**
	 * List of registered databases
	 *
	 * @var array
	 */
	protected static $bases = [];

	/**
	 * List of registered packages and their base
	 *
	 * @var array
	 */
	protected static $packages = [];

	/**
	 * Connection parameters
	 *
	 * @var array
	 */
	protected static $connections = [];

	private $instances = [];

	/**
	 * Current parameters
	 *
	 * @var string
	 */
	protected $params;

	private static $count = 0;
	private static $thisTime;
	private static $time = 0;

	/**
	 * Class configuration
	 */
	private static $debug = FALSE; // Display each query
	private static $mon = NULL; // Enable/disable monitoring

	const MON_LUCK = 2;

	private $monQueryStatus = FALSE;
	private static $monQueries = [];
	private static $monQueriesPosition = 0;

	/**
	 * Option to reconnect in case of failure.
	 * Set as string so in order to avoid overriding some PDO constants.
	 * This affects $this->maxReconnections and $this->reconnections
	 */
	const MAX_RECONNECTIONS = "Max number of reconnections";

	private $maxReconnections;
	private $reconnections = 0;

	/**
	 * DatabaseFactory constructor
	 *
	 * @param string $package A package name
	 * @param array $options Options for the connection
	 */
	public function __construct(string $package, array $options = []) {

		if(strpos($package, '@') !== FALSE) {
			list($package, $at) = explode('@', $package, 2);
		} else {
			$at = NULL;
		}

		// Check is the package is registered
		if(isset(self::$packages[$package]) === FALSE) {
			throw new ConnectionDatabaseException("Package '".$package."' is not registered");
		}

		// Check is the base is registered
		if(isset(self::$bases[self::$packages[$package]]) === FALSE) {
			throw new ConnectionDatabaseException("Base '".self::$packages[$package]."' is not registered");
		}

		$this->params = self::$servers[self::$bases[self::$packages[$package]]];

		if($at !== NULL) {

			if(isset($this->params['@'.$at])) {
				$this->params = $this->params['@'.$at] + $this->params;
			} else {
				throw new ConnectionDatabaseException("Access '".$package."@".$at."' is not registered");
			}

		}

		$login = $this->params['login'];
		$password = $this->params['password'];

		$options += $this->params['options'] ?? [];
		$options += [
			PDO::ATTR_PERSISTENT => FALSE,
		];

		if($this->params['type'] === 'MySQL') {
			$options += [
				PDO::MYSQL_ATTR_DIRECT_QUERY => TRUE,
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
			];
		}

		$this->maxReconnections = $options[self::MAX_RECONNECTIONS] ?? 3; // 3 is default.

		try {

			$dsn = $this->dsn($this->params);

			parent::__construct(
				$dsn,
				$login, $password,
				$options
			);

		}
		catch(PDOException $e) {

			$message = $e->getMessage();

			if(preg_match("/SQLSTATE\[([0-9]+)\]\s*\[([0-9]+)\]/si", $e->getMessage(), $result)) {

				$sqlSpecificCode = (int)$result[2];

				$code = $sqlSpecificCode;

			} else {
				$code = $e->getCode();
			}

			throw new ConnectionDatabaseException($message, 9990000 + $code);

		}

	}

	private function dsn(array $params): string {

		switch($params['type']) {
			case 'MySQL' :
				return "mysql:host=".$params['host'];
			default :
				throw new Exception($params['type'].' is not supported');
		}

	}

	/**
	 * Add a new server
	 *
	 * [
	 *		'type' => 'MySQL',
	 *		'host' => '127.0.0.1',
	 *		'port' => 3306,
	 *		'login' => 'root',
	 *		'password' => '???',
	 *		'bases' => ['dev', 'session'],
	 *		'@replication' => [
	 *			'host' => '127.0.0.1',
	 *		]
	 *	]
	 *
	 * @param array $server
	 */
	public static function addServer(array $server) {

		array_expects($server, ['type', 'host', 'port', 'login', 'password', 'bases']);

		$position = count(self::$servers);

		self::$servers[$position] = $server;

		foreach($server['bases'] as $base) {
			self::$bases[$base] = $position;
		}

	}

	/**
	 * Reset server configuration
	 */
	public static function resetServers() {
		self::$servers = [];
		self::$bases = [];
	}

	/**
	 * Link packages to bases
	 *
	 * [
	 *		'Cache' => 'cache',
	 *	]
	 *
	 */
	public static function setPackages(array $packages): void {
		self::$packages = $packages;
	}

	/**
	 * Return registered packages
	 *
	 * @return array
	 */
	public static function getPackages(): array {
		return self::$packages;
	}

	/**
	 * Reset package configuration
	 */
	public static function resetPackages() {
		self::$packages = [];
	}

	/**
	 * Check if a connection exists
	 */
	public static function hasConnection(string $connection): bool {
		return isset(self::$connections[$connection]);
	}

	/**
	 * Get Database files
	 */
	public function __get(string $property) {

		if(isset($this->instances[$property]) === FALSE) {

			$class = $this->params['type'].ucfirst($property);
			$file = LIME_DIRECTORY.'/framework/core/Database/drivers/'.$this->params['type'].'/'.$class.'.php';

			require_once $file;

			$this->instances[$property] = new $class($this);

		}

		return $this->instances[$property];


	}

	/**
	 * Get server parameters for a package
	 *
	 * @param string $package
	 *
	 * @return array
	 */
	public static function SERVER(string $package): array {

		if(strpos($package, '@') !== FALSE) {
			list($package, $at) = explode('@', $package, 2);
		} else {
			$at = NULL;
		}

		$base = self::getBase($package);

		if($base === NULL) {
			throw new Exception('Could not find package \''.$package.'\'');
		}

		$serverId = self::$bases[$base] ?? NULL;

		if($serverId === NULL) {
			throw new Exception('Could not find server for database \''.$base.'\'');
		}

		$server = self::$servers[$serverId] ?? NULL;

		if($server === NULL) {
			throw new Exception('Could not find server for package \''.$package.'\'');
		}

		if($at !== NULL) {
			return $server['@'.$at] + $server;
		} else {
			return $server;
		}

	}

	/**
	 * Get base for a package
	 *
	 * @param string $package
	 *
	 * @return string / null
	 */
	public static function getBase(string $package): string {
		if(isset(self::$packages[$package]) === FALSE) {
			throw new \Exception('Package \''.$package.'\' is not registered');
		}
		return self::$packages[$package];
	}

	/**
	 * Get a param of the current connection
	 *
	 * @param string $name Param name
	 *
	 * @return string / null Param value
	 */
	public function getParam(string $name) {
		return $this->params[$name] ?? NULL;
	}

	/**
	 * Execute a SQL query
	 *
	 * $hasRows must be set to TRUE for queries that return rows, such as OPTIMIZE, ALTER...
	 */
	public function exec($statement, bool $hasRows = FALSE): int|false {

		self::$count++;

		$trace = $this->trace($statement);

		try {

			if($hasRows) {
				$result = $this->query($statement);
				if($result !== FALSE) {
					$result->closeCursor();
				}
			} else {
				$result = parent::exec($statement);
			}

			$this->untrace($trace, $statement, __FUNCTION__);

		} catch(PDOException $e) {
			$this->untrace($trace, $statement, __FUNCTION__);
			$this->handleFailure($statement, $e->errorInfo);
		}


		try {
			$return = $this->handleResult($statement, $result);
			$this->reconnections = 0;
			return $return;

		}
		catch(LostServerException $e) {

			if($this->reconnections < $this->maxReconnections) {
				$this->reconnections++;
				return $this->exec($statement); // replay.
			}
			throw $e;
		}
		catch(DeadLockException $e) {

			return $this->exec($statement);

		}

	}

	/**
	 * Perform a select SQL query
	 *
	 */
	public function select($statement, ...$options) {

		self::$count++;

		$trace = $this->trace($statement);

		if($options === []) {
			$options = [PDO::FETCH_ASSOC];
		}

		$result = $this->query($statement, ...$options);

		$this->untrace($trace, $statement, __FUNCTION__);

		try {
			$return = $this->handleResult($statement, $result);
			$this->reconnections = 0;
			return $return;

		}
		catch(LostServerException $e) {

			if($this->reconnections < $this->maxReconnections) {
				$this->reconnections++;
				return $this->select($statement); // replay.
			}
			throw $e;
		}

	}

	/**
	 * Execute a SQL query and returns a unique result
	 *
	 * @param string $sql The SQL query
	 * @return mixed The first field of the first result, or false if there is no result
	 */
	public function selectUnique($statement) {

		$rs = $this->select($statement, PDO::FETCH_NUM);
		$row = $rs->fetch();
		$rs->closeCursor();
		return $row ? current($row) : NULL;

	}

	/**
	 * Handle SQL errors
	 *
	 */
	protected function handleResult($statement, $result) {

		if($result !== FALSE) {
			return $result;
		} else {
			$this->handleFailure($statement, $this->errorInfo());
		}

	}

	protected function handleFailure(string $statement, array $errorInfo): void {

		list($sqlCode, $sqlSpecificCode, $sqlError) = $errorInfo;

		$sqlSpecificCode = (int)$sqlSpecificCode;

		// 2006: "MySQL Server has gone away". 2013: "Lost connection to MySQL server during query"
		if($sqlSpecificCode === 2006 or $sqlSpecificCode === 2013) {
			$this->__construct(NULL); // reconnect
			throw new LostServerException($sqlError);
		}

		// 1213: "Deadlock found when trying to get lock; try restarting transaction"
		if($sqlSpecificCode === 1213) {
			throw new DeadLockException($sqlError);
		}

		throw new QueryDatabaseException(
			$sqlError." - Full SQL query: ".$statement, (int)$sqlSpecificCode
		);

	}


	public function trace(&$statement) {

		if(self::$mon) {
			$this->checkQueryMon();
		}

		return microtime(TRUE);

	}

	public function untrace($trace, $sql, $function) {

		$time = microtime(TRUE) - $trace;

		if(self::$mon) {
			$this->doQueryMon($sql, $time);
			$this->registerQueryMon($sql, $time);
		}

		if(self::$debug) {

			$isFirstQuery = (self::$time === 0);

			self::$thisTime[self::$count] = $time;
			self::$time += self::$thisTime[self::$count];

			$time = self::$thisTime[self::$count];

			$backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 0);

			foreach($backtrace as $key => $value) {

				if(
					strpos($value['file'], LIME_DIRECTORY.'/framework/core/Database/') === 0 or
					$value['file'] === LIME_DIRECTORY.'/framework/core/Module.php'
				) {
					unset($backtrace[$key]);
					continue;
				} else {
					break;
				}

			}

			// Print debug information
			$sql = trim(preg_replace("/[ \n\r\t]+/s", " ", $sql));


			switch(Route::getRequestedWith()) {

				case 'cli' :
					echo ''.$function.'() (\033[01m'.self::$count.'\033[00m : ';
					echo $sql.' (\033[31m'.sprintf("%.5f", self::$thisTime[self::$count]).' s.\033[00m)'."\n";
					break;

				default :

					$h = '';

					if(Route::getRequestedWith() === 'http') {
						$h .= '<div class="dev-sql">';
					}

					$h .= '<div class="dev-block">';
					$h .= '<div class="dev-extends" onclick="Dev.extends(this)"><span class="btn btn-primary btn-xs">'.self::$count.'</span> '.$this->api->color($sql).' (<span class="sql-query-time">'.sprintf("%.5f", self::$thisTime[self::$count]).' s.'.'</span>)</div>';
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

	}

	/**
	 * Start query monitoring
	 *
	 * @param string $class Handler class
	 * @param int $luck Handler luck
	 */
	public static function startMon(string $class, int $luck = self::MON_LUCK) {

		if(luck($luck)) {

			$handler = new $class;

			if(self::$mon === NULL) {
				register_shutdown_function([$handler, 'handleRequestAndTables']);
			}

			self::$mon = [$handler, $handler->checkQueries(), $handler->getQueryTimeLimit(), $handler->getQueryNumberLimit()];

		}

	}

	/**
	 * Stop query monitoring
	 *
	 * @param bool $mon
	 */
	public static function stopMon() {
		self::$mon = NULL;
	}

	public static function getMonQueries() {
		return self::$monQueries;
	}

	/**
	 * Do query monitoring
	 *
	 */
	protected function doQueryMon(string $sql, int $time) {

		if($this->monQueryStatus === FALSE) {
			return;
		}

		if(self::$mon[1] === FALSE) {
			return;
		}

		if($time * 1000 < self::$mon[2]) {
			return;
		}

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		foreach($backtrace as $key => $trace) {
			if(($trace['file'] ?? NULL) === LIME_DIRECTORY.'/framework/core/Page.php') {
				break;
			}
		}

		$backtrace = array_slice($backtrace, 2, $key - 1);
		$duration = $this->getQueryDuration($sql);
		$sql = mb_substr($sql, 0, 1024);

		list($connection, $table) = $this->extractMonInfos($sql);

		self::$mon[0]->handleQuery(
			$this->params['host'],
			$connection,
			$table,
			$sql,
			$backtrace,
			$duration
		);

	}

	protected function registerQueryMon(string $sql, int $time) {

		if(self::$monQueriesPosition > self::$mon[3]) {
			return;
		}

		$mode = NULL;
		list($connection, $table) = $this->extractMonInfos($sql, $mode, $command);
		$duration = $this->getQueryDuration($sql);

		self::$monQueries[self::$monQueriesPosition++] = [
			$this->params['host'],
			$connection,
			$table,
			$time,
			$duration,
			$mode,
			$command
		];

	}

	protected function checkQueryMon() {

		if($this->monQueryStatus === TRUE) {
			return;
		}

		$this->monQueryStatus = TRUE;

		parent::exec('SET profiling = 1');

	}

	protected function getQueryDuration(string $sql) {

		$sql = trim($sql);

		// Exclude unions from profiling
		if(strpos($sql, ' UNION ') === FALSE and strpos($sql, 'INSERT ') !== 0) {
			try {
				$rs = parent::query('SHOW PROFILE LIMIT 100', PDO::FETCH_ASSOC);
			}
			catch(Exception $e) {
				trigger_error($e->getMessage(), E_USER_NOTICE);
				$rs = NULL;
			}
		} else {
			$rs = NULL;
		}

		if($rs) {
			$duration = $rs->fetchAll();
		} else {
			$duration = [];
		}

		return $duration;

	}

	protected function extractMonInfos(string $sql, string &$mode = NULL, string &$command = NULL): array {

		$sql = ltrim($sql);
		$table = NULL;
		$connection = NULL;

		$hasSelect = (stripos($sql, 'SELECT') === 0);
		$hasDelete = (stripos($sql, 'DELETE') === 0);

		if($hasSelect or $hasDelete) {
			if(preg_match("/^\s*(SELECT|DELETE)(.*?)FROM\s+([a-z0-9\_`\.]+)(\s+|$)/si", $sql, $result)) {
				$table = $result[3];
				$mode = $hasSelect ? 'read' : 'write';
			}

			if($hasSelect) {
				$command = 'SELECT';
			} else if($hasDelete) {
				$command = 'DELETE';
			}

		} else {

			$hasUpdate = (stripos($sql, 'UPDATE ') === 0);
			$hasInsert = (stripos($sql, 'INSERT ') === 0);
			$hasReplace = (stripos($sql, 'REPLACE') === 0);

			if($hasUpdate or $hasInsert or $hasReplace) {

				if(preg_match("/^(UPDATE|REPLACE|INSERT)(?:\s+(?:IGNORE|INTO))*\s+([a-z0-9\_`\.]+)/si", $sql, $result)) {
					$table = $result[2];
					$mode = 'write';
				}

				if($hasUpdate) {
					$command = 'UPDATE';
				} else if($hasInsert) {
					$command = 'INSERT';
				} else if($hasReplace) {
					$command = 'REPLACE';
				}

			}

		}

		if($table !== NULL) {

			// Remove `
			$table = str_replace('`', '', $table);

			// Remove database name
			if(strpos($table, '.') !== FALSE) {
				list($database, $table) = explode('.', $table);
			}

			// Remove numeric suffix
			$table = preg_replace("/\_[0-9]+$/si", "", $table);
			$model = ucfirst($table).'Model';

			if(class_exists($model)) {

				$mElement = new $model;
				$connection = $mElement->getPackage();

				$position = strpos($this->params['connection'], '@');
				if($position !== FALSE) {
					$connection .= substr($this->params['connection'], $position);
				}

			} else {
				$connection = '?';
			}

		}

		return [$connection, $table];

	}

	/**
	 * Change 'debug' parameter
	 *
	 * @param bool $trace
	 */
	public static function setDebug($debug) {
		self::$debug = (bool)$debug;
	}

	/**
	 * Get actual value of 'debug' parameter
	 *
	 * @return bool
	 */
	public static function getDebug() {
		return self::$debug;
	}

}

/*
 * Generic object for Database libraries
 *
 */
class DatabaseObject {

	/**
	 * Database object
	 *
	 * @var Database
	 */
	protected $db;

	/**
	 * DatabaseFactory constructor
	 *
	 * @param string $db A connection name or a Database object
	 */
	public function __construct($db) {

		if($db instanceof Database) {
			$this->db = $db;
		} else {
			$this->db = new Database($db);
		}

	}

}

/**
 * Exception thrown if a SQL query fails
 */
class QueryDatabaseException extends Exception {

	const ERROR_SYNTAX = 1149;
	const ERROR_DUPLICATE_ENTRY = 1062;
	const ERROR_CRASH = 1194;

}

/**
 * Exception thrown if a server connection is lost. This can happen when
 * several processes try to access the same resource in parallel, or when
 * a network or server timeout occurs. In that case, the query is re-run.
 */
class LostServerException extends Exception {}

/**
 * Exception thrown if a dead lock occurs.
 * In that case, the query is re-run
 */
class DeadLockException extends Exception {}


/**
 * Exception thrown if the connection to the database fails
 */
class ConnectionDatabaseException extends Exception {

	const ERROR_DENIED = 1044;

	public function handle() {
		$this->write();
	}

}
?>
