<?php
/**
 * Administration for Modules
 */
class ModuleAdministration {

	/**
	 *  Database connection
	 *
	 *  @var Database
	 */
	protected Database $db;

	/**
	 * Element module
	 *
	 * @var Module
	 */
	protected ModuleModel $mElement;


	/**
	 * Initialize the class
	 *
	 * @param string $name Module name
	 */
	public function __construct(string $name) {

		$reflection = new ReflectionClass('\\'.$name.'Model');

		if($reflection->isAbstract()) {
			throw new Exception("Element is abstract");
		}

		$this->mElement = $reflection->newInstance();

		// Create a DB connection
		$this->db = new Database($this->mElement->getPackage());

	}


	/**
	 * Install the table
	 *
	 */
	public function init() {

		$this->createTable();

	}

	/**
	 * Rebuild the table
	 *
	 *	Only copy fields existing in the backup and the new table
	 *	- Old fields will be ignored
	 *	- New fields will have the default MySQL value
	 *
	 */
	public function rebuild(array $default) {

		$base = $this->mElement->getDb();
		$database = $this->mElement->pdo();
		$suffixes = $this->mElement->getSuffixes();

		$tables = [];

		// List tables
		foreach($suffixes as $suffix) {
			if($suffix === '') {
				$tables[] = $this->mElement->getTable();
			} else {
				$tables[] = $this->mElement->getTable().'_'.$suffix;
			}
		}

		// Checks default values
		foreach($default as $key => $value) {

			if($this->mElement->hasProperty($key) === FALSE) {
				throw new Exception('Invalid property \''.$key.'\'');
			}

		}

		foreach($tables as $table) {

			$intersect = array_intersect($this->getFieldsName($database, $base, $table), array_keys($default));

			if($intersect) {
				throw new Exception('Can not set a default value for properties \''.implode('\', \'', $intersect).'\' (already existing)');
			}

		}

		// Move old tables
		$autoIncrements = [];

		foreach($tables as $table) {

			$autoIncrements[$table] = $database->select('SHOW TABLE STATUS FROM '.$database->api->field($base).' LIKE \''.$table.'\'')->fetch(PDO::FETCH_ASSOC)["Auto_increment"] ?? NULL;

			$this->renameTable($database, $base, $table, 'backup_'.$table);

		}

		// Create new tables
		$this->createTable($autoIncrements);

		// Move data from old tables to new tables and delete old tables
		try {

			foreach($tables as $table) {

				$backupTable = 'backup_'.$table;

				$copyFields = array_intersect(
					$this->getFieldsName($database, $base, $backupTable),
					$this->getFieldsName($database, $base, $table)
				);

				$tableName = $table;
				$backupTableName = $backupTable;

				$defaultFields = array_keys($default);
				if($defaultFields) {
					$copyFields = array_diff($copyFields, $defaultFields);
				}

				$copyFields = array_map([$database->api, 'field'], $copyFields);

				$fromFields = $copyFields;
				$toFields = $copyFields;

				foreach($default as $key => $value) {

					$fromFields[] = $this->mElement->format($value);
					$toFields[] = $database->api->field($key);

				}

				$this->rebuildInsert($database, $base, $tableName, $backupTableName, $fromFields, $toFields);

			}

			// Drop backup tables
			foreach($tables as $table) {
				$database->manager->dropTable($base, 'backup_'.$table);
			}

			$error = NULL;

		} catch(QueryDatabaseException $e) {

			$error = $e->getMessage();

			foreach($tables as $table) {

				$database->manager->dropTable($base, $table);
				$this->renameTable($database, $base, 'backup_'.$table, $table);

			}

		}

		if($error !== NULL) {
			throw new Exception($error);
		}

	}

	private function renameTable(\PDO $database, string $base, string $table, string $newTable) {

		// A table already exist, so we can rename it
		if($database->manager->hasTable($base, $table)) {
			$database->manager->renameTable($base, $table, $newTable);
		}

		// Check if backup table exists
		if($database->manager->hasTable($base, $newTable) === FALSE) {
			throw new Exception("Table '".$base.'.'.$newTable."' can not be found, rebuilding aborted...");
		}

	}

	private function rebuildInsert(\PDO $database, string $base, string $table, string $backupTable, array $fromFields, array $toFields) {

		$sql = 'INSERT INTO '.$database->api->field($base).'.'.$database->api->field($table).'('.implode(', ', $toFields).') SELECT '.implode(', ', $fromFields).' FROM '.$database->api->field($base).'.'.$database->api->field($backupTable);

		$database->exec($sql);

	}

	private function getFieldsName(\PDO $database, string $base, string $table): array {
		$fields = $database->manager->getFields($base, $table);
		$fieldsName = array_column($fields, 'name');
		return $fieldsName;
	}

	public function createTable(array $autoIncrements = []) {

		$fields = [];

		$this->db->manager;

		foreach($this->mElement->getProperties() as $name) {

			$range = $this->mElement->getPropertyRange($name);
			$type = $this->mElement->getPropertyType($name);

			if($type === 'view') {
				continue;
			}

			switch($type) {

				case 'int8' :
				case 'int16' :
				case 'int24' :
				case 'int32' :
				case 'int64' :

					if($range === NULL or $range[0] < 0 or $range[0] === NULL) {
						$structure = $type;
					} else {
						$structure = [$type, DatabaseManager::UNSIGNED];
					}

					break;

				case 'serial8' :
				case 'serial16' :
				case 'serial24' :
				case 'serial32' :
				case 'serial64' :

					$structure = [$type, NULL];

					break;

				case 'decimal' :
					['digits' => $digits, 'decimal' => $decimal] = $this->mElement->getProperty($name);
					$structure = ['decimal', $digits, $decimal];
					break;

				case 'float32' :
					if($range === NULL) {
						$structure = 'float32';
					} else {

						$structure = $range[0] < 0 ? 'float32' : ['float32', DatabaseManager::UNSIGNED];

					}

					break;

				case 'float64' :
					if($range === NULL) {
						$structure = 'float64';
					} else {

						$structure = $range[0] < 0 ? 'float64' : ['float64', DatabaseManager::UNSIGNED];

					}
					break;

				case 'textFixed' :
				case 'binaryFixed' :
					if($range === NULL or $range[1] === NULL or $range[1] > Filter::MAX_STRING_SIZE) {
						throw new Exception("Max size of ".Filter::MAX_STRING_SIZE." expected for ".$name."");
					} else {

						$max = $range[1];
						$structure = [$type, $max];

					}

					if($type === 'textFixed') {
						$structure[2] = $this->mElement->getPropertyCharset($name);
						$structure[3] = $this->mElement->getPropertyCollate($name);
					}

					break;

				case 'binary8' :
					if($range === NULL or $range[1] === NULL) {
						$max = NULL;
					} else {
						$max = $range[1];
					}
					$structure = ['binary8', $max];
					break;

				case 'binary16' :
					if($range === NULL or $range[1] === NULL) {
						$max = NULL;
					} else {
						$max = $range[1];
					}
					$structure = ['binary16', $max];
					break;

				case 'binary24' :
					if($range === NULL or $range[1] === NULL) {
						$max = NULL;
					} else {
						$max = $range[1];
					}
					$structure = ['binary24', $max];
					break;

				case 'binary32' :
					if($range === NULL or $range[1] === NULL) {
						$max = NULL;
					} else {
						$max = $range[1];
					}
					$structure = ['binary32', $max];
					break;

				case 'email' :
					$structure = ['text8', 100, 'utf8', 'general'];
					break;

				case 'url' :
					$structure = ['text16', Filter::MAX_TEXT16_SIZE, 'utf8'];
					break;

				case 'fqn' :
					$structure = ['text8', Filter::MAX_FQN_SIZE, 'ascii'];
					break;

				case 'md5' :
					$structure = ['binaryFixed', 16];
					break;

				case 'sid' :
					$structure = ['textFixed', 40, 'ascii'];
					break;

				case 'ipv6' :
					$structure = ['textFixed', 39, 'ascii'];
					break;

				case 'color' :
					$structure = ['textFixed', 7, 'ascii'];
					break;

				case 'ipv4' :
					$structure = 'int32';
					break;

				case 'ip' :
					$structure = ['textFixed', 39, 'ascii'];
					break;

				case 'text8' :
				case 'editor8' :
					$structure = ['text8', Filter::MAX_TEXT8_SIZE, $this->mElement->getPropertyCharset($name), $this->mElement->getPropertyCollate($name)];
					break;

				case 'text16' :
				case 'editor16' :
					$structure = ['text16', NULL, $this->mElement->getPropertyCharset($name), $this->mElement->getPropertyCollate($name)];
					break;

				case 'text24' :
				case 'editor24' :
					$structure = ['text24', NULL, $this->mElement->getPropertyCharset($name), $this->mElement->getPropertyCollate($name)];
					break;

				case 'text32' :
				case 'editor32' :
					$structure = ['text32', NULL, $this->mElement->getPropertyCharset($name), $this->mElement->getPropertyCollate($name)];
					break;

				case 'json' :
					$structure = ['json', NULL, $this->mElement->getPropertyCharset($name), $this->mElement->getPropertyCollate($name)];
					break;

				case 'date' :
					$structure = 'date';
					break;

				case 'datetime'  :
					$structure = 'datetime';
					break;

				case 'month'  :
					$structure = ['textFixed', 7, 'ascii'];
					break;

				case 'week'  :
					$structure = ['textFixed', 8, 'ascii'];
					break;

				case 'time'  :
					$structure = 'time';
					break;

				case 'bool' :
					$structure = 'bool';
					break;

				case 'element8' :
				case 'element16' :
				case 'element24' :
				case 'element32' :
				case 'element64' :

					$field = str_replace('element', 'int', $type);
					$sign = DatabaseManager::UNSIGNED;

					$structure = [$field, $sign];
					break;

				case 'enum' :
					$structure = ['enum', $this->mElement->getPropertyEnum($name)];
					break;

				case 'set' :
					$structure = ['int32', DatabaseManager::UNSIGNED];
					break;

				case 'collection' :

					$listType = $this->mElement->getProperty($name)[1];

					if($listType === 'serial8') {
						$chunk = 1;
					} else if($listType === 'serial16') {
						$chunk = 2;
					} else if($listType === 'serial32') {
						$chunk = 4;
					} else {
						$chunk = 8;
					}

					if($range === NULL or $range[1] === NULL) {
						$max = floor(255 / $chunk);
						$type = 'binary8';
					} else {

						$max = $range[1];
						$max *= $chunk;

						if($max <= floor(255 / $chunk)) {
							$type = 'binary8';
						} else if($max <= floor(65535 / $chunk)) {
							$type = 'binary16';
						} else if($max <= floor(16777215 / $chunk)) {
							$type = 'binary24';
						} else {
							$type = 'binary32';
						}
					}

					$structure = [$type, $max];
					break;

				case 'point' :
				case 'polygon' :
					$structure = $type;
					break;

				default :
					throw new Exception("Type '".$type."' does not exist");

			}

			$fields[$name] = $this->buildField($name, $structure);

		}

		$indexes = $this->getIndexes();

		$charset = $this->mElement->getCharset();

		switch($charset) {

			case 'binary' :
				$charset = DatabaseManager::CHARSET_BINARY;
				break;

			case 'utf8' :
				$charset = DatabaseManager::CHARSET_UTF8;
				break;

			case 'ascii' :
				$charset = DatabaseManager::CHARSET_ASCII;
				break;

			default :
				$charset = NULL;

		}

		$storage = $this->mElement->getStorage();

		$this->doCreateTable($fields, $indexes, $charset, $storage, $autoIncrements);

	}

	protected function buildField(string $name, $structure): array {

		$null = $this->mElement->isPropertyNull($name) ? DatabaseManager::NULL : DatabaseManager::NOTNULL;

		$default = $this->mElement->getDefaultValue($name);

		if(is_scalar($default)) {
			$default = $this->mElement->encode($name, $default);
		} else {
			$default = NULL;
		}

		return [$name, $structure, $null, $default];

	}

	protected function getIndexes(): array {

		$indexes = [];

		foreach($this->mElement->getUniqueConstraints() as $name => $values) {

			$field = 'unique_'.$name;

			$indexes[$field] = [$field, DatabaseManager::UNIQUE, $this->buildIndex($values)];

		}

		foreach($this->mElement->getSpatialConstraints() as $name => $values) {

			$field = 'spatial_'.$name;

			$indexes[$field] = [$field, DatabaseManager::SPATIAL, $this->buildIndex($values)];

		}

		foreach($this->mElement->getIndexConstraints() as $name => $values) {

			$field = 'index_'.$name;

			$indexes[$field] = [$field, DatabaseManager::INDEX, $this->buildIndex($values)];

		}

		foreach($this->mElement->getSearchConstraints() as $name => $values) {

			$field = 'search_'.$name;

			$indexes[$field] = [$field, DatabaseManager::SEARCH, $this->buildIndex($values)];

		}

		return $indexes;

	}

	protected function buildIndex(array $values): array {

		$index = [];

		foreach($values as $value) {

			if(is_array($value)) {
				$size = $value[1];
				$field = $value[0];
			} else {
				$size = NULL;
				$field = $value;
			}

			$type = $this->mElement->getPropertyType($field);
			$range = $this->mElement->getPropertyRange($field);

			if(
				$range !== NULL and $range[1] !== NULL and
				in_array($type, ['text8', 'editor8', 'binary8', 'email', 'url'])
			) {
				$size = $range[1];
			}

			if($size === NULL) {
				$index[] = $field;
			} else {
				$index[] = [$field, $size];
			}

		}

		return $index;

	}

	protected function doCreateTable(array $fields, array $indexes, string $charset, string $storage, array $autoIncrements = []) {

		$table = $this->mElement->getTable();
		$base = $this->mElement->getDb();

		try {

			if(
				$this->mElement->isSplitted() === FALSE or
				$this->mElement->getSplitProperties()
			) {
				$this->db->manager->createTable($base, $table, $fields, $indexes, $charset, $storage, $autoIncrements[$table] ?? NULL);
			}


			if($this->mElement->isSplitted()) {

				$hasSerial = (
					$this->mElement->getSplitMode() === 'sequence' and
					$this->mElement->hasProperty('id') and
					strpos($this->mElement->getPropertyType('id'), 'serial') === 0
				);

				if($hasSerial) {
					$fields['id'][1] = (array)$fields['id'][1];
				}

				$splitProperties = $this->mElement->getSplitProperties();

				if($splitProperties) {

					array_unshift($splitProperties, 'id');

					$splitFields = array_intersect_key($fields, array_flip($splitProperties));

					$splitIndexes = [];

					foreach($indexes as $key => $index) {

						if(array_diff($index[2], $splitProperties) === []) {
							$splitIndexes[$key] = $index;
						}

					}

				} else {

					$splitIndexes = $indexes;
					$splitFields = $fields;

				}

				foreach($this->mElement->getSuffixes() as $suffix) {

					// Table already created
					if($suffix === '') {
						continue;
					}

					// Determine the starting ID value of the sub-table.
					if($hasSerial) {
						$fields['id'][1][1] = $this->getStartValue($fields['id'][1][0], $suffix, $this->mElement->getSplit());
					}

					$this->db->manager->createTable($base, $table.'_'.$suffix, $splitFields, $splitIndexes, $charset, $storage, $autoIncrements[$table.'_'.$suffix] ?? NULL);

				}

			}

		}
		catch(ConnectionDatabaseException $e) {
			throw new Exception("Database connection error");
		}
		catch(QueryDatabaseException $e) {
			throw new Exception("Internal database exception : ".$e->getMessage());
		}

	}

	/**
	 * Calculate the starting value for the ID field
	 * of the table number $table among $totalTable tables.
	 *
	 * @param string $type Filter field type
	 * @param int $table The number of the table we want to get the starting value for
	 * @param int $totalTable The total amount of tables
	 * @return int The starting value
	 */
	public function getStartValue(string $type, int $table, int $totalTable): int {

		if($totalTable !== 0) {

			switch($type) {
				case 'serial8' :
					$max = Filter::MAX_INT8;
					break;
				case 'serial16' :
					$max = Filter::MAX_INT16;
					break;
				case 'serial24' :
					$max = Filter::MAX_INT24;
					break;
				case 'serial32' :
					$max = Filter::MAX_INT32;
					break;
				case 'serial64' :
					$max = Filter::MAX_INT64;
					break;
				default :
					return 1;
			}

			return $table * (floor($max / $totalTable) + 1);

		}

	}

	/**
	 * Calculate the ending value for the ID field
	 * of the table number $table among $totalTable tables.
	 *
	 * @param string $type Filter field type
	 * @param int $table The number of the table we want to get the ending value for
	 * @param int $totalTable The total amount of tables
	 *
	 * @return int The ending value
	 */
	public function getEndValue(string $type, int $table, int $totalTable): int {

		if($totalTable !== 0) {

			switch($type) {
				case 'serial8' :
					$max = Filter::MAX_INT8;
					break;
				case 'serial16' :
					$max = Filter::MAX_INT16;
					break;
				case 'serial24' :
					$max = Filter::MAX_INT24;
					break;
				case 'serial32' :
					$max = Filter::MAX_INT32;
					break;
				case 'serial64' :
					$max = Filter::MAX_INT64;
					break;
				default :
					return 1;
			}

			return (($table + 1) * (floor($max / $totalTable) + 1)) - 1;

		}

	}

	/**
	 * Remove module's tables
	 *
	 */
	public function finalize() {

		$this->dropTable();

	}

	public function dropTable() {

		$table = $this->mElement->getTable();
		$base = $this->mElement->getDb();

		try {

			foreach($this->mElement->getSuffixes() as $suffix) {

				if($suffix === '') {
					$this->db->manager->dropTable($base, $table);
				} else {
					$this->db->manager->dropTable($base, $table.'_'.$suffix);
				}

			}

		}
		catch(ConnectionDatabaseException $e) {
			throw new Exception("Database connection error");
		}
		catch(QueryDatabaseException $e) {
			throw new Exception("Internal database exception : ".$e->getMessage());
		}

	}

}
?>
