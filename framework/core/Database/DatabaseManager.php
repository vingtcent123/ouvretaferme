<?php

/**
 * This class contains somes methods to handle tables
 */
abstract class DatabaseManager extends DatabaseObject {

	// Null value
	const NULL =  TRUE;
	const NOTNULL =  FALSE;

	// Signed/Unsigned numbers
	const SIGNED = TRUE;
	const UNSIGNED = FALSE;

	const INDEX = 21;
	const SPATIAL = 28;
	const UNIQUE = 22;
	const SEARCH = 36;

	// Tables charsets
	const CHARSET_BINARY = 'binary';
	const CHARSET_UTF8 = 'utf8mb4';
	const CHARSET_ASCII = 'ascii';

	/**
	 * Create a table
	 *
	 * Example :
	 * <code>
	 * $db->createTable("test", [
	 * 	['id', 'serial32', DatabaseManager::NOTNULL],
	 * 	['field_integer', 'int32', DatabaseManager::NOTNULL, '12'],
	 * 	['field_string', ['text8', 50], DatabaseManager::NOTNULL, 'test'],
	 * 	['field_datetime', 'datetime'],
	 * ], [
	 * 	['index_test', DatabaseManager::INDEX, 'fieldInteger'],
	 * 	['unique_test', DatabaseManager::UNIQUE, ['fieldInteger', 'fieldString']]
	 * ]
	 * </code>
	 *
	 * @param string $base Name of the database
	 * @param string $table The name of the table
	 * @param array $fields One element for each field or index in an array. The syntax of an element is : [ [ (string) Field name ], [ (int) Field type | [ [ (int) Field type ], [ (int) Field length ] ] ], [ Data field case: [ (int) DatabaseManager::NULL | (int) DatabaseManager::NOTNULL ], [ Field default value: (int) DatabaseManager::NULL | (null) NULL | (string) Any string ] | Index case: [ (string) A field name | (array (string)) A list of fields name ] ] ].
	 * @param array $indexes Indexes
	 * @return bool True is the table has been created, false otherwise
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function createTable(string $base, string $table, array $fields, array $indexes, string $charset = self::CHARSET_UTF8, string $storage = NULL): bool;

	/**
	 * Rename a table
	 *
	 * @param string $base Name of the database
	 * @param string $from Old name of the table
	 * @param string $to New name of the table
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function renameTable(string $base, string $from, string $to);

	/**
	 * Optimize a table
	 *
	 * @param string $base Name of the database
	 * @param string $table The table to optimize
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function optimizeTable(string $base, string $table);

	/**
	 * Flush a table
	 *
	 * @param string $base Name of the database
	 * @param string $table The table to flush
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function flushTable(string $base, string $table);

	/**
	 * Repair a table
	 *
	 * @param string $base Name of the database
	 * @param string $table The table to repair
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function repairTable(string $base, string $table);

	/**
	 * Check a table
	 *
	 * @param string $base Name of the database
	 * @param string $table
	 * @param string $fast Enable/disable fast mode
	 */
	abstract public function checkTable(string $base, string $table, bool $fast = TRUE);

	/**
	 * Check if a table exists
	 *
	 * @param string $base Name of the database
	 * @param string $table The table to check
	 * @throws QueryDatabaseManagerException
	 */
	abstract public function hasTable(string $base, string $table): bool;

	/**
	 * Get table list
	 *
	 * @param string $base Name of the database
	 * @return array
	 * @throws QueryDatabaseManagerException
	 */
	abstract public function getTables(string $base, array $engines = NULL);

	/**
	 * Get info on a table
	 *
	 * @param string $base Name of the database
	 * @param string $table
	 * @return array
	 */
	abstract public function getTable(string $base, string $table);

	/**
	 * Remove a table
	 *
	 * @param string $base Name of the database
	 * @param string $table A table name
	 * @throws QueryDatabaseManagerException
	 *
	 */
	public function dropTable(string $base, string $table) {
		$this->db->query("DROP TABLE ".$this->db->api->field($base).'.'.$this->db->api->field($table));
	}

	/**
	 * Add several indexes to a table
	 *
	 * @param string $base Name of the database
	 * @param string $table A table name
	 * @param string $indexes A list of indexes
	 *
	 */
	abstract public function createIndexes(string $base, string $table, array $indexes);

	/**
	 * Add an index to a table
	 *
	 * @param string $base Name of the database
	 * @param string $table A table name
	 * @param string $name A name for this index
	 * @param int $type Index type (DatabaseManager::INDEX, DatabaseManager::UNIQUE)
	 * @param mixed $fields Fields concerned by the index
	 * @param mixed $drop Drop old index
	 *
	 */
	abstract public function createIndex(string $base, string $table, string $name, int $type, array $fields, bool $drop = FALSE);

	/**
	 * Delete several indexes from a table
	 *
	 * @param string $base Name of the database
	 * @param string $table A table name
	 * @param string $names An index name
	 *
	 */
	abstract public function dropIndexes(string $base, string $table, array $names);

	/**
	 * Delete an index from a table
	 *
	 * @param string $base Name of the database
	 * @param string $table A table name
	 * @param string $name An index name
	 *
	 */
	abstract public function dropIndex(string $base, string $table, string $name);

	/**
	 * Get the fields of a table
	 *
	 * @param string $base Name of the database
	 * @param string $table The table
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function getFields(string $base, string $table): array;

	/**
	 * Check if a table has a field
	 *
	 * @param string $base Name of the database
	 * @param string $table The table
	 * @param string $field The field name
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function hasField(string $base, string $table, string $field): bool;

	/**
	 * Get the indexes of a table
	 *
	 * @param string $base Name of the database
	 * @param string $table The table
	 * @throws QueryDatabaseManagerException
	 *
	 */
	abstract public function getIndexes(string $base, string $table);

	// Don't add table name where creating/dropping indexes
	const INDEX_NO_TABLE = FALSE;

	protected $options = [];

	public function option(string $name, string $value) {

		$this->options[$name] = $value;
		return $this;

	}

	public function resetOptions(): array {

		$options = $this->options + [
			self::INDEX_NO_TABLE => FALSE,
		];

		$this->options = [];

		return $options;

	}

}
?>
