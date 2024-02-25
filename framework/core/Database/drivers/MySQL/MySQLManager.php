<?php

require_once LIME_DIRECTORY.'/framework/core/Database/DatabaseManager.php';

/**
 * MySQL for Database
 */

class MySQLManager extends DatabaseManager {

	protected $type = [
		'binaryFixed' => "BINARY",
		'binary8' => "VARBINARY",
		'binary16' => "BLOB",
		'binary24' => "MEDIUMBLOB",
		'binary32' => "LONGBLOB",
		'textFixed' => "CHAR",
		'text8' => "VARCHAR",
		'text16' => "TEXT",
		'text24' => "MEDIUMTEXT",
		'text32' => "LONGTEXT",
		'json' => "JSON",
		'date' => "DATE",
		'time' => "TIME",
		'datetime' => "DATETIME",
		'decimal' => "DECIMAL",
		'float32' => "FLOAT",
		'float64' => "DOUBLE",
		'int8' => "TINYINT",
		'int16' => "SMALLINT",
		'int24' => "MEDIUMINT",
		'int32' => "INT",
		'int64' => "BIGINT",
		'serial8' => "TINYINT UNSIGNED",
		'serial16' => "SMALLINT UNSIGNED",
		'serial24' => "MEDIUMINT UNSIGNED",
		'serial32' => "INT UNSIGNED",
		'serial64' => "BIGINT UNSIGNED",
		'enum' => "ENUM",
		'bool' => "TINYINT(1)",
		'point' => "POINT",
		'polygon' => "POLYGON"
	];

	public function createTable(string $base, string $table, array $fields, array $indexes, string $charset = self::CHARSET_UTF8, string $storage = NULL, ?int $autoIncrement = NULL): bool {

		$column = [];
		$other = [];
		$types = [];

		// Auto increment start value;
		$autoIncrementDefault = NULL;

		foreach($fields as $value) {

			if(is_array($value) === FALSE) {
				throw new QueryDatabaseException("Wrong parameters for createTable()");
			}

			list($name, $type) = $value;

			if(is_string($type)) {
				$type = (array)$type;
			}

			$plus = "";

			$null = $value[2] ?? DatabaseManager::NULL;
			$default = $value[3] ?? NULL;

			if(is_bool($default)) {
				$default = (int)$default;
			}

			if(
				!in_array($null, [DatabaseManager::NULL, DatabaseManager::NOTNULL], TRUE) or
				(!is_scalar($default) and !is_null($default) and !($default instanceof Set))
			) {
				throw new QueryDatabaseException("Wrong parameters for createTable()");
			}

			$tmp = $this->type[$type[0]];

			if(is_string($tmp)) {
				$typeColumn = $tmp;
			} else {
				$typeColumn = $tmp[$charset];
			}

			$types[$name] = $type;

			switch($type[0]) {

				case 'enum' :
					$elements = [];
					foreach($type[1] as $element) {
						$elements[] = $this->db->quote($element);
					}
					$typeColumn .= "(".implode(', ', $elements).") CHARACTER SET ascii COLLATE ascii_bin";
					break;

				case 'int8' :
				case 'int16' :
				case 'int24' :
				case 'int32' :
				case 'int64' :
				case 'float32' :
				case 'float64' :
					if(isset($type[1]) and $type[1] === DatabaseManager::UNSIGNED) {
						$typeColumn .= " UNSIGNED";
					}
					break;

				case 'decimal' :
					$typeColumn .= '('.$type[1].', '.$type[2].')';
					break;

				case 'serial8' :
				case 'serial16' :
				case 'serial24' :
				case 'serial32' :
				case 'serial64' :

					$null = DatabaseManager::NOTNULL;
					$plus .= " AUTO_INCREMENT";

					if(isset($type[1])) {
						$autoIncrementDefault = (string)$type[1];
					}
					break;

				case 'text8' :
				case 'text16' :
				case 'text24' :
				case 'text32' :

					$charsetColumn = $type[2] ?? DatabaseManager::CHARSET_UTF8;

					if($type[0] === 'text8') {
						$typeColumn .= isset($type[1]) ? "(".$type[1].")" : "(255)";
					}

					$typeColumn .= " CHARACTER SET ".$charsetColumn;

					if(isset($type[3]) and $type[3] !== NULL) {
						$typeColumn .= " COLLATE ".$charsetColumn.'_'.$type[3]."_ci";
					} else {
						$typeColumn .= " COLLATE ".$charsetColumn."_bin";
					}
					break;

				case 'textFixed' :

					$charsetColumn = $type[2] ?? DatabaseManager::CHARSET_UTF8;

					$typeColumn .= isset($type[1]) ? "(".$type[1].")" : "";
					$typeColumn .= " CHARACTER SET ".$charsetColumn;

					if(isset($type[3]) and $type[3] !== NULL) {
						$typeColumn .= " COLLATE ".$charsetColumn.'_'.$type[3]."_ci";
					} else {
						$typeColumn .= " COLLATE ".$charsetColumn."_bin";
					}
					break;

				case 'binary8' :
				case 'binary16' :
				case 'binary24' :
				case 'binary32' :
					if($type[0] === 'binary8') {
						$typeColumn .= isset($type[1]) ? "(".$type[1].")" : "(255)";
					}
					break;

				case 'binaryFixed' :
					$typeColumn .= isset($type[1]) ? "(".$type[1].")" : "";
					break;

				default :
					$typeColumn .= isset($type[1]) ? "(".$type[1].")" : "";

			}

			if($name === 'id') {
				$other[] = ", PRIMARY KEY (".$this->db->api->field($name).")";
			}

			switch($null) {

				case DatabaseManager::NULL :
					$null_column = "NULL";
					break;

				case DatabaseManager::NOTNULL :
					$null_column = "NOT NULL";
					break;

			}


			if(is_null($default)) {
				$default_column = "";
			} else if($default === DatabaseManager::NULL) {
				$default_column = "DEFAULT NULL";
			} else if(is_int($default) or is_float($default)) {
				$default_column = "DEFAULT ".$default."";
			} else if($default instanceof Set) {
				$default_column = "DEFAULT ".$default->get();
			} else {
				$default_column = "DEFAULT ".$this->db->quote($default)."";
			}

			$column[] = $this->db->api->field($name)." ".$typeColumn." ".$null_column." ".$default_column." ".$plus;

		}

		foreach($indexes as $index) {

			$other[] = ', '.$this->createIndexString($base, $table, $index[0], $index[1], $index[2], TRUE);

		}

		if($column) { // A least one column in the table

			$sql = "CREATE TABLE ".$this->db->api->field($base).".".$this->db->api->field($table)." (

			".implode(', ', $column)."

			".implode('', $other)."

			) ENGINE=";

			if($storage === NULL) {
				$sql .= 'myisam';
			} else {
				$sql .= $storage;
			}

			$sql .=  ' CHARACTER SET = ';

			switch($charset) {

				case DatabaseManager::CHARSET_ASCII :
					$sql .= "ASCII";
					break;

				case DatabaseManager::CHARSET_BINARY :
					$sql .= "BINARY";
					break;

				case DatabaseManager::CHARSET_UTF8 :
					$sql .= "utf8mb4 COLLATE utf8mb4_bin";
					break;

			}

			$increment = ($autoIncrement ?? $autoIncrementDefault);
			
			if($increment !== NULL) {
				$sql .= ' AUTO_INCREMENT = '.$increment;
			}

			$sql .= ';';
			$this->db->exec($sql);

			return TRUE;

		} else {
			throw new QueryDatabaseException("Table '".encode($table)."' can not be created: has only indexes");
		}

	}

	public function getTables(string $base, array $engines = NULL): array {

		$sql = 'SHOW TABLE STATUS FROM '.$this->db->api->field($base);
		$statement = $this->db->query($sql);

		$tables = [];

		if($statement) {
			while($result = $statement->fetch()) {
				if($engines === NULL or in_array($result['Engine'], (array)$engines) or $result['Engine'] === NULL) {
					$tables[] = $result['Name'];
				}
			}
		}

		return $tables;

	}

	public function getTable(string $base, string $table) {

		$sql = 'SHOW TABLE STATUS FROM '.$this->db->api->field($base).' LIKE '.$this->db->quote($table);
		$statement = $this->db->query($sql);

		if($statement) {

			$result = $statement->fetch();

			return [
				'name' => $result['Name'],
				'engine' => $result['Engine'],
				'rows' => $result['Rows'],
				'size' => [
					'total' => $result['Index_length'] + $result['Data_length'],
					'data' => $result['Data_length'],
					'index' => $result['Index_length'],
					'free' => $result['Data_free'],
				],
				'creation' => $result['Create_time'],
				'lastUpdate' => $result['Update_time'],
				'lastCheck' => $result['Check_time'],
			];

		}

		return NULL;

	}

	public function hasTable(string $base, string $table): bool {

		$sql = 'SHOW TABLES FROM '.$this->db->api->field($base).' LIKE '.$this->db->quote($table).'';

		$statement = $this->db->query($sql);

		if($statement) {
			return ($statement->rowCount() > 0);
		}

		return FALSE;

	}

	public function renameTable(string $base, string $from, string $to) {
		return $this->db->exec("ALTER TABLE ".$this->db->api->field($base).".".$this->db->api->field($from)." RENAME TO ".$this->db->api->field($base).".".$this->db->api->field($to));
	}

	public function optimizeTable(string $base, string $table) {
		return $this->db->exec("OPTIMIZE TABLE ".$this->db->api->field($base).".".$this->db->api->field($table)."");
	}

	public function flushTable(string $base, string $table) {
		return $this->db->exec("FLUSH TABLE ".$this->db->api->field($base).".".$this->db->api->field($table)."");
	}

	public function repairTable(string $base, string $table) {

		$sql = 'REPAIR TABLE '.$this->db->api->field($base).".".$this->db->api->field($table);
		$statement = $this->db->query($sql);

		if($statement) {

			$error = $this->getErrorFromStatement($statement);

			if($error === NULL) {
				return TRUE;
			}

			return $error;

		}

		return TRUE;

	}

	public function checkTable(string $base, string $table, bool $fast = TRUE) {

		$sql = 'CHECK TABLE '.$this->db->api->field($base).'.'.$this->db->api->field($table);
		if($fast) {
			$sql .= ' FAST QUICK';
		}

		$statement = $this->db->query($sql);

		if($statement) {

			$error = $this->getErrorFromStatement($statement, ['Not checked']);

			if($error === NULL) {
				return TRUE;
			}

			return $error;

		}

		return TRUE;

	}

	protected function getErrorFromStatement($statement, array $messages = []) {

		$messages = array_merge($messages, ['OK', 'Table is already up to date']);
		$error = [];

		while($row = $statement->fetch()) {

			if(in_array($row['Msg_text'], $messages) === FALSE) {
				$error[] = $row['Msg_text'];
			}

		}

		if($error) {
			return implode("\n", $error);
		} else {
			return NULL;
		}

	}

	public function createIndexes(string $base, string $table, array $indexes) {

		foreach($indexes as $index) {

			$this->createIndex($base, $table, $index[0], $index[1], $index[2]);

		}

	}

	public function createIndex(string $base, string $table, string $name, int $type, array $fields, bool $drop = FALSE) {

		if($drop) {
			$dropQuery = 'DROP INDEX '.$this->db->api->field($base).".".$this->db->api->field($name).',';
		} else {
			$dropQuery = '';
		}

		$indexString = $this->createIndexString($base, $table, $name, $type, $fields);

		return $this->db->exec("ALTER TABLE ".$this->db->api->field($base).".".$this->db->api->field($table)." ".$dropQuery." ADD ".$indexString);

	}

	protected function createIndexString(string $base, string $table, string $name, int $type, array $fields, bool $hasKey = FALSE): string {

		$options = $this->resetOptions();

		if($options[self::INDEX_NO_TABLE] === FALSE) {
			$name = $table."_".$name;
		}

		$fields = (array)$fields;

		foreach($fields as $key => $field) {
			if(is_array($field)) {
				list($field, $size) = $field;
			} else {
				$size = NULL;
			}
			$fields[$key] = $this->db->api->field($field);
			if($size !== NULL) {
				$fields[$key] .= '('.$size.')';
			}
		}

		$index = $this->db->api->field($name);

		switch($type) {
			case DatabaseManager::SPATIAL :
				$typeTextual = "SPATIAL INDEX";
				break;
			case DatabaseManager::INDEX :
				$typeTextual = "INDEX";
				break;
			case DatabaseManager::UNIQUE :
				$typeTextual = "UNIQUE";
				break;
			case DatabaseManager::SEARCH :
				$typeTextual = "FULLTEXT";
				break;
		}

		if($hasKey and in_array($type, [DatabaseManager::INDEX, DatabaseManager::SPATIAL]) === FALSE) {
			$typeTextual .= ' KEY';
		}

		return "".$typeTextual." ".$index." (".implode(", ", $fields).")";

	}

	public function dropIndexes(string $base, string $table, array $names) {

		foreach($names as $name) {
			$this->dropIndex($base, $table, $name);
		}

	}

	public function dropIndex(string $base, string $table, string $name) {

		$options = $this->resetOptions();

		if($options[self::INDEX_NO_TABLE] === FALSE) {
			$name = $table."_".$name;
		}

		$index = $this->db->api->field($name);
		$table = $this->db->api->field($base).".".$this->db->api->field($table);

		return $this->db->exec("ALTER TABLE ".$table." DROP INDEX ".$index);

	}

	public function getFields(string $base, string $table): array {

		$table = $this->db->api->field($base).".".$this->db->api->field($table);
		$fields = [];

		$rs = $this->db->select('SHOW FULL COLUMNS FROM '.$table);

		while($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$fields[] = [
				'name' => $row['Field'],
				'type' => $row['Type'],
				'null' => ($row['Null'] === "YES"),
				'collation' => $row['Collation'],
				'default' => $row['Default']
			];
		}

		return $fields;

	}

	public function hasField(string $base, string $table, string $field): bool {

		$sql = 'SHOW FULL COLUMNS FROM '.$this->db->api->field($base).".".$this->db->api->field($table).' LIKE '.$this->db->quote($field).'';

		$statement = $this->db->query($sql);

		if($statement) {
			return ($statement->rowCount() > 0);
		}

		return FALSE;

	}

	public function getIndexes(string $base, string $table): array {

		$table = $this->db->api->field($base).".".$this->db->api->field($table);
		$indexes = [];

		$rs = $this->db->select('SHOW INDEX FROM '.$table);

		while($row = $rs->fetch(PDO::FETCH_ASSOC)) {

			$name = $row['Key_name'];

			if(empty($indexes[$name])) {
				$indexes[$name] = [
					'primary' => ($row['Key_name'] === 'PRIMARY'),
					'unique' => ($row['Non_unique'] !== '1'),
					'cardinality' => $row['Cardinality'],
					'fulltext' => ($row['Index_type'] === 'FULLTEXT'),
					'fields' => [],
				];
			}

			$position = (int)$row['Seq_in_index'] - 1;
			$field = $row['Column_name'];

			$indexes[$name]['fields'][$position] = $field;

		}

		return $indexes;

	}

}
?>
