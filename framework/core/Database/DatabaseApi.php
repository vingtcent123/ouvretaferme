<?php

/**
 * This class contains somes methods to handle database
 */
abstract class DatabaseApi extends DatabaseObject {

	/**
	 * Cast data to use in a SQL query
	 * This method has effect only for PostgreSQL
	 * This method doesn't reproduce the MySQL's CAST() behaviour
	 *
	 * <code>
	 * $db = new MySQL( ... );
	 * $db->query("SELECT ".$this->db->cast('count(*)', Database::SMALLINT)." AS data FROM table"); // count(*) is casted as a SMALLINT
	 * </code>
	 *
	 * @return string A string to add in a SQL query
	 */
	public function cast(string $field, $type): string {
		return $field;
	}

	/**
	 * Concat data and format it to use in a SQL query
	 * Each value which doesn't correspond to a field name must be delimited by quotes
	 * You can pass an unlimited number of arguments to this function (an argument for each element to concat.
	 *
	 * <code>
	 * $db = new SQLite( ... );
	 * $db->query("SELECT ".$this->db->concat("year", "'-'", "month", "'-'", "day")." AS data FROM table"); // year, month and day are table fields
	 * </code>
	 *
	 * @return string A string to add in a SQL query
	 */
	abstract public function concat(...$list);

	/**
	 * Function for random numbers
	 *
	 * @return string The SQL random function
	 */
	abstract public function random();

	/**
	 * Escape a database field
	 *
	 * @param string $field The field
	 * @return string Escaped field
	 */
	abstract public function field(string $field);

	/**
	 * Quote a string before insertion in database
	 * Redefining PDO::$quote() avoid to open a connection to the DBMS et we don't perform any query
	 *
	 * @param string $string The string
	 * @return string Escaped string
	 */
	abstract public function quote(string $string);

	/**
	 * Returns LIMIT parameter to use with a SQL query
	 *
	 * <code>
	 * $db = new SQLite( ... );
	 * $db->query("SELECT * FROM table WHERE field='1' ".$db->limit(5, 10));
	 * </code>
	 *
	 * @param string $start Position of the first expected result
	 * @param string $length Number of lines expected
	 * @return string A string to add at the end of a SQL query
	 */
	abstract public function limit(int $start, int $length);

	/**
	 * Returns the number of rows for the last use of SQL_COUNT_RESULTS
	 *
	 * @return int The number of rows
	 */
	abstract public function found();

	/**
	 * Return a string representing the current time for the database.
	 * The $mode parameter indicates the format you want the result in.
	 * Default value is 'datetime' and is expected to return a string
	 * like 'YYYY-MM-DD HH:MM:SS', common to many RDBMS.
	 * Possible values are 'datetime', 'date', 'time' and 'timestamp' (as in *UNIX* timestamp).
	 * An unknown or unsupported $mode should send NULL back.
	 *
	 * @param string $mode Date format
	 */
	abstract public function now(string $mode = 'datetime', string $difference = NULL);

	/**
	 * Checks if a connection has a replication
	 * A replication has the same name as the original connection with '@replication' suffix
	 *
	 * @return bool
	 */
	public function hasReplication(string $replicationName = 'replication'): bool {
		$connection = $this->db->getParam('connection').'@'.$replicationName;
		return $this->db->hasConnection($connection);
	}

	/**
	 * Get a database instance for a replication
	 *
	 * @return Database
	 */
	public function getReplication(string $replicationName = 'replication'): Database {
		$connection = $this->db->getParam('connection').'@'.$replicationName;
		return new Database($connection);
	}

	/**
	 * Get replication delay
	 * This method must be called by a instance of the original connection
	 * If no replication has been found, an exception is thrown
	 *
	 * @param string $replicationName Name of the replication (ie: "replication")
	 * @param int $cacheTimeout Cache timeout to store replication delay (NULL = no cache)
	 * @return int
	 */
	abstract public function getReplicationDelay(string $replicationName, int $cacheTimeout = NULL);

}
?>
