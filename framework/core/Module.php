<?php
/**
 * Generic module for modules using database
 */
abstract class ModuleModel {

	/**
	 * Array storing selected fields for the next action
	 *
	 * @var array
	 */
	public array $selection = [];

	/**
	 * Delegate query
	 *
	 */
	public ?array $delegate = NULL;

	/**
	 * Properties
	 *
	 * @var array
	 */
	protected array $properties = [];

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected string $module;

	/**
	 * Properties that are elements
	 *
	 * @var array
	 */
	protected array $propertiesToModule = [];

	/**
	 * Physical properties
	 *
	 * @var array
	 */
	protected array $propertiesList = [];

	/**
	 * Temporary properties
	 *
	 * @var array
	 */
	public array $propertiesTemporary = [];

	/**
	 * Callable properties
	 *
	 * @var array
	 */
	public array $propertiesCallable = [];

	/**
	 * Split mode
	 *
	 * @var string NULL, 'list' or 'sequence'
	 */
	protected ?string $splitMode = NULL;

	/**
	 * Table split
	 *
	 * @var int
	 */
	protected ?int $split = NULL;

	/**
	 * Property to split
	 *
	 * @var string
	 */
	protected ?string $splitOn = NULL;

	/**
	 * Store :elements to add
	 *
	 * @var array
	 */
	protected array $splitAdd = [];

	/**
	 * Properties for splitted tables
	 *
	 * @var string
	 */
	protected ?array $splitProperties = NULL;

	/**
	 * Unique constraints
	 *
	 * @var array
	 */
	protected array $uniqueConstraints = [];

	/**
	 * Spatial constraints
	 *
	 * @var array
	 */
	protected array $spatialConstraints = [];

	/**
	 * Index constraints
	 *
	 * @var array
	 */
	protected array $indexConstraints = [];

	/**
	 * Search constraints
	 *
	 * @var array
	 */
	protected array $searchConstraints = [];

	/**
	 * Cache configuration
	 *
	 * @var string
	 */
	protected $cache = 'storage';

	/**
	 * Module charset
	 *
	 * @var string
	 */
	protected string $charset = 'utf8';

	/**
	 * Module storage
	 *
	 * @var string
	 */
	protected string $storage = 'innodb';

	/**
	 * A condition for a query
	 *
	 * @var string
	 */
	protected ?string $condition = NULL;

	/**
	 * To group results
	 *
	 * @var array
	 */
	protected $group = NULL;

	/**
	 * having condition
	 *
	 * @var string
	 */
	protected $having = NULL;

	/**
	 * Sort condition
	 *
	 * @var string
	 */
	protected $sort = [];

	/**
	 * Limit for write operations
	 *
	 * @var int
	 */
	protected $limit = NULL;

	/**
	 * Union all subtables
	 *
	 * @var bool
	 */
	protected bool $union = FALSE;

	/**
	 * Return results as recordset
	 *
	 * @var string (NULL, TRUE:buffered, FALSE: not buffered)
	 */
	protected ?bool $recordset = NULL;

	/**
	 * Options for a query
	 *
	 * @var array
	 */
	protected array $options = [];


	/**
	 * Closure for table prefix
	 */
	private static ?Closure $prefix = NULL;


	/**
	 * Store database connections
	 */
	private static array $db = [];


	/**
	 * Stack for DB transactions
	 */
	private static array $dbTransactionStack = [];

	/**
	 * Suffix for database tables
	 *
	 * @var string
	 */
	protected string $suffix = '';

	/**
	 * Persistent suffix
	 *
	 * @var string
	 */
	protected static $persistentSuffix = [];

	/**
	 * Previous suffix
	 *
	 * @var string
	 */
	protected string $previousSuffix = '';

	/**
	 * Module package
	 */
	protected string $package;

	/**
	 * Current database connection name
	 */
	protected ?string $connection = NULL;

	/**
	 * Database name
	 */
	protected ?string $base = NULL;

	/**
	 * Database keyName (host:port)
	 */
	protected $server;

	/**
	 * Element table
	 *
	 * @var string
	 */
	protected string $table;

	/**
	 * Cache
	 *
	 * @var string
	 */
	protected ?string $cacheOption;

	/**
	 * Joined modules
	 *
	 * @var array
	 */
	protected array $join = [];

	/**
	 * Found results
	 *
	 * @var int
	 */
	protected ?int $found = 0;

	/**
	 * Found results
	 *
	 * @var int
	 */
	protected ?array $foundInfo = NULL;

	/**
	 * Previous connection
	 *
	 * @var string
	 */
	protected ?string $atBefore = NULL;

	/**
	 * Registered delegations for the next query
	 *
	 * @var array
	 */
	protected array $selectionDelegation = [];

	/**
	 * Union splits
	 *
	 * @var array
	 */
	private array $splitUnions = [];

	public function __construct() {

		$this->suffix($this->getPersistentSuffix());
      $this->base = Database::getBase($this->package);

	}

	/**
	 * Get table prefix
	 *
	 * @return Closure
	 */
	public static function setPrefix(?Closure $callback): void {
		self::$prefix = $callback;
	}

	/**
	 * Get module package
	 */
	public function getPackage(): string {
		return $this->package;
	}

	/**
	 * Get module name
	 *
	 */
	public function getModule(): string {
		return $this->module;
	}

	/**
	 * Get an empty element for this module
	 *
	 * @return array Element
	 */
	public function getNewElement(): Element {
		return new $this->module;
	}

	/**
	 * Save a temporary element property
	 */
	public function saveTemporary(string $name, $mask): void {

		if(is_array($mask)) {
			$mask['cast'] = self::getCast($name, $mask[0]);
		} else if(is_closure($mask) === FALSE) {
			throw new Exception('Invalid mask');
		}

		$this->propertiesTemporary[$name] = $mask;

	}

	/**
	 * Save a callable element property
	 */
	public function saveCallable(string $name, callable $value): void {

		$this->propertiesCallable[$name] = $value;

	}

	public static function getCast(string $name, string $type): string {

		if($type === 'bool') {
			return 'bool';
		} else if(str_starts_with($type, 'element')) {
			return 'element';
		} else if(str_contains($type, '\\')) {
			return $type;
		} else if(str_starts_with($type, 'int') or str_starts_with($type, 'serial')) {
			return 'int';
		} else if(str_starts_with($type, 'float') or $type === 'decimal') {
			return 'float';
		} else if($type == 'set') {
			return 'set';
		} else if($type == 'enum') {
			return 'enum';
		} else if($type === 'point' or $type === 'polygon') {
			return 'json';
		} else if(str_starts_with($type, 'json')) {
			return 'array';
		} else if($type === 'collection') {
			return 'collection';
		} else if(str_starts_with($type, 'binary')) {
			return 'binary';
		} else {
			return 'string';
		}

	}

	public function getDefaultValue(string $property) {
	}

	/**
	 * Get element properties
	 *
	 * @return array
	 */
	public function getProperties(): array {
		return $this->propertiesList;
	}

	/**
	 * Get element properties that must be converted into elements
	 */
	public function getPropertiesToElement(): array {
		return $this->propertiesToModule;
	}

	/**
	 * Check if a property exists
	 */
	public function hasProperty(string $name): bool {
		return is_string($name) and isset($this->properties[$name]);
	}

	/**
	 * Check if a property is unique
	 */
	public function isPropertyUnique(string $property): bool {
		return $this->properties[$property]['unique'] ?? FALSE;
	}

	/**
	 * Check if a property can be null
	 */
	public function isPropertyNull(string $property): bool {
		return $this->properties[$property]['null'] ?? FALSE;
	}

	/**
	 * Get infos about a property
	 *
	 * @return array
	 */
	public function getProperty(string $property): array {
		return $this->properties[$property];
	}

	/**
	 * Get property describer
	 *
	 * @return string Module name
	 */
	public function describer($property, array $labels = []): PropertyDescriber {

		$values = [
			'label' => $labels[$property] ?? NULL
		];

		if($this->hasProperty($property)) {

			$type = $this->getPropertyType($property);

			$values += [
				'type' => $type,
				'range' => $this->getPropertyRange($property),
				'enum' => ($type === 'enum') ? $this->getPropertyEnum($property) : NULL,
				'set' => ($type === 'set') ? $this->getPropertySet($property) : NULL,
				'default' => $this->getDefaultValue($property),
				'module' => $this->getPropertyToModule($property),
			];

		}

		return new PropertyDescriber($property, $values);

	}

	/**
	 * Get type of an element property
	 *
	 * @return array
	 */
	public function getPropertyType(string $property): string {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return $this->properties[$property][0];

	}

	/**
	 * Get the range of an element property
	 *
	 * @return array
	 */
	public function getPropertyRange(string $property) {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return Filter::getRange($this->properties[$property]);
	}

	/**
	 * Get the enumeration of an element property
	 *
	 * @return array
	 */
	public function getPropertyEnum(string $property): array {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return $this->properties[$property][1];

	}

	/**
	 * Get the set of an element property
	 *
	 * @return array
	 */
	public function getPropertySet(string $property): array {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return $this->properties[$property][1];

	}

	/**
	 * Get the specified charset of an element property
	 *
	 * @return string
	 */
	public function getPropertyCharset(string $property) {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return $this->properties[$property]['charset'] ?? NULL;
	}

	/**
	 * Get the specified collate of an element property
	 *
	 * @return string
	 */
	public function getPropertyCollate(string $property) {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return $this->properties[$property]['collate'] ?? NULL;
	}

	/**
	 * Get overflow value
	 *
	 * @return string
	 */
	public function getPropertyOverflow(string $property) {

		if($this->hasProperty($property) === FALSE) {
			throw new Exception('Property '.$this->module.'::'.$property.' does not exist');
		}

		return Filter::getOverflow($this->properties[$property][0]);
	}

	/**
	 * Get an element name from a property
	 *
	 * @return array
	 */
	public function getPropertyToModule(string $property) {
		return $this->propertiesToModule[$property] ?? NULL;
	}

	/**
	 * Cast the property of an element
	 */
	public function cast(string $property, &$value, bool $preserveEmptyStrings = FALSE) {

		$isProperty = $this->hasProperty($property);

		if(isset($this->propertiesTemporary[$property])) {

			if(is_closure($this->propertiesTemporary[$property])) {
				$value = $this->propertiesTemporary[$property]($value);
				return;
			} else {
				$cast = $this->propertiesTemporary[$property]['cast'];
			}

		} else if($isProperty) {
			$cast = $this->properties[$property]['cast'];
		} else {
			return;
		}

		if($cast === NULL) {
			return;
		}

		switch($cast) {

			case 'int' :
			case 'string' :
			case 'bool' :
			case 'array' :
			case 'float' :

				if($value === NULL) {

					if($isProperty === FALSE or $this->isPropertyNull($property)) {
						return;
					}

				} else {

					if($cast === 'string' and is_string($value)) {
						$value = trim($value);
					}

				}

				if(
					$preserveEmptyStrings === FALSE and
					$isProperty and
					(
						$this->isPropertyNull($property) or
						in_array($cast, ['int', 'float']) // Un champ laissé vide pour un nombre est toujours considéré comme NULL et non égal à zéro
					) and
					$value === ''
				) {
					$value = NULL;
				} else {
					setType($value, $cast);
				}

				break;

			case 'binary' :

				if($value === NULL) {

					if($isProperty === FALSE or $this->isPropertyNull($property)) {
						return;
					}

				}

				$value = (binary)$value;

				break;

			case 'element' :

				if(is_array($value)) {
					$id = $value['id'] ?? NULL;
				} else {
					$id = $value;
					$value = [];
				}


				$element = $this->propertiesToModule[$property];

				if($element !== NULL) {

					if($id === NULL or $id === '') {
						$value = new $element();
					} else {

						if(is_numeric($id)) {
							$value = new $element(['id' => (int)$id]);
						} else {
							$value = new $element();
						}

					}

				} else {
					throw new Exception('Property '.$this->module.'::$'.$property.' can not be casted as an element');
				}

				break;

			case 'collection':

				if(is_array($value)) {
					$value = Collection::fromArray($value, 'element');
				} else if($value instanceof Collection === FALSE) {
					$value = NULL;
				}

				break;

			case 'json':

				if(is_string($value)) {
					try {
						$value = json_decode($value, TRUE, 512, JSON_THROW_ON_ERROR);
					} catch(Exception $e) {
						$value = NULL;
					}
				} else {
					$value = NULL;
				}

				break;

			case 'set' :

				if(empty($value)) {
					$value = NULL;
					return;
				}

				$value = new Set($value);
				break;

			case 'datetime' :
				$time = strtotime($value);
				$value = date('Y-m-d, H:i:s', $time);
				break;

			case 'time' :
				$time = strtotime($value);
				$value = date('H:i:s', $time);
				break;

			case 'enum' :

				if($value === NULL) {
					return;
				}

				$value = ctype_digit($value) ? (int)$value : (string)$value;
				break;

			default :

				if(strpos($cast, '\\') !== FALSE) {

					$value = (string)$value;

					if($value === '') {
						return new $cast;
					}

					$cast::model()->cast('id', $value);

					return new $cast([
						'id' => $value
					]);

				} else {
					throw new Exception('Can not cast in \''.$cast.'\'');
				}

		}

	}

	/**
	 * Check the property of an element
	 */
	public function check(string $property, $value): bool {
		return Filter::check($this->properties[$property], $value);
	}

	/**
	 * Set the property of an element
	 */
	public function set(Element $e, string $property, $value, bool $safe = TRUE): bool {

		if(
			$safe === FALSE or
			$this->check($property, $value)
		) {

			$this->cast($property, $value, preserveEmptyStrings: TRUE);
			$e[$property] = $value;

			return TRUE;

		} else {
			return FALSE;
		}

	}

	/**
	 * Set multiple property
	 *
	 */
	public function setAll(Element $e, $values, \ModuleModel $m) {

		if(is_array($values)) {

			foreach($values as $property => $value) {

				$value = $m->decode($property, $value);
				$this->cast($property, $value, preserveEmptyStrings: TRUE);

				if(is_array($value) and isset($e[$property]) and is_array($e[$property])) {
					$e[$property] = array_replace_recursive($e[$property], $value);
				} else {
					$e[$property] = $value;
				}
			}

		}

	}

	/**
	 * Apply callable properties to an element
	 *
	 * @param array $e
	 */
	public function setCallable(Element $e) {

		foreach($this->propertiesCallable as $name => $value) {
			$e[$name] = $value($e);
		}

	}

	/**
	 * Apply callable properties to a collection
	 *
	 * @param array $e
	 */
	public function setCallableCollection(&$c) {

		if($this->propertiesCallable) {

			foreach($c as $key => $e) {
				$this->setCallable($c[$key]);
			}

		}

	}

	/**
	 * Get unique constraints
	 *
	 * @return array
	 */
	public function getUniqueConstraints(): array {
		return $this->uniqueConstraints;
	}

	/**
	 * Get spatial constraints
	 *
	 * @return array
	 */
	public function getSpatialConstraints(): array {
		return $this->spatialConstraints;
	}

	/**
	 * Get index constraints
	 *
	 * @return array
	 */
	public function getIndexConstraints(): array {
		return $this->indexConstraints;
	}

	/**
	 * Get search constraints
	 *
	 * @return array
	 */
	public function getSearchConstraints(): array {
		return $this->searchConstraints;
	}

	/**
	 * Get current database connection name
	 *
	 * @return string
	 */
	public function getConnection(): string {
		if($this->connection === NULL) {
			return $this->getPackage();
		} else {
			return $this->connection;
		}
	}

	/**
	 * Set current database connection name
	 *
	 * @param string $connection
	 */
	public function setConnection(string $connection) {
		$this->connection = $connection;
	}

	/**
	 * Get cache settings
	 *
	 * @return int or NULL
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Get split
	 *
	 * @return string
	 */
	public function getSplitMode() {
		return $this->splitMode;
	}

	/**
	 * Get table split
	 *
	 * @return int
	 */
	public function getSplit() {
		return $this->split;
	}

	/**
	 * Get property to split
	 *
	 * @return int
	 */
	public function getSplitOn() {
		return $this->splitOn;
	}

	/**
	 * Get properties of splitted tables
	 *
	 * @return array
	 */
	public function getSplitProperties() {
		return $this->splitProperties;
	}

	/**
	 * Check if an element is splitted
	 *
	 * @return bool
	 */
	public function isSplitted(): bool {
		return ($this->splitMode !== NULL);
	}

	/**
	 * Get element charset
	 *
	 * @return string
	 */
	public function getCharset(): string {
		return $this->charset;
	}

	/**
	 * Get element storage
	 *
	 * @return string
	 */
	public function getStorage() {
		return $this->storage;
	}

	/**
	 * Get table split
	 *
	 * @return int
	 */
	public function split($value) {

		if($this->split !== NULL) {

			if(
				$this->getPropertyToModule($this->splitOn) !== NULL or
				in_array($this->getPropertyType($this->splitOn), ['element8', 'element16', 'element24', 'element32', 'element64'])
			) {
				if(isset($value['id'])) {
					$id = $value['id']; // DO NOT CAST AS INT! this will break the modulo when dealing with 64-bit identifiers on 32-bit hardware.
				} else {
					throw new Exception("Unable to split ".$this->module." on NULL identifier");
				}

				if($id < 0) {
					return 0;
				} else {
					return $id % $this->split;
				}

			}

		}

	}

	/**
	 * Get table split by Id
	 *
	 * @param int $id
	 * @return int
	 */
	public function splitById($id) {

		if($this->split !== NULL) {

			$max = $this->getPropertyOverflow('id');

			if(is_array($id)) {

				if(isset($id['id'])) {
					$id = $id['id'];
				} else {
					throw new Exception("Unable to split a table on NULL identifier");
				}

			}

			if($id < 0) {
				return 0;
			} else if($id > $max) {
				return $this->split - 1;
			} else {
				$id = cast($id, 'int');
				return (int)floor($id / (floor($max / $this->split) + 1));
			}

		}

	}

	/**
	 * Remove the current condition
	 */
	public function resetWhere() {
		$condition = $this->condition;
		$this->condition = NULL;
		return ($condition === NULL) ? NULL : substr($condition, 0, -5);
	}

	/**
	 * Change the condition for the next query.
	 *
	 * 1/ SQL String
	 * 2/ $property = $value
	 * 3/ $property, $operator, $value
	 */
	public function where(...$data): ModuleModel {

		$condition = $this->getWhereCondition(...$data);

		if($condition !== NULL) {
			$this->setCondition('condition', $condition);
		}

		return $this;

	}

	protected function getWhereCondition(...$data): ?string {

		if(array_key_exists('if', $data)) {

			$if = $data['if'];
			unset($data['if']);

			if(!$if) {
				return array_key_exists('else', $data) ? '('.$data['else'].')' : NULL;
			}

			if(array_key_exists('else', $data)) {
				unset($data['else']);
			}

		}

		switch(count($data)) {

			case 1 :
				[$value] = $data;
				if($value === NULL) {
					return NULL;
				} else {
					return '('.(is_closure($value) ? $value() : $value).')';
				}

			case 2 :
				[$property, $value] = $data;
				return $this->whereBuild($property, '=', is_closure($value) ? $value() : $value);

			case 3 :
				[$property, $operator, $value] = $data;
				return $this->whereBuild($property, $operator, is_closure($value) ? $value() : $value);

		}

	}

	public function or(...$wheres): ModuleModel {

		$this->condition ??= '';
		$this->condition .= '(';

			foreach($wheres as $where) {

				$this->condition .= '(';

				$where->call($this);

				if(str_ends_with($this->condition, ' AND ')) {
					$this->condition = substr($this->condition, 0, -5);
				}

				$this->condition .= ')';

				$this->condition .= ' OR ';

			}

			if(str_ends_with($this->condition, ' OR ')) {
				$this->condition = substr($this->condition, 0, -4);
			} else {
				$this->condition .= '1';
			}

		$this->condition .= ') AND ';

		return $this;

	}

	protected function whereBuild(string|Sql $property, string $operator, $value): ?string {

		if($property instanceof Sql) {
			$field = $property->__toString();
		} else if($this->join and strpos($property, '.') !== FALSE) {
			list($table, $property) = explode('.', $property, 2);
			$field = $this->field($table).'.'.$this->field($property);
		} else {
			$field = $this->field($property);
		}

		$operator = strtoupper($operator);
		$not = '';

		switch($operator) {

			case 'IN' :
			case 'NOT IN' :

				if($value instanceof Collection) {
					$value = $value->getIds();
				} else if(is_array($value) === FALSE) {
					$value = (array)$value;
				}

				$valuePrepared = $this->getModuleWhere($property)->prepareSeveral($property, $value);

				if($valuePrepared) {
					$valueSql = '('.implode(', ', $valuePrepared).')';
				} else {
					return '(0)';
				}

				break;

			case '=' :
			case '>' :
			case '<' :
			case '>=' :
			case '<=' :
			case '!=' :
			case 'BETWEEN' :
			case 'LIKE' :
			case 'NOT LIKE' :

				$valueSql = (string)$this->getModuleWhere($property)->prepare($property, $value);

				if($valueSql === 'NULL') {

					switch($operator) {

						case '=' :
							$operator = 'IS';
							break;

						case '!=' :
							$operator = 'IS NOT';
							break;

					}

				}

				if($operator === '=') {

					if($this->getSplitOn() === $property) {
						$this->with($value);
					} else if($this->getSplitOn() !== NULL and $property === 'id') {
						$this->withId($value);
					}
				} else if($operator === 'LIKE' and $this->hasProperty($property) and $this->getPropertyType($property) === 'set') {
					$operator = '&';
				} else if($operator === 'NOT LIKE' and $this->hasProperty($property) and $this->getPropertyType($property) === 'set') {
					$operator = '&';
					$not = '!';
				}

				break;

			default :
				throw new Exception('Invalid operator \''.$operator.'\'');

		}

		return $not.'('.$field.' '.$operator.' '.$valueSql.')';

	}

	protected function getModuleWhere($property) {

		if(
			$this->join and
			$property instanceof Sql === FALSE and
			strpos($property, '.') !== FALSE and
			$property[0] === 'm'
		) {

			list($joinModule, $joinProperty) = explode('.', $property, 2);

			if($joinModule === 'm1') {
				return $this;
			} else {
				return $this->join[(int)substr($joinModule, 1) - 1][0];
			}

		} else {
			return $this;
		}

	}

	/**
	 * Select all elements for the next query
	 */
	public function all(): ModuleModel {
		$this->condition = '1';
		return $this;
	}

	/**
	 * Build the condition for the next where or having query
	 *
	 * @param string $conditionAttribute: 'condition' for where() OR 'having' for having()
	 * @param string $condition
	 */
	private function setCondition($conditionAttribute, string $condition): ModuleModel {

		if($condition === NULL) {

			return $this;

		} else {

			$this->{$conditionAttribute} ??= '';
			$this->{$conditionAttribute} .= '('.$condition.') AND ';

			return $this;
		}

	}

	/**
	 * Change the group for the next query
	 *
	 */
	public function group($group): ModuleModel {
		$this->group = $group;
		return $this;
	}

	/**
	 * Remove the current group for queries
	 */
	public function resetGroup() {
		$group = $this->group;
		$this->group = NULL;
		return $group;
	}

	/**
	 * Change the having for the next query
	 *
	 */
	public function having($having): ModuleModel {
		if($having !== NULL) {
			return $this->setCondition('having', '('.$having.')');
		} else {
			return $this;
		}
	}

	/**
	 * Remove the current having for queries
	 */
	public function resetHaving() {
		$having = $this->having;
		$this->having = NULL;
		return ($having === NULL) ? NULL : substr($having, 0, -5);
	}

	/**
	 * Change sorting condition
	 *
	 */
	public function sort(Sql|string|array|null $sort): ModuleModel {
		$this->sort = $sort;
		return $this;
	}

	/**
	 * Remove the current sort for queries
	 *
	 * @return array Current sort
	 */
	public function resetSort(): Sql|string|array|null {
		$sort = $this->sort;
		$this->sort = NULL;
		return $sort;
	}

	/**
	 * Change limit for write operations
	 *
	 */
	public function limit($limit): ModuleModel {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Remove the current limit for queries
	 *
	 * @return array Current limit
	 */
	public function resetLimit() {
		$limit = $this->limit;
		$this->limit = NULL;
		return $limit;
	}

	/**
	 * Enable union for the next query
	 */
	public function union(): ModuleModel {
		$this->union = TRUE;
		return $this;
	}

	/**
	 * Enable recordset for the next query
	 */
	public function recordset(bool $buffered = TRUE): ModuleModel {
		$this->recordset = $buffered;
		return $this;
	}

	/**
	 * Remove the options
	 *
	 * @return array
	 */
	public function resetOptions() {
		$options = $this->options;
		$this->options = [];
		return $options;
	}

	/**
	 * Add an option for the next query.
	 *		count -> Option to count results when queries have a limit
	 *		index-force -> Option to force use of indexes
	 *		index-ignore -> Option to ignore indexes
	 *		update-ignore -> Option for UPDATE IGNORE
	 *		add-ignore -> Option for INSERT IGNORE
	 *		add-replace -> Option for REPLACE instead of INSERT
	 *		delete-join ->
	 *			Choose nums of module in which the selected data must be deleted
	 *			Each num must be separated with a comma
	 *			You can use the flag '*' to delete data in all modules
	 *		highlight -> Option for highlighting queries
	 *
	 *
	 * @param string $options
	 * @return $this
	 */
	public function option(...$options): ModuleModel {

		$name = array_shift($options);
		$this->options[$name] = $options;

		return $this;

	}

	/**
	 * Display next SQL query
	 *
	 * @return Module
	 */
	public function highlight(): ModuleModel {
		return $this->option('highlight');
	}

	/**
	 * Add several options for the next query.
	 *
	 * @param string $options The options
	 * @return $this
	 */
	public function options($options): ModuleModel {
		if(is_array($options)) {
			$this->options = $options + $this->options;
		}
		return $this;
	}

	public function isSelected(mixed $field): bool {
		return (
			is_string($field) and (
				array_key_exists($field, $this->selection) or
				in_array($field, $this->selection, TRUE)
			)
		);
	}

	/**
	 * Add some fields to the selected fields
	 *
	 * @param array $fields List of fields (ie: ['plip','plop','plap' => ['plup']])
	 */
	public function select(...$fields): ModuleModel {

		if(array_key_exists('if', $fields)) {

			$if = $fields['if'];
			unset($fields['if']);

			if(!$if) {
				return $this;
			}

		}

		if(count($fields) === 1) {
			$fields = (array)$fields[0];
		} else {
			$fields = (array)$fields;
		}

		foreach($fields as $key => $value) {

			if(is_string($key)) {
				if(is_array($value)) {
					if(isset($this->selection[$key]) === FALSE) {
						$this->selection[$key] = [];
					}
					$this->selectRecursive($value, $this->selection[$key]);
				} else {
					$this->selection[$key] = $value;
				}
			} else {
				if(in_array($value, $this->selection) === FALSE) {
					$this->selection[] = $value;
				}
			}

		}

		return $this;

	}

	protected function selectRecursive(array $fields, array &$selection) {

		foreach($fields as $key => $value){
			// get array key to test if key already exists
			if(is_string($key)) {
				if(is_array($value)) {
					// need to go deeply
					if(isset($selection[$key]) === FALSE) {
						$selection[$key] = [];
					}
					$this->selectRecursive($value, $selection[$key]);
				} else {
					$selection[$key] = $value;
				}
			} else {
				// add fields in selection
				$selection[] = $value;
			}
		}

	}

	/**
	 * Remove the current selection
	 *
	 * @return array Current selection
	 */
	public function resetSelection(): array {
		$selection = $this->selection;
		$this->selection = [];
		return $selection;
	}

	/**
	 * Reset query
	 *
	 */
	public function reset() {

		$this->selection = [];
		$this->condition = NULL;
		$this->sort = [];
		$this->group = NULL;
		$this->having = NULL;
		$this->limit = NULL;
		$this->options = [];
		$this->join = [];
		$this->union = FALSE;
		$this->recordset = NULL;
		$this->suffix = '';

	}

	/**
	 * Encode a property value to set in the storage engine
	 *
	 * @param string $property Property name
	 * @param string $value Property $value
	 */
	public function encode(string $property, $value) {
		return $value;
	}

	/**
	 * Used in float encoding to round a value.
	 * This gives us a precision of about 1e-9 without rounding errors.
	 */
	protected function recast($value, $factor): int {
		$approx = ($value * $factor * 100) / 100;
		$high = ceil($approx);

		if(abs($approx - $high) < 0.2) {
			return $high;
		}
		return (int)$approx;
	}

	/**
	 * Decode a property value got from the storage engine
	 *
	 * @param string $property Property name
	 * @param string $value Property $value
	 */
	public function decode(string $property, $value) {
		return $value;
	}

	/**
	 * Create the connection to the database
	 */
	public function db(string $connection = NULL) {

		if($connection === NULL) {
			$connection = $this->getPackage();
		}

		// Database Objet has been instanciated at least one time.
		$params = Database::SERVER($connection);
 		$server = $params['host'].':'.$params['port'];

 		// $db isn't empty but doesn't contain the searched connection
		if(isset(self::$db[$server]) === FALSE) {
			self::$db[$server] = self::dbInstance($connection);
			self::$dbTransactionStack[$server] = 0;
		}

		$this->setConnection($connection);
		$this->server = $server;

	}

	/**
	 * Clean all connections to the database
	 *
	 */
	public static function dbClean() {

		foreach(self::$db as $db) {
			unset($db);
		}

		self::$db = [];

	}

	protected static function dbInstance(string $connection, array $options = []): Database {

		try {
			return new Database($connection, $options);
		}
		catch(ConnectionDatabaseException $e) {

			if($e->getCode() === ConnectionDatabaseException::ERROR_DENIED) {
				return new Database($connection, $options);
			} else {
				throw $e;
			}

		}

	}

	/**
	 * Check if a connection exists
	 */
	public function canAt(string $name): bool {

		if(strpos($name, '@') !== FALSE) {
			return Database::hasConnection($name);
		} else {
			return Database::hasConnection($this->getPackage().'@'.$name);
		}

	}

	protected $atNotFound = [];

	/**
	 * Change connection to the database
	 *
	 * default: use de default element connection
	 * replication: use the replication connection
	 * 	$options is an integer which defines the max delay of the replication in seconds (let it to NULL to disable delay check)
	 * 		at() throws an exception if the delay is not respected
	 * others: you can use the name of connection you want (it must be defined in Database file)
	 */
	public function at(string $name, array $options = []): ModuleModel {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		if(is_int($options)) {
			$options = ['maxDelay' => $options];
		} else {
			$options = (array)$options;
		}

		$options += [
			'maxDelay' => NULL,
			'cacheTimeout' => NULL,
		];

		if(strpos($this->getConnection(), '@') === FALSE) {
			$this->atBefore = 'default';
		} else {
			$this->atBefore = $this->getConnection();
		}

		if($name === 'default') {
			$this->db($this->getPackage());
		} else if(strpos($name, 'replication') === 0) {
			if(in_array($name, $this->atNotFound)) {
				throw new ModuleException("No replication has been found");
			} else if($options['maxDelay'] !== NULL) {

				try {
					$delay = self::$db[$this->server]->api->getReplicationDelay($name, $options['cacheTimeout']);
				}
				catch(Exception $e) {
					$this->atBefore = NULL;
					$this->atNotFound[] = $name;
					throw new ModuleException("No replication has been found");
				}
				if($delay > $options['maxDelay']) {
					$this->atBefore = NULL;
					throw new ModuleException("The replication is too late");
				}
			}
			$this->db($this->getPackage().'@'.$name);
		} else if(strpos($name, '@') !== FALSE) {
			$this->db($name);
		} else {
			$this->db($this->getPackage().'@'.$name);
		}

		return $this;

	}

	/**
	 * Restore previous connection
	 *
	 */
	public function restore() {

		if($this->atBefore !== NULL) {
			$this->at($this->atBefore);
			$this->atBefore = NULL;
		}

	}

	/**
	 * Get dataBase name.
	 */
	public function getDb(): string {
		return $this->base;
	}

	/**
	 *  Get table name
	 *
	 * @return string
	 */
	public function getTable(string $suffix = ''): string {

		if(self::$prefix) {
			$prefix = self::$prefix->call($this, $this);
		} else {
			$prefix = '';
		}

		if($suffix !== '') {
			return $prefix.$this->table.'_'.$suffix;
		} else {
			return $prefix.$this->table;
		}


	}

	/**
	 *  Get Database object
	 *
	 * @return Database
	 */
	public function pdo(): Database {
		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		return self::$db[$this->server];
	}

	/**
	 * Begin transaction
	 *
	 * @return Database
	 */
	public function beginTransaction() {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		self::$dbTransactionStack[$this->server]++;

		if(self::$dbTransactionStack[$this->server] === 1) {
			return self::$db[$this->server]->beginTransaction();
		}

	}

	/**
	 * Commit transaction
	 *
	 * @return Database
	 */
	public function commit() {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		if(self::$dbTransactionStack[$this->server] === 0) {
			return;
		}

		if(self::$dbTransactionStack[$this->server] === 1) {
			return self::$db[$this->server]->commit();
		}

		self::$dbTransactionStack[$this->server]--;

	}

	/**
	 * Roll back transaction
	 *
	 * @return Database
	 */
	public function rollBack() {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}
		
		if(self::$dbTransactionStack[$this->server] === 0) {
			return;
		}

		self::$dbTransactionStack[$this->server] = 0;

		return self::$db[$this->server]->rollBack();

	}

	/**
	 * Roll back all pending transactions
	 *
	 * @return Database
	 */
	public static function rollBackEverything() {

		foreach(self::$dbTransactionStack as $server => $number) {

			if($number > 0) {
				self::$db[$server]->rollBack();
			}

		}

	}

	/**
	 * Change suffix for next query
	 *
	 * @param string $value A value for suffix
	 */
	public function suffix($value): ModuleModel {
		$this->suffix = (string)$value;
		return $this;
	}

	/**
	 * Set a persistent suffix
	 *
	 * @param string $value
	 */
	public function persistentSuffix($value) {
		self::$persistentSuffix[$this->module] = (string)$value;
		$this->suffix = self::$persistentSuffix[$this->module];
		return $this;
	}

	/**
	 * Get current persistent suffix
	 *
	 * @param string $value
	 */
	public function getPersistentSuffix() {
		return self::$persistentSuffix[$this->module] ?? '';
	}

	/**
	 * Set a persistent suffix
	 *
	 * @param string $value
	 */
	public function resetPersistentSuffix() {
		self::$persistentSuffix[$this->module] = '';
		$this->suffix = '';
	}

	/**
	 * Reset current suffix
	 *
	 * @return string Current suffix
	 */
	public function resetSuffix(): string {
		$this->previousSuffix = $this->suffix;
		$this->suffix = $this->getPersistentSuffix();
		return $this->previousSuffix;
	}

	/**
	 * Return current tables suffix
	 *
	 * @return string
	 */
	public function getSuffixes(): ModuleSplit {

		if($this->isSplitted()) {

			if($this->getSplitProperties()) {

				$suffixes = $this->split;
				$suffixes[] = '';

				return new ModuleSplit($suffixes);

			} else {
				return new ModuleSplit($this->split);
			}

		} else {
			return new ModuleSplit(['']);
		}

	}

	/**
	 * Select the fine table for an element
	 *
	 * @param mixed $value The value to hash
	 */
	public function with($value): ModuleModel {

		$suffix = $this->split($value);

		if($suffix !== NULL) {
			$this->suffix($suffix);
		}

		return $this;

	}

	/**
	 * Select the fine table from an id
	 *
	 * @param mixed $id An id
	 */
	public function withId($id): ModuleModel {

		$suffix = $this->splitById($id);

		if($suffix !== NULL) {
			$this->suffix($suffix);
		}

		return $this;
	}

	/**
	 * Select the fine table
	 *
	 */
	protected function withElement(Element $eElement): ModuleModel {

		$suffix = $this->getSuffixFromElement($eElement);

		if($suffix !== NULL) {
			$this->suffix($suffix);
		}

		return $this;

	}

	protected function getSuffixFromElement(Element $eElement) {

		if($this->getSplitMode() === 'sequence') {

			$property = $this->getSplitOn();
			$value = $eElement[$property] ?? NULL;

			if($value !== NULL) {
				return $this->split($value);
			} else {
				if($property === 'id') {
					return (string)mt_rand(0, $this->getSplit() - 1);
				} else {
					throw new Exception("Can not split NULL value for element ".$this->module);
				}
			}

		}

	}

	/**
	 * Join the next query to another module
	 *
	 * @param ModuleModel $mElement The module to join
	 * @param string $condition Join condition
	 * @param string $type Join type (LEFT, RIGHT, INNER)
	 */
	public function join(ModuleModel $mElement, string $condition = NULL, string $type = 'INNER'): ModuleModel {

		$type = strtoupper($type);

		if($type !== 'LEFT' and $type !== 'RIGHT' and $type !== 'INNER') {
			trigger_error("Invalid type has been set (please use LEFT, RIGHT, INNER)");
			exit;
		}

		if($this->join === []) {

			$this->join[] = [
				$this,
				NULL,
				NULL,
				NULL
			];

		}

		$mElementClone = clone $mElement;

		$this->join[] = [
			$mElementClone,
			$condition,
			$type,
			$mElementClone->resetSelection()
		];

		$mElement->resetSelection();

		return $this;

	}

	protected function joinSeveral(array $join): ModuleModel {
		$this->join = $join;
		return $this;
	}

	/**
	 * Reset the join to other modules
	 *
	 * @param Module A module
	 */
	public function resetJoin() {
		$this->join = [];
	}

	/**
	 * Format a value before insertion in the database
	 *
	 * @param mixed $value Value to format
	 */
	public function format($value): string {

		if($value === NULL) {
			return 'NULL';
		} else if(is_int($value) or is_float($value)) {
			return (string)$value;
		} else if(is_bool($value)) {
			return $value ? '1' : '0';
		} else if(is_string($value)) {
			$from = ['\\', '"', '\'', chr(0), chr(10), chr(13), chr(26)];
			$to = ['\\\\', '\\"', '\\\'', '\\0', '\\n', '\\r', '\\Z'];
			return "'".str_replace($from, $to, $value)."'";
		} else if($value instanceof Element) {
			if($value->offsetExists('id') === FALSE) {
				return 'NULL';
			} else {
				return $this->format($value['id']);
			}
		} else if($value instanceof Sql) {
			return $value->__toString();
		} else if($value instanceof Set) {
			return $value->get();
		} else {
			// Unexpected...
			return '';
		}

	}

	/**
	 * Prepare a list of values to use in a query
	 *
	 * @param string $property
	 * @param array $values The values to format
	 */
	public function prepareSeveral(string $property, $values): array {

		$output = [];

		foreach($values as $value) {
			$output[] = $this->prepare($property, $value);
		}

		return $output;

	}

	/**
	 * Prepare a value to use in a query
	 *
	 * @param string $property
	 * @param mixed $value The value to format
	 */
	public function prepare(string $property, $value): string {

		$valueEncoded = $this->encode($property, $value);
		return $this->format($valueEncoded);

	}

	/**
	 * Get an escaped field
	 */
	public function field(string $field): string {
		return "`".str_replace('.', '`.`', addcslashes($field, "`"))."`";
	}

	/**
	 * Returns current date
	 *
	 * @param string $mode Date mode (date, datetime, time, timestamp)
	 */
	public function now(string $mode = 'datetime', string $difference = NULL): string {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		return self::$db[$this->server]->api->now($mode, $difference);

	}

	/**
	 * Count elements from a condition
	 *
	 * @param mixed $distinct Count distinct values of a property (can be NULL)
	 * @return int
	 */
	public function count($distinct = NULL): int {

		if($this->union) {
			$this->union = FALSE;
			return $this->countUnion($distinct);
		} else {
			return $this->countSingle($distinct);
		}

	}

	protected function countSingle($distinct = NULL): int {

		if($this->join) {
			$selection = [new Sql('COUNT(*)')];
		} else {

			if($distinct === NULL) {
				// Count everything
				$selection = [new Sql('COUNT(*)')];
			} else {

				if($distinct instanceof Sql) {
					$value = (string)$distinct;
				} else {

					$property = (string)$distinct;

					if($this->hasProperty($property) === FALSE) {
						throw new ModuleException("\$distinct must be the name of a valid property");
					}

					$value = $this->field($property);

				}

				$selection = [new Sql('count(DISTINCT '.$value.')')];

			}

		}

		$sql = $this->buildSelect($selection);

		$this->resetJoin();

		try {

			if(empty(self::$db[$this->server])) {
				$this->db();
			}

			return (int)self::$db[$this->server]->selectUnique($sql);

		}
		catch(QueryDatabaseException $e) {

			$this->handleException($e);

		}

	}

	/**
	 * Count elements on a merge table
	 *
	 * @param mixed $distinct Count distinct values of a property (can be NULL)
	 * @return int
	 */
	protected function countUnion($distinct = NULL): int {

		$condition = $this->resetWhere();

		$join = $this->join;

		$total = 0;

		foreach($this->getSuffixes() as $suffix) {

			$this->join = $join;

			$total += $this
				->suffix($suffix)
				->where($condition)
				->countSingle($distinct);

		}

		return $total;

	}

	/**
	 * Get delegate parameters
	 *
	 * @return string
	 */
	public function resetDelegate() {

		$delegate = $this->delegate;
		$this->delegate = NULL;
		return $delegate;

	}

	/**
	 * Delegate get() query to another one
	 *
	 * @param string $property
	 * @param \Closure $callback
	 * @return \Module
	 */
	public function delegateElement(string $propertySource, \Closure $callback = NULL, string $propertyParent = 'id'): ModuleModel {

		if($this->isSelected($propertySource) === FALSE) {
			$this->select($propertySource);
		}

		$index = [$propertySource];

		return $this->delegateSomething('element', $propertySource, $propertyParent, $index, $callback, NULL);

	}

	/**
	 * Delegate get() query to another one
	 *
	 * @param string $property
	 * @param \Closure $callback
	 * @return \Module
	 */
	public function delegateArray(string $propertySource, \Closure $callback = NULL, string $propertyParent = 'id'): ModuleModel {

		if($this->isSelected($propertySource) === FALSE) {
			$this->select($propertySource);
		}

		$index = [$propertySource];

		return $this->delegateSomething('array', $propertySource, $propertyParent, $index, $callback, NULL);

	}

	/**
	 * Delegate a single property query to another one
	 *
	 * @param string $property
	 * @param string $select
	 * @return \Module
	 */
	public function delegateProperty(string $propertySource, $propertySelected, \Closure $callback = NULL, string $propertyParent = 'id'): ModuleModel {

		$select = [];

		if($this->isSelected($propertySelected) === FALSE) {

			if($propertySelected instanceof Sql) {
				$select['_'] = $propertySelected;
				$propertySelected = '_';
			} else {
				$select[] = $propertySelected;
			}

		}

		if($this->isSelected($propertySource) === FALSE) {
			$select[] = $propertySource;
		}

		if($select) {
			$this->select($select);
		}

		$index = [$propertySource];

		return $this->delegateSomething('property', $propertySource, $propertyParent, $index, $callback, $propertySelected);

	}

	/**
	 * Delegate collection() query to another one
	 *
	 * @param string $property
	 * @param array $index For indexing Collection
	 * @return \Module
	 */
	public function delegateCollection(string $propertySource, $index = NULL, \Closure $callback = NULL, string $propertyParent = 'id'): ModuleModel {

		if($this->isSelected($propertySource) === FALSE) {
			$this->select($propertySource);
		}

		$index = array_merge([$propertySource], $index === NULL ? [NULL] : (array)$index);

		return $this->delegateSomething('collection', $propertySource, $propertyParent, $index, $callback, NULL);

	}

	private function delegateSomething(string $type, string $propertySource, string $propertyParent, $index, ?\Closure $callback, ?string $propertySelected): ModuleModel {

		// If complex property source, then get the real property value
		if(array_key_exists($propertySource, $this->selection) and $this->selection[$propertySource] instanceof Sql) {
			$propertySource = $this->selection[$propertySource];
		}

		$this->delegate = [
			$propertySource,
			$propertyParent,
			$propertySelected,
			$type,
			$index,
			$callback
		];

		$mElement = clone $this;

		$this->reset();

		return $mElement;

	}

	/**
	 * Get links from a element group or an element
	 *
	 * @param &$mixed A Collection or an element
	 */
	public function setDelegation(&$mixed) {

		if($this->selectionDelegation === []) {
			return;
		}

		$parents = [];

		foreach($this->selectionDelegation as $destination => $mDelegate) {

			$delegate = $mDelegate->resetDelegate();

			[$propertySource, $propertyParent, $propertySelected, $type, $index, $callback] = $delegate;

			if($propertySource === NULL) {

				switch($type) {

					case 'property' :
						$mixed[$destination] = NULL;
						break;

					case 'element' :
						$mixed[$destination] = $mDelegate->getNewElement();
						break;

					case 'array' :
						$mixed[$destination] = NULL;
						break;

					case 'collection' :
						$mixed[$destination] = new Collection;
						break;

				}

			} else {

				if(isset($parents[$propertyParent]) === FALSE) {

					if($mixed instanceof Element) {
						$parents[$propertyParent] = [$mixed->empty() ? NULL : $mixed[$propertyParent]];
					} else if($mixed instanceof Collection) {
						$parents[$propertyParent] = $mixed->getColumn($propertyParent);
					} else {
						$parents[$propertyParent] = [];
						foreach($mixed as $e) {
							if($e instanceof Element === FALSE) {
								throw new Exception('Delegation error');
							} else {
								$parents[$propertyParent][] = $e->empty() ? NULL : $e[$propertyParent];
							}
						}
					}

				}

				if($parents[$propertyParent] === []) {
					continue;
				}

				$mDelegate->where($propertySource, 'IN', array_filter($parents[$propertyParent]));

				if(
					$propertySource === $mDelegate->getSplitOn() and
					$mixed instanceof Element
				) {
					$mDelegate->with($mixed);
				}

				$cDelegate = $mDelegate->getCollection(NULL, NULL, $index);

				$this->buildRecursiveDelegation($mixed, $destination, $propertyParent, $propertySelected, $type, $cDelegate, $callback);

			}

		}

	}

	private function buildRecursiveDelegation(&$mixed, $destination, string $propertyParent, ?string $propertySelected, string $type, Collection $cDelegate, ?\Closure $callback) {

		if($mixed instanceof Element) {

			if($mixed->notEmpty()) {

				if($mixed[$propertyParent] instanceof Element) {
					if($mixed[$propertyParent]->empty()) {
						return;
					} else {
						$parent = $mixed[$propertyParent]['id'];
					}
				} else {
					$parent = $mixed[$propertyParent];
				}

				switch($type) {

					case 'property' :
						$value = $cDelegate[$parent][$propertySelected] ?? NULL;
						break;

					case 'element' :
						$value = $cDelegate[$parent] ?? new Element;
						break;

					case 'array' :
						if($cDelegate->offsetExists($parent)) {
							$value = $cDelegate[$parent]->getArrayCopy();
						} else {
							$value = NULL;
						}
						break;

					case 'collection' :
						$value = $cDelegate[$parent] ?? new Collection;
						break;

				}

				if($callback !== NULL) {
					$value = $callback($value);
				}

				$mixed[$destination] = $value;
			}

		} else {

			foreach($mixed as $key => $subElement) {
				$this->buildRecursiveDelegation($mixed[$key], $destination, $propertyParent, $propertySelected, $type, $cDelegate, $callback);
			}

		}

	}


	/**
	 * Manage cache for the next query
	 *
	 * @param string $type Cache type (mem, redis, db, no, storage)
	 * @param string $key Cache key
	 * @param int $timeout Cache timeout
	 */
	public function cache(string $type, string $key = NULL, int $timeout = NULL): ModuleModel {
		if($type === 'none' or $type === 'storage') {
			$this->options['cache'] = $type;
		} else {
			$this->options['cache'] = [$type, $timeout, NULL, $key];
		}
		return $this;
	}


	/**
	 * Return a Collection which correspond to the selected fields (select()) and the current condition (condition())
	 *
	 * @param int $offset Position of the results in the query
	 * @param int $number Number of results
	 * @param array $index For indexing Collection
	 * @return Collection A Collection
	 */
	public function getCollection(int $offset = NULL, int $number = NULL, mixed $index = NULL): Collection|Generator {

		$selection = $this->resetSelection();
		$sql = $this->buildSelect($selection, $offset, $number);

		if($this->recordset !== NULL) {
			return $this->buildRecordSet($sql, $selection);
		}

		if($sql === NULL) {
			return new Collection();
		}

		try {

			$index = array_values((array)$index);

			if($this->join) {

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				try {
					$rs = self::$db[$this->server]->select($sql);
				} catch(QueryDatabaseException $e) {
					$this->handleException($e);
				}

				$cElement = $this->buildCollectionFromJoinRecordSet($rs, $index);

				if($this->foundInfo !== NULL) {
					$this->found = $this->getFound();
				}

				$this->resetJoin();

				return $cElement;

			} else {

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				$rs = self::$db[$this->server]->select($sql);

				$cElement = $this->buildCollectionFromRecordSet($rs, $index, $selection);

				if($this->foundInfo !== NULL) {
					$this->found = $this->getFound();
				}

				return $cElement;

			}

		} catch(QueryDatabaseException $e) {

			$this->handleException($e);

		}


	}

	/**
	 * Return an mRecordSet which correspond to the selected fields (select()) and the current condition (condition())
	 *
	 * @param int $offset Position of the results in the query
	 * @param int $number Number of results
	 * @param int $buffered Buffered / unbuffered recordset
	 * @return RecordSet
	 */
	protected function buildRecordSet(?string $sql, array $selection) {

		$buffered = $this->recordset;

		$this->recordset = NULL;

		if($sql === NULL) {
			return;
		}

		try {

			if($this->join) {

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				$rs = self::$db[$this->server]->select($sql);

				foreach($this->join as $position => $join) {

					list($mElement) = $join;

					$position++;

					$mElement->select($selection[$position] ?? []);

				}

				while($row = $rs->fetch(PDO::FETCH_ASSOC)) {

					$result = [];
					$fields = [];

					foreach($row as $field => $value) {

						list($prefix, $property) = explode('_', substr($field, 1), 2);
						$fields[$prefix][$property] = $value;

					}

					foreach($this->join as $position => list($mElement)) {

						$position++;

						$eElement = new $this->module;

						if(isset($fields[$position])) {
							$mElement->setAll($eElement, $fields[$position], $mElement);
							$mElement->setCallable($eElement);
						}

						$result[] = $eElement;


					}

					yield $result;

				}

				$this->resetJoin();

			} else {

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				if($buffered) {

					$database = self::$db[$this->server];

				} else {

					$database = self::dbInstance($this->getConnection(), [
						PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => FALSE,
					]);

				}

				$rs = $database->select($sql);

				while($row = $rs->fetch(PDO::FETCH_ASSOC)) {

					$eElement = new $this->module;
					$this->setAll($eElement, $row, $this);
					$this->setCallable($eElement);

					yield $eElement;

				}

				$rs->closeCursor();

			}

		} catch(QueryDatabaseException $e) {

			$this->handleException($e);
			return;

		}


	}

	/**
	 * Build query and apply callback to results
	 *
	 * @param int $offset Position of the results in the query
	 * @param int $number Number of results
	 * @return array An array
	 */
	public function callback(callable $callback, int $offset = NULL, int $number = NULL) {

		// We could use >recordset() if we are sure there are no sub-properties
		$cElement = $this->getCollection($offset, $number);

		foreach($cElement as $eElement) {
			$callback($eElement);
		}

	}

	/**
	 * Return is one row or more exists with the given condition
	 *
	 * @param mixed An element (array) or a group of elements (Collection) or nothing
	 * @return boolean
	 */
	public function exists(&$value = NULL): bool {

		if($value instanceof Collection) {
			$this->whereId('IN', $value);
		} else if($value instanceof Element) {
			if($value->empty() or $value->offsetExists('id') === FALSE) {
				return FALSE;
			} else {
				$this->whereId($value['id']);
			}
		}

		$options = $this->resetOptions();

		$subOptions = ['cache' => FALSE];

		if(isset($options['union'])) {
			$subOptions['union'] = $options['union'];
		}

		$sql = $this->buildSelect([new Sql('1')], NULL, NULL, $subOptions);

		$this->cacheOption = $options['cache'] ?? $this->getCache();

		if($this->cacheOption === 'none') {
			$sql = 'SELECT SQL_NO_CACHE EXISTS ('.$sql.')';
		} else {
			$sql = 'SELECT EXISTS ('.$sql.')';
		}

		if(isset($options['highlight'])) {
			$this->sql($sql);
		}

		$this->resetJoin();

		try {

			if(empty(self::$db[$this->server])) {
				$this->db();
			}

			$exists = (int)self::$db[$this->server]->selectUnique($sql);

			return (bool)$exists;
		}
		catch(QueryDatabaseException $e) {
			$this->handleException($e);
		}
	}


	/**
	 * Get elements
	 *
	 * @param mixed An element (array) or a group of elements (Collection)
	 * @return bool TRUE on success
	 * @throws ModuleException
	 */
	public function get(&$value = NULL): Element|bool {

		if($value instanceof Collection) {
			return $this->getSeveral($value);
		} else if($value === NULL) {
			$eElement = new $this->module;
			$this->getOne($eElement);
			return $eElement;

		} else if($value instanceof Element) {
			return $this->getOne($value);
		} else {
			throw new Exception(__CLASS__.'::get() called with invalid argument');
		}

	}

	/**
	 * Get an element from a condition or an identifier
	 *
	 * @param Element $eElement An element with some not null properties
	 * @param bool TRUE if an element has been found, FALSE otherwise
	 */
	protected function getOne(Element $eElement): bool {

		// No condition for the update
		$id = $eElement['id'] ?? NULL;

		// Element identifier is null
		if($id !== NULL) {

			$this->where("id = ".$this->prepare('id', $id));

			if($this->splitOn === 'id') {
				$this->withId($id);
			}

		}

		$selection = $this->resetSelection();
		$sql = $this->buildSelect($selection, 0, 1);

		try {

			if($this->join) {

				if($sql === NULL) {
					return FALSE;
				}

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				try {
					$rs = self::$db[$this->server]->select($sql);
				} catch(QueryDatabaseException $e) {
					$this->handleException($e);
				}

				$cElement = $this->buildCollectionFromJoinRecordSet($rs, NULL);

				$this->resetJoin();

				if($cElement->empty()) {
					return FALSE;
				}

				foreach($cElement->first() as $key => $value) {
					$eElement[$key] = $value;
				};

				return TRUE;

			} else {

				if($sql === NULL) {
					$this->setDelegation($eElement);
					return TRUE;
				}

				if(empty(self::$db[$this->server])) {
					$this->db();
				}

				$rs = self::$db[$this->server]->select($sql);


				$result = $rs->fetch();
				$rs->closeCursor();

				if($result) {

					$this->setAll($eElement, $result, $this);

					foreach($this->getPropertiesToElement() as $property => $element) {

						if(empty($selection[$property]) === FALSE) {

							if($eElement[$property]->notEmpty()) {

								$class = $element.'Model';

								(new $class())
									->select($selection[$property])
									->get($eElement[$property]);

							}

						}

					}

					$return = TRUE;
				} else {
					$return = FALSE;
				}

				if($return === TRUE) {
					$this->setDelegation($eElement);
					$this->setCallable($eElement);
				}

				if($this->foundInfo !== NULL) {
					$this->found = $this->getFound();
				}

				return $return;

			}

		} catch(QueryDatabaseException $e) {
			$this->handleException($e);
		}

	}

	/**
	 * Get multiple values from a condition or an identifier
	 *
	 * @param mixed $property The property to get the values from
	 * @param int $offset Position of the results in the query
	 * @param int $number Number of results
	 * @param array $index For indexing Collection
	 * @return array / Collection A list of values
	 */
	public function getColumn($property, int $offset = NULL, int $number = NULL, ?string $index = NULL) {

		if($property instanceof Sql) {
			$select = ['_' => $property];
			$property = '_';
		} else {
			$select = $property;
		}

		$cModule = $this
			->select($select)
			->getCollection($offset, $number);

		if(is_string($property) and $this->getPropertyToModule($property) !== NULL) {
			return $cModule->getColumnCollection($property, $index);
		} else {
			return $cModule->getColumn($property, $index);
		}

	}

	/**
	 * Get a single value from a condition or an identifier
	 *
	 * @param mixed $property The property to get the value from
	 * @return mixed A value
	 */
	public function getValue($property, mixed $defaultValue = NULL): mixed {

		if($property instanceof Sql) {
			$select = ['_' => $property];
			$property = '_';
		} else {
			$select = $property;
		}

		$eElement = new $this->module;

		if($this
			->select($select)
			->get($eElement)) {

			return $eElement[$property];

		} else {
			return $defaultValue;
		}

	}

	/**
	 * Get elements from an Collection.
	 * You can set an additional condition if you want.
	 * Identifier of elements that do not exist are set to NULL.
	 *
	 * @param Collection/array $cElement A Collection
	 * @throws ModuleException
	 */
	protected function getSeveral($cElement) {

		$this->found = 0;

		$ids = [];

		foreach($cElement as $key => $eElement) {

			if($eElement instanceof Element) {

				$id = $eElement['id'] ?? NULL;

				if($id !== NULL) {
					$ids[$key] = $id;
				}

			} else {
				throw new ModuleException("At least one element is not a '".$this->module."' element");
			}

		}

		// Nothing in the Collection
		if(empty($ids)) {
			return FALSE;
		}

		// Remove duplicate identifiers
		$ids = array_unique($ids);

		$selection = $this->resetSelection();

		try {
			$list = $this->buildSelection($selection);
		}
		catch(Exception $e) {
			throw new ModuleException($e->getMessage(), $e->getCode());
		}

		// Get the condition
		$condition = $this->resetWhere();

		// Get the suffix
		$suffix = $this->resetSuffix();

		//Get the options
		$options = $this->resetOptions();

		$this->cacheOption = $options['cache'] ?? $this->getCache();

		if($list or $condition or $options) {

			$list['id'] = $this->field('id');

			try {

				$sqlParts = [];

				if($this->getSplitMode() === 'sequence' and $suffix === '') {

					$idsStore = [];
					$splitOn = $this->getSplitOn();

					foreach($cElement as $eElement) {

						if($eElement->empty()) {
							continue;
						}

						if($splitOn === 'id') {
							$splitSuffix = $this->split($eElement['id']);
						} else {
							$splitSuffix = $this->splitById($eElement['id']);
						}

						$idsStore[$splitSuffix][] = $eElement['id'];

					}

					foreach($idsStore as $splitSuffix => $ids) {

						$sqlParts[] = $this->getSeveralSelect($list, $this->field($this->getDb()).'.'.$this->field($this->getTable().'_'.$splitSuffix), $ids, $condition);

						if($this->cacheOption === 'none') {
							$this->cacheOption = 'storage';
						}

					}

				} else {
					$sqlParts[] = $this->getSeveralSelect($list, $this->field($this->getDb()).'.'.$this->field($this->getTable($suffix)), $ids, $condition);
				}

				$sql = implode(' UNION ', $sqlParts);

			} catch(QueryDatabaseException $e) {
				$this->handleException($e);
			}

		} else {
			$sql = NULL;
		}

		if($sql !== NULL) {

			if(empty(self::$db[$this->server])) {
				$this->db();
			}

			$rs = self::$db[$this->server]->select($sql);

			$row = $rs->fetch(PDO::FETCH_ASSOC);

			if($row) {

				$subProperties = [];

				foreach($this->getPropertiesToElement() as $property => $element) {
					if(
						isset($selection[$property]) and
						is_array($selection[$property])
					) {
						$subProperties[$property] = [];
					}
				}

				$cElementInternal = [];

				do {

					$key = (int)$row['id'];
					$cElementInternal[$key] = new $this->module;

					$this->setAll($cElementInternal[$key], $row, $this);

					foreach($subProperties as $property => $values) {
						$subProperties[$property][] = &$cElementInternal[$key][$property];
					}

				} while($row = $rs->fetch(PDO::FETCH_ASSOC));

				$rs->closeCursor();

				foreach($subProperties as $property => $cElementProperty) {

					$class = $this->getPropertyToModule($property).'Model';

					(new $class())
						->select($selection[$property])
						->getSeveral($cElementProperty);

				}

				// Put internal element group in out element group
				foreach($cElement as $key => $eElement) {

					if($eElement->notEmpty() and isset($cElementInternal[$eElement['id']])) {

						foreach($cElementInternal[$eElement['id']] as $property => $value) {
							$cElement[$key][$property] = $value;
						}

						$this->found++;

					}

				}

			} else { // No result

				$rs->closeCursor();

			}

		} else {

		}

		$this->setDelegation($cElement);
		$this->setCallableCollection($cElement);

		return TRUE;

	}

	protected function getSeveralSelect(array $fields, string $table, array $ids, string $condition = NULL): string {

		$sql = 'SELECT ';

		if($this->cacheOption === 'none') {
			$sql .= 'SQL_NO_CACHE ';
		}

		$sql .= implode(', ', $fields).'
		FROM
			'.$table.'
		WHERE
			id IN ('.implode(', ', $ids).')';

		if($condition !== NULL) {
			$sql .= ' AND ('.$condition.')';
		}

		return $sql;

	}

	/**
	 * Build a Collection from a record set
	 *
	 * @param PDOStatement $rs A record set
	 * @param array $index Collection indexing
	 */
	public function buildCollectionFromRecordSet(PDOStatement $rs, $index, array $selection): Collection {

		$subProperties = [];
		$subPropertiesList = [];

		foreach($this->getPropertiesToElement() as $property => $element) {

			if(
				empty($selection[$property]) === FALSE and
				$element !== NULL
			) {
				$subProperties[$property] = [];
				$subPropertiesList[] = $property;
			}
		}

		$depth = count($index);
		$row = $rs->fetch(PDO::FETCH_ASSOC);

		if($row) {

			$elements = [];

			do {

				$eElement = new $this->module;
				$this->setAll($eElement, $row, $this);

				foreach($subPropertiesList as $property) {
					$subProperties[$property][] = &$eElement[$property];
				}

				$elements[] = $eElement;

			} while($row = $rs->fetch(PDO::FETCH_ASSOC));

			$rs->closeCursor();

			foreach($subProperties as $property => $cElementProperty) {

				$class = $this->getPropertyToModule($property).'Model';

				(new $class())
					->select($selection[$property])
					->getSeveral($cElementProperty);

			}

			$this->setDelegation($elements);
			$this->setCallableCollection($elements);

			if($depth === 0) {

				$cElement = new Collection($elements);

			} else {

				$cElement = new Collection();
				$cElement->setDepth($depth);

				foreach($elements as $eElement) {
					$this->addToCollection($cElement, $eElement, $index);
				}

			}

			$cElement->rewind();

			return $cElement;

		} else {

			$rs->closeCursor();

			$cElement = new Collection();
			$cElement->setDepth($depth);

			return $cElement;

		}


	}

	/**
	 * Build an Collection from a record set with jointures
	 *
	 * @param PDOStatement $rs A record set
	 */
	public function buildCollectionFromJoinRecordSet(PDOStatement $rs, $index) {

		$properties = [];
		$subProperties = [];

		foreach($this->join as $position => $value) {

			list($mElement, $condition, $type, $selection) = $value;

			if($selection) {
				$properties[$position] = $value;
			}

			$subProperties[$position] = [];

			foreach($mElement->getPropertiesToElement() as $property => $element) {

				if(empty($selection[$property]) === FALSE) {
					$subProperties[$position][$property] = [];
				}

			}

		}

		$row = $rs->fetch(PDO::FETCH_ASSOC);

		$references = array_fill(0, count($this->join), []);

		if($row) {

			$elements = [];

			do {

				$fields = [];

				foreach($row as $field => $value) {

					list($prefix, $property) = explode('_', substr($field, 1), 2);
					$fields[$prefix - 1][$property] = $value;

				}

				$eElementMain = NULL;

				foreach($properties as $position => list($mElement, $condition, $type, $selection)) {

					$eElement = $mElement->getNewElement();

					if(isset($fields[$position])) {
						$mElement->setAll($eElement, $fields[$position], $mElement);
					}

					if($eElementMain === NULL) {
						$eElementMain = $eElement;
					} else {
						$eElementMain->merge($eElement);
					}

					foreach($subProperties[$position] as $property => $values) {
						$subProperties[$position][$property][] = $eElement[$property];
					}

					$references[$position][] = $eElement;

				}

				$elements[] = $eElementMain;

			}  while($row = $rs->fetch(PDO::FETCH_ASSOC));

			$rs->closeCursor();

			foreach($properties as $position => list($mElement, , , $selection)) {

				foreach($subProperties[$position] as $property => $cProperty) {

					$class = $mElement->getPropertyToModule($property).'Model';

					(new $class())
						->select($selection[$property])
						->getSeveral($cProperty);

				}

				$mElement->setDelegation($references[$position]);
				$mElement->setCallableCollection($references[$position]);

			}

			$depth = $index ? count($index) : 0;

			if($depth === 0) {

				$cElement = new Collection($elements);

			} else {

				$cElement = new Collection();
				$cElement->setDepth($depth);

				foreach($elements as $eElement) {
					$this->addToCollection($cElement, $eElement, $index);
				}

			}

			return $cElement;

		} else {
			$rs->closeCursor();
			return new Collection();
		}

	}

	protected function addToCollection(Collection $cElement, $eElement, $index) {

		if($eElement instanceof Element === FALSE) {
			throw new ModuleException("Unknown error");
		} else if($index[0] !== NULL) {

			if($index[0] instanceof Closure) {
				$value = $index[0]->call($this, $eElement);
			} else {
				if($eElement->offsetExists($index[0]) === FALSE) {
					throw new ModuleException("Property '".$index[0]."' has not been selected");
				}
				$value = $eElement[$index[0]];
			}


			if($value instanceof Element) {
				if($value->empty()) {
					$offset = NULL;
				} else {
					$offset = $value['id'];
				}
			} else {
				$offset = is_bool($value) ? (int)$value : $value;
			}

		} else {
			$offset = NULL;
		}

		$depth = $cElement->getDepth();

		if($depth === 1) {
			if($offset === NULL) {
				$cElement[] = $eElement;
			} else {
				$cElement[$offset] = $eElement;
			}
		} else {

			// PHP bug with ArrayIterator
			if($offset === NULL) {
				$offset = '';
			}

			if(isset($cElement[$offset]) === FALSE) {

				$cElement[$offset] = new Collection();
				$cElement[$offset]->setDepth($depth - 1);

			}

			$this->addToCollection(
				$cElement[$offset],
				$eElement,
				array_slice($index, 1)
			);

		}
	}

	public function found() {
		return $this->found;
	}

	protected function getFound() {

		list($condition, $group, $having, $join, $suffix) = $this->foundInfo;
		$this->foundInfo = NULL;

		if(
			$this->isSplitted() === FALSE or
			$suffix !== ''
		) {
			$method = 'countSingle';
		} else {
			$method = 'countUnion';
		}

		if($group !== NULL) {
			$this->group($group);
		}

		$found = $this
			->joinSeveral($join)
			->suffix($suffix)
			->where($condition)
			->having($having)
			->$method();

		return $found;

	}

	/**
	 * Callback method called before insertion of the element in the database
	 *
	 * @param Element $eElement An element
	 */
	protected function cbAddProperties(&$eElement, $index): array {

		if($this->getSplitProperties() and $this->suffix !== '') {
			$properties = $this->getSplitProperties();
		} else {
			$properties = $this->propertiesList;
		}

		$sql = [];

		foreach($eElement as $property => $value) {

			if(isset($this->properties[$property])) {

				$encodedValue = $this->encode($property, $value);

				$sql[$property] = $this->format($encodedValue);

			} else if($property[0] === ':') {

				$suffix = substr($property, 1);

				if(isset($this->splitAdd[$suffix]) === FALSE) {
					$this->splitAdd[$suffix] = new Collection();
				}

				$eElementSplit = $value;

				if(strpos($this->getPropertyType('id'), 'int') === 0) {
					$eElementSplit['id'] = $eElement['id'];
				}

				$this->splitAdd[$suffix][$index] = $eElementSplit;

			}

		}

		foreach($properties as $property) {

			if(isset($sql[$property]) === FALSE) {

				$eElement[$property] = $this->getDefaultValue($property);

				$sql[$property] = $this->format($this->encode($property, $eElement[$property]));

			}

		}

		return $sql;

	}

	/**
	 * Add elements
	 *
	 * @param mixed An element (array) or a group of elements (Collection)
	 * @return int Affected rows
	 * @throws ModuleException
	 */
	public function insert(&$value): int {

		try {

			if($value instanceof Collection) {
				$affected = $this->insertSeveral($value);
			} else {
				$affected = $this->insertOne($value);
			}

			if($this->splitAdd) {

				$serial = (strpos($this->getPropertyType('id'), 'serial') === 0);

				//handle automatic add on split table when table has noName ID.
				if($serial) {
					if($value instanceof Collection) {
						foreach($value as $key => $eElement) {
							foreach($eElement as $property => $propertyValue) {
								if($property[0] === ':') {
									continue;
								}
								$this->where($property, $propertyValue);
							}

							$id = $this->getValue('id');
							foreach($this->splitAdd as $suffix => $cElement) {
								$this->splitAdd[$suffix][$key]['id'] = $id;
							}
						}
					} else {
						foreach($this->splitAdd as $suffix => $cElement) {
							$this->splitAdd[$suffix][0]['id'] = $value["id"];
						}
					}

				}

				$splitAdd = $this->splitAdd;
				$this->splitAdd = [];

				foreach($splitAdd as $suffix => $cElement) {

					$this
						->suffix($suffix)
						->insert($cElement);

				}

			}

			return $affected;

		}
		catch(QueryDatabaseException $e) {
			$this->handleException($e);
		}

	}

	protected function insertOne(Element $eElement): int {

		$this->withElement($eElement);

		// Execute some PHP code before insertion of the element in the database
		$properties = $this->cbAddProperties($eElement, 0);

		$fields = array_keys($properties);

		$ignore = NULL;
		$sql = $this->buildAdd($fields, [$properties], $ignore);

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		$affected = self::$db[$this->server]->exec($sql);

		if(
			isset($eElement['id']) === FALSE and
			$this->hasProperty('id') and
			$affected > 0
		) {
			$id = self::$db[$this->server]->lastInsertId($this->getDb().'.'.$this->getTable());
			$this->cast('id', $id);

			// Set element identifier
			$eElement['id'] = $id;

		}

		return $affected;

	}

	/**
	 * Add several elements
	 * DbManager::cbAddAfter() is not called with this method
	 *
	 * @param &$cElement The elements
	 * @return bool TRUE on success
	 * @throws ModuleException
	 */
	protected function insertSeveral(Collection $cElement): int {

		// Adds elements for non-splitted tables or if a table suffix has already been defined
		if($this->getSplitMode() !== 'sequence' or $this->suffix !== '') {
			return $this->insertSeveralProcessing($cElement);
		}
		// Splitted tables
		else {
			return $this->insertSeveralSplit($cElement);
		}

	}

	/**
	 * Add several elements with the same suffix
	 *
	 * @param &$cElement The elements
	 * @return bool TRUE on success
	 * @throws ModuleException
	 */
	protected function insertSeveralProcessing(Collection $cElement): int {

		if($cElement->empty()) {
			return 0;
		}

		$values = [];
		$fields = [];

		foreach($cElement as $index => $eElement) {

			// Get the SQL query to add the element in the database
			$properties = $this->cbAddProperties($eElement, $index);

			if($fields === []) {
				$fields = array_keys($properties);
			}

			$values[] = $properties;

		}

		// Insert the element in the database
		$sql = $this->buildAdd($fields, $values);

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		// Add the elements
		return self::$db[$this->server]->exec($sql);

	}

	/**
	 * Add several elements with the same suffix
	 *
	 * @param Collection $cElement The elements
	 * @return bool TRUE on success
	 * @throws ModuleException
	 */
	protected function insertSeveralSplit(Collection $cElement): int {

		$ccElement = new Collection();
		$ccElement->setDepth(2);

		foreach($cElement as $eElement) {

			// Construct element groups with the same table suffix
			$suffix = $this->getSuffixFromElement($eElement);

			$ccElement->push([$suffix, NULL], $eElement);

		}

		$affected = 0;

		// Save options to conserve it for each insertSeveralProcessing()
		$savedOptions = $this->options;

		foreach($ccElement as $suffix => $cElement) {

			$this->suffix($suffix);
			$this->options = $savedOptions;

			$affected += $this->insertSeveralProcessing($cElement);

		}

		$this->suffix = '';

		return $affected;

	}

	/**
	 * Update an element
	 *
	 * @param mixed $data The element or a condition string
	 * @param mixed $properties New properties (facultative)
	 * @return int Affected rows
	 */
	public function update(/*Element|array */$data, ?array $properties = NULL): int {

		if(is_array($properties)) {

			if($data instanceof Element === FALSE) {
				throw new Exception("Element expected, ".getType($data)." found");
			}

			$data = clone $data;

			if($this->selection === []) {
				$this->select(array_keys($properties));
			}

			foreach($properties as $key => $value) {
				$data[$key] = $value;
			}

		} else if(is_array($data)) {
			$this->selection = [];
			$this->select(array_keys($data));
		}

		try {

			if($this->union) {
				$this->union = FALSE;
				return $this->updateUnion($data);
			} else if($this->join) {
				return $this->updateJoin($data);
			} else {
				return $this->updateInternal($data);
			}

		} catch(QueryDatabaseException $e) {

			$this->handleException($e);

		}

	}

	protected function updateInternal($data): int {

		// Get the update string
		$update = $this->getUpdateString($data);

		// Get the update condition
		$condition = $this->getUpdateCondition($data);

		$sql = $this->buildUpdate($update, NULL, $condition);

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		return self::$db[$this->server]->exec($sql);

	}

	/**
	 * Update elements on a merge table
	 *
	 * @param mixed $data The element or a condition string
	 */
	public function updateUnion($data): int {

		$condition = $this->resetWhere();
		$affected = 0;

		foreach($this->getSuffixes() as $suffix) {

			$affected += $this
				->suffix($suffix)
				->where($condition)
				->updateInternal($data);

		}

		return $affected;

	}

	protected function updateJoin($data): int {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		$condition = $this->getUpdateCondition($data, 'm1.');
		$update = $this->getUpdateString($data, 'm1.');
		$join = $this->buildJoin();

		$sql = $this->buildUpdate($update, $join, $condition);

		$this->resetJoin();

		return self::$db[$this->server]->exec($sql);

	}

	protected function getUpdateCondition($data, $prefix = ''): string {

		// Get the update condition
		$condition = $this->resetWhere();

		// Get the update string
		if(
			$data instanceof Element and
			$data->offsetExists('id')
		) {

			$newCondition = $prefix.'id = '.$this->prepare('id', $data['id']);

			// No condition for the update
			if($condition === NULL) {
				$condition = $newCondition;
			} else {
				$condition = '('.$condition.') AND '.$newCondition;
			}

			if($this->splitOn === 'id' and $data['id'] !== NULL) {
				$this->withId($data['id']);
			}

		} else if(is_array($data)) {

			// No condition for the update
			if($condition === NULL) {
				throw new ModuleException("Update requires a condition or an element");
			}

		}

		return $condition;

	}

	protected function getUpdateString($data, $prefix = '') {

		if(is_string($data)) {
			return $data;
		} else if(is_array($data) or $data instanceof Element) {

			$selection = $this->resetSelection();

			$new = [];

			// Update of selected properties
			foreach($selection as $property) {
				$new[] = $prefix.$this->getUpdateProperty($data, $property);
			}

			if($new) {
				return implode(',', $new);
			} else {
				return NULL;
			}

		} else {
			throw new ModuleException("Expected a '".$this->module."' element");
		}

	}

	/**
	 * Callback method called to build a SQL statement for a property
	 */
	protected function getUpdateProperty($eElement, $property): string {

		$value = $eElement[$property] ?? NULL;

		if($value instanceof Sql === FALSE) {
			$value = $this->encode($property, $value);
		}

		if($this->isPropertyNull($property) === FALSE) {
			if($value === NULL) {
				throw new ModuleException("Property '".$property."' must not be null");
			}
		}

		return $this->field($property).' = '.$this->format($value);

	}

	/**
	 * Delete elements
	 *
	 * @param mixed $value The element or NULL if you set a condition
	 * @return int Affected rows
	 * @throws ModuleException
	 */
	public function delete($value = NULL): int {

		try {

			if($this->union) {
				$this->union = FALSE;
				return $this->deleteUnion($value);
			} else if($value instanceof Collection) {
				return $this->deleteSeveral($value);
			} else {
				return $this->deleteInternal($value);
			}

		}
		catch(QueryDatabaseException $e) {

			$this->handleException($e);

		}

	}

	protected function deleteSeveral(Collection $cElement): int {

		if($cElement->notEmpty()) {

			return $this
				->whereId('IN', $cElement)
				->deleteInternal(NULL);

		} else {
			return 0;
		}

	}

	protected function deleteInternal($eElement): int {

		if($eElement !== NULL and ($eElement instanceof Element) === FALSE) {
			throw new ModuleException("Expected an instance of '".$this->module."'");
		}

		if($this->join) {
			return $this->deleteJoin($eElement);
		}

		$sql = $this->deleteCreate($eElement);

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		return self::$db[$this->server]->exec($sql);

	}

	protected function deleteUnion($value): int {

		$affected = 0;

		if($value instanceof Collection) {

			// TODO : optimisation possible pour n'aller que sur les tables réellement demandées par le Collection
			// Si et seulement si $value->count() > $count

			foreach($this->getSuffixes() as $suffix) {

				$affected += $this
					->suffix($suffix)
					->deleteSeveral($value);

			}

		} else {

			$condition = $this->resetWhere();
			$options = $this->resetOptions();

			$join = $this->join;


			foreach($this->getSuffixes() as $suffix) {

				$this->join = $join;

				$affected += $this
					->suffix($suffix)
					->where($condition)
					->options($options)
					->deleteInternal(NULL);

			}

		}

		$this->suffix('');

		return $affected;

	}

	protected function deleteJoin($eElement): int {

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		$condition = $this->getDeleteCondition($eElement, 'm1.');
		$sort = $this->buildSort($this->resetSort());
		$join = $this->buildJoin();

		$sql = $this->buildDelete($join, $condition, $sort);

		$this->resetJoin();

		return self::$db[$this->server]->exec($sql);

	}

	protected function getDeleteCondition($eElement, $prefix = ''): string {

		// No condition for the update
		$id = $eElement['id'] ?? NULL;

		// Element identifier is null
		if($id !== NULL) {

			$this->where("id = ".$this->prepare('id', $id));

			if($this->splitOn === 'id') {
				$this->withId($id);
			}

		}

		$condition = $this->resetWhere();

		// No condition specified
		if($condition === NULL) {
			throw new ModuleException("Delete requires a condition or an element");
		}

		return $condition;

	}

	private function deleteCreate($eElement): string {

		$condition = $this->getDeleteCondition($eElement);
		$sort = $this->buildSort($this->resetSort());

		return $this->buildDelete(NULL, $condition, $sort);

	}

	/**
	 * Optimize table
	 *
	 * @return bool TRUE on success
	 */
	public function optimize(): bool {
		return $this->action('OPTIMIZE');
	}

	/**
	 * Repair table
	 *
	 * @return bool TRUE on success
	 */
	public function repair(): bool {
		return $this->action('REPAIR');
	}

	/**
	 * Flush table
	 *
	 * @return bool TRUE on success
	 */
	public function flush(): bool {
		return $this->action('FLUSH');
	}

	private function action(string $action): bool {

		if(strpos($this->getConnection(), '@replication') !== FALSE) {
			throw new Exception("Can not use write statements with replications");
		}

		if(empty(self::$db[$this->server])) {
			$this->db();
		}

		if($this->isSplitted() === FALSE) {
			$this->actionTable($action);
		} else {

			foreach($this->getSuffixes() as $suffix) {

				$this
					->suffix($suffix)
					->actionTable($action);

			}

			$this->suffix(''); // devrait pas être là mais actionTable() fait pas encore son boulot

		}

		return TRUE;

	}

	private function actionTable(string $action) {

		try {
			$sql = $action." TABLE ".$this->field($this->getDb()).'.'.$this->field($this->getTable())."";
			self::$db[$this->server]->exec($sql, TRUE);
		}
		catch(QueryDatabaseException $e) {
			$this->handleException($e);
		}

	}

	/**
	 * Build a Select SQL query
	 *
	 * @param string $select Selected fields
	 * @param int $offset Position of the results in the query
	 * @param int $number Number of results
	 * @param array $options Options for the query
	 * @return string A SQL query
	 */
	protected function buildSelect(
		array $selection,
		?int $offset = NULL, ?int $number = NULL,
		?array $options = NULL
	) {

		$condition = $this->resetWhere();
		$group = $this->resetGroup();
		$having = $this->resetHaving();
		$sort = $this->resetSort();
		$suffix = $this->resetSuffix();

		if($options === NULL) {
			$options = $this->resetOptions();
		}

		$this->cacheOption = $options['cache'] ?? $this->getCache();

		if($this->join) {
			$select = implode(', ', $this->buildSelectionJoin($selection));
		} else {
			$select = implode(', ', $this->buildSelection($selection));
		}

		if($select === '') {
			return NULL;
		}

		$join = $this->buildJoin();

		$table = $this->field($this->getDb()).'.'.$this->field($this->getTable($suffix));
		if($join) {
			$table .= " AS ".$this->field('m1');
		}

		$table .= $this->index($options, $suffix);
		$table .= $join;

		$groupBuild = $this->buildGroup($group);
		$sortBuild = $this->buildSort($sort);
		$splitUnions = $this->getSplitUnions();

		if($this->union and $this->isSplitted()) {

			$this->union = FALSE;

			// get all splitted tables if whereByElements is not specified
			if($splitUnions === []) {

				foreach($this->getSuffixes() as $suffix) {
					$splitUnions[] = $suffix;
				}

			}

			$parts = [];
			foreach($splitUnions as $splitSuffix) {

				$splittedTable = str_replace($this->field($this->getTable()), $this->field($this->getTable().'_'.$splitSuffix), $table);
				$parts[] = $this->buildSelectPart($select, $splittedTable, $condition, $groupBuild, $having);

				if($this->cacheOption === 'none') {
					$this->cacheOption = 'storage';
				}
			}

			$sql = implode(' UNION ', $parts);

		} else {
			$sql = $this->buildSelectPart($select, $table, $condition, $groupBuild, $having);
		}

		if($sortBuild !== NULL) {
			$sql .= " ORDER BY ".$sortBuild;
		}

		$sql .= $this->buildLimit($offset, $number);

		if(isset($options['highlight'])) {
			$this->sql($sql);
		}

		if(isset($options['count'])) {
			$this->foundInfo = [$condition, $group, $having, $this->join, $suffix];
		}

		return $sql;

	}

	private function buildSelectPart(string $select, string $table, $condition, $group, $having): string {

		$part = 'SELECT ';

		if($this->cacheOption === 'none') {
			$part .= ' SQL_NO_CACHE ';
		}

		$part .= $select;
		$part .= " FROM ".$table;

		if($condition !== NULL and $condition !== '1') {
			$part .= " WHERE ".$condition;
		}
		if($group) {
			$part .= " GROUP BY ".$group;
		}
		if($having !== NULL) {
			$part .= " HAVING ".$having;
		}
		return $part;

	}

	/**
	 * Build a Delete SQL query
	 *
	 * @param array $join Jointure to add
	 * @param string $condition A condition
	 * @param string $sort Sorting condition
	 * @return string A SQL query
	 */
	protected function buildDelete($join = NULL, string $condition = NULL, string $sort = NULL): string {

		if(strpos($this->getConnection(), '@replication') !== FALSE) {
			throw new Exception("Can not use write statements with replications");
		}

		$options = $this->resetOptions();
		$suffix = $this->resetSuffix();

		$table = $this->field($this->getDb()).'.'.$this->field($this->getTable($suffix));
		if($join) {
			$table .= " AS ".$this->field('m1');
		}

		if($join) {

			if(isset($options['delete-join'])) {
				$fieldsJoin = implode(',', $options['delete-join']);
				if($fieldsJoin === '*') {
					$fields = [];
					for($i = 1, $count = count($this->join); $i <= $count + 1; $i++) {
						$fields[] = $this->field('m'.$i);
					}
				} else {
					$fields = [];
					foreach($options['delete-join'] as $field) {
						$fields[] = $this->field($field);
					}
				}
			} else {
				$fields = [
					$this->field('m1')
				];
			}

			$sql = "USE ".$this->field($this->getDb())."; ";
			$sql .= "DELETE ".implode(',', $fields)." FROM ".$table."";
			$sql .= $join;

		} else {
			$sql = "DELETE FROM ".$table;
		}

		if($condition !== NULL and $condition !== '1') {
			$sql .= " WHERE ".$condition;
		}

		if($sort !== NULL) {
			$sql .= " ORDER BY ".$sort;
		}

		$limit = $this->resetLimit();
		if($limit !== NULL) {
			$sql .= " ".$this->buildLimit(0, $limit);
		}

		if(isset($options['highlight'])) {
			$this->sql($sql);
		}

		return $sql;

	}


	/**
	 * Build a Add SQL query
	 *
	 * @param array $fields Fields to add
	 * @param array $list List of values
	 * @return string A SQL query
	 */
	protected function buildAdd(
		array $fields,
		array $values,
		bool &$ignore = NULL
	): string {

		if(strpos($this->getConnection(), '@replication') !== FALSE) {
			throw new Exception("Can not use write statements with replications");
		}

		$options = $this->resetOptions();
		$suffix = $this->resetSuffix();

		$table = $this->field($this->getDb()).'.'.$this->field($this->getTable($suffix));

		if(isset($options['add-replace'])) {
			$sql = "REPLACE";
		} else {
			$sql = "INSERT";
		}

		$ignore = isset($options['add-ignore']);

		if($ignore) {
			$sql .= ' IGNORE';
		}

		$sql .= " INTO ".$table."(
			".implode(', ', array_map([$this, 'field'], $fields))."
		) VALUES";

		$rows = [];

		foreach($values as $value) {
			$row = '';
			foreach($fields as $field) {
				$row .= $value[$field].',';
			}
			$rows[] = '('.substr($row, 0, -1).')';
		}

		$sql .= implode(', ', $rows);

		if(isset($options['highlight'])) {
			$this->sql($sql);
		}

		return $sql;

	}


	/**
	 * Build a Update SQL query
	 *
	 * @param array $update Update string
	 * @param array $join Jointure to add
	 * @param string $condition A condition
	 * @return string A SQL query
	 */
	protected function buildUpdate(
		$update,
		$join = NULL,
		string $condition = NULL
	): string {

		if(strpos($this->getConnection(), '@replication') !== FALSE) {
			throw new Exception("Can not use write statements with replications");
		}

		$options = $this->resetOptions();
		$suffix = $this->resetSuffix();

		$table = $this->field($this->getDb()).'.'.$this->field($this->getTable($suffix));
		if($join) {
			$table .= " AS ".$this->field('m1');
		}

		$sql = "UPDATE";

		if(isset($options['update-ignore'])) {
			$sql .= " IGNORE";
		}

		$sql.= " ".$table;


		$sql .= $this->index($options, $suffix);
		$sql .= $join;

		$sql .= " SET ".$update;

		if($condition !== NULL and $condition !== '1') {
			$sql .= " WHERE ".$condition;
		}

		$sortBuild = $this->buildSort($this->resetSort());
		if($sortBuild !== NULL) {
			$sql .= " ORDER BY ".$sortBuild;
		}

		$limit = $this->resetLimit();
		if($limit !== NULL) {
			$sql .= " ".$this->buildLimit(0, $limit);
		}

		if(isset($options['highlight'])) {
			$this->sql($sql);
		}

		return $sql;

	}

	/**
	 * Build join entries
	 *
	 * @return string
	 */
	protected function buildJoin(): string {

		if(empty($this->join)) {
			return '';
		}

		$sql = '';

		foreach($this->join as $position => $join) {

			if($position === 0) {
				continue;
			}

			list($mElement, $condition, $type) = $join;

			$suffix = $mElement->resetSuffix();

			$field = $this->field($mElement->getDb()).'.'.$this->field($mElement->getTable($suffix)).' AS '.$this->field('m'.($position + 1));

			$sql .= ' '.$type.' JOIN '.$field.' ON '.$condition;

		}

		return $sql;

	}

	/**
	 * Build a limit from an offset and a number
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return string
	 */
	protected function buildLimit(int $offset = NULL, int $limit = NULL): string {

		if($offset !== NULL and $limit !== NULL) {

			// Avoid to open a DB connection (if using quote() of Database
			if($offset >= 0 and $limit >= 0) {
				return ' '.$this->getLimit($offset, $limit);
			} else {
				return ' '.$this->getLimit(0, 0);
			}

		}

		return '';

	}

	private function getLimit(int $start, int $length): string {
		$return = 'LIMIT ';
		if($start > 0) {
			$return .= $start.', ';
		}
		$return .= $length;
		return $return;
	}

	/**
	 * Get a group string
	 *
	 * @param mixed $group
	 */
	protected function buildGroup($group) {

		if(empty($group)) {
			return NULL;
		} else if(is_array($group)) {

			$condition = [];

			foreach($group as $property) {

				if($property instanceof Sql) {
					$condition[] = $property;
				} else {
					$condition[] = $this->buildField($property);
				}

			}

			if(empty($condition)) {
				return NULL;
			}

			return implode(', ', $condition);

		} else if(is_string($group)) {
			return $this->buildField($group);
		} else if($group instanceof Sql) {
			return $group->__toString();
		} else {
			throw new ModuleException("Bad arguments for group");
		}

	}

	/**
	 * Get a sorting string
	 *
	 */
	protected function buildSort(Sql|string|array|null $sort): ?string {

		if(empty($sort)) {
			return NULL;
		} else if(is_array($sort)) {

			$condition = [];

			foreach($sort as $key => $value) {

				if($value instanceof Sql) {
					$condition[] = $value->__toString();
				} else {

					$sortType = ($value === SORT_DESC) ? 'DESC' : 'ASC';
					$condition[] = $this->buildField($key).' '.$sortType;

				}

			}

			if(empty($condition)) {
				return NULL;
			}

			return implode(', ', $condition);

		} else if(is_string($sort)) {
			return $this->buildField($sort);
		} else if($sort instanceof Sql) {
			return $sort->__toString();
		} else {
			throw new ModuleException("Bad arguments for sorting");
		}

	}

	/**
	 * Build a field from a property name
	 *
	 * @param string $property
	 * @return string
	 */
	protected function buildField(string $property): string {

		if(strpos($property, '.') !== FALSE) {

			list($prefix, $suffix) = explode('.', $property, 2);

			return $this->field($prefix).'.'.$this->field($suffix);

		} else if($property instanceof Sql) {
			return (string)$property;
		} else {
			return $this->field($property);
		}

	}

	/**
	 * Verify selected fields for an element and return a valid property list for jointures
	 *
	 * @return array A list of properties
	 */
	protected function buildSelectionJoin(array $selectionZero): array {

		// Join issue
		if($this->server === NULL) {
			$this->db();
		}

		$fieldList = [];
		$this->propertiesTemporary = [];
		$this->propertiesCallable = [];

		foreach($this->join as $position => list($mElement, , , $selection)) {

			if($position === 0) {
				$selection = $selectionZero;
				$this->join[0][3] = $selectionZero;
			}

			$table = 'm'.($position + 1);

			$fieldList = array_merge($fieldList, $mElement->buildSelection($selection, $table));

		}

		return array_unique($fieldList);

	}

	/**
	 * Verify selected fields for an element and return a valid property list
	 * If there is no property selected, this method return ['0 AS cowleoptere']
	 *
	 * @return array A list of properties
	 */
	protected function buildSelection(array $selection, ?string $table = NULL): array {

		$fieldList = [];
		$this->propertiesTemporary = [];
		$this->propertiesCallable = [];

		$this->selectionDelegation = [];

		foreach($selection as $key => $value) {

			$field = is_string($value) ? $value : $key;
			$value = is_string($value) ? ['id'] : $value;

			if($value instanceof Sql) {

				$fieldList[$field] = [$value->__toString(), FALSE, function($field) {
					return $field;
				}];

				if(is_closure($value->getType())) {
					$this->saveTemporary($field, $value->getType(), TRUE);
				} else {
					$mask = (array)$value->getType() + ['null' => TRUE];
					$this->saveTemporary($field, $mask, TRUE);
				}

			} else if(is_closure($value)) {

				$this->saveCallable($field, $value);

			} else if($this->hasProperty($field)) { // Verify that the property is valid

				$fieldList[$field] = [$field, TRUE, $this->getFieldCallback($field)];

			} else if($value instanceof ModuleModel) {

				$this->selectionDelegation[$field] = $value;

			} else if($value === NULL) {
				continue;
			} else {
				throw new ModuleException('Property '.$this->module.'::$'.$field.' does not exist');
			}

		}

		$list = [];

		if($table !== NULL) {

			foreach($fieldList as $fieldName => [$fieldSql, $hasTable, $fieldCallback]) {

				if($hasTable) {
					$field = $table.'.'.$fieldSql;
				} else {
					$field = $fieldSql;
				}

				$sqlField = $fieldCallback($field);
				$sqlAs = $this->field($table.'_'.$fieldName);

				$list[] = $sqlField.' AS '.$sqlAs;

			}

		} else {

			foreach($fieldList as $fieldName => [$fieldSql, , $fieldCallback]) {

				$sqlField = $fieldCallback($fieldSql);
				$sqlAs = $this->field($fieldName);

				if($sqlField !== $sqlAs) {
					$list[] = $sqlField.' AS '.$sqlAs;
				} else {
					$list[] = $sqlField;
				}

			}
		}

		return $list;

	}

	protected function getFieldCallback(string $property): Closure {

		switch($this->getPropertyType($property)) {

			case 'point' :
				return function(string $field) {
					return 'ST_AsGeoJSON(ST_PointFromText(ST_AsText('.$this->field($field).')))';
				};

			case 'polygon' :
				return function(string $field) {
					return 'ST_AsGeoJSON(ST_PolygonFromText(ST_AsText('.$this->field($field).')))';
				};

			default :
				return function($field) {
					return $this->field($field);
				};

		}

	}

	private function sql(string $sql) {

		if(empty(self::$db[$this->server])) {
			$this->db($this->getPackage());
		}

		if(Route::getRequestedWith() === 'cli') {
			echo "\033[01m".$this->module."\033[00m: ".$sql."\n";
		} else if(Route::getRequestedWith() === 'http') {
			echo "<span class='debug'><strong>".$this->module."</strong>: ".$sql."</span><br/>\n";
		} else {
			echo $this->module.': '.$sql.'\n';
		}

	}

	private function index(array $options, $suffix): string {

		$mode = NULL;
		$fields = NULL;

		if(isset($options['index-force'])) {
			$mode = 'FORCE';
			$fields = $options['index-force'];
		} else if(isset($options['index-ignore'])) {
			$mode = 'IGNORE';
			$fields = $options['index-ignore'];
		}

		if($fields !== NULL) {

			$constraintsIndex = $this->getIndexConstraints();
			$constraintsUnique = $this->getUniqueConstraints();

			$indexes = [];

			foreach($fields as $field) {

				$field = (array)$field;

				$id = array_keys($constraintsIndex, $field, TRUE);

				if($id) {
					$type = 'index';
				} else {
					$id = array_keys($constraintsUnique, $field, TRUE);
					$type = 'unique';
				}

				$field = array_map([$this, 'field'], $field);

				if(empty($id)) {
					throw new ModuleException("The index or unique constraint for ".implode(', ', $field)." can not be found");
				} else {
					$id = $id[0];
				}

				$indexes[] = $this->field($this->getTable($suffix).'_'.strtolower($type).'_'.$id);

			}

			return ' '.$mode.' INDEX('.implode(', ', $indexes).')';

		} else {
			return '';
		}

	}

	/* Exception to fix MySQL bug */
	protected function handleException(Exception $eCatch) {

		$data = [
			'db' => $this->getDb(),
			'table' => $this->getTable()
		];

		switch($eCatch->getCode()) {

			case QueryDatabaseException::ERROR_DUPLICATE_ENTRY :

				$eThrow = new DuplicateException(
					$eCatch->getMessage(),
					DuplicateException::EXISTING
				);

				if(preg_match('/^Duplicate entry \'(.*?)\' for key \'(.+?)\'/si', $eCatch->getMessage(), $result) > 0) {

					if(strpos($result[2], 'PRIMARY') !== FALSE) {
						$data['duplicate'] = ['id'];
					} else {

						$match = NULL;
						if(preg_match('/unique\_([0-9]+)$/si', $result[2], $match)) {
							$data['duplicate'] = $this->getUniqueConstraints()[$match[1]];
						} else {
							$data['duplicate'] = NULL;
						}

					}

				} else {
					$data['duplicate'] = NULL;
				}

				break;

			default :
				$eThrow = new ModuleException(
					'SQL error: '.$eCatch->getMessage(),
					$eCatch->getCode()
				);

		}

		$eThrow->setInfo($data);

		throw $eThrow;

	}

	private function getSplitUnions() {
		return $this->splitUnions;
	}

}

/**
 * Exception thrown by modules
 *
 */
class ModuleException extends Exception {

	/**
	 * Additional info about the exception
	 *
	 * @var array
	 */
	protected $info = [];

	/**
	 * Set additional info
	 *
	 * @param array $info
	 */
	public function setInfo(array $info) {
		$this->info = $info + $this->info;
	}

	/**
	 * Get info
	 *
	 * @return array
	 */
	public function getInfo(): array {
		return $this->info;
	}

}

/**
 * Exception for duplicate entries thrown in modules
 *
 */
class DuplicateException extends ModuleException {

	/**
	 * Duplicate entry in the database...
	 *
	 * @var int
	 */
	const EXISTING = 9991062;

}

/**
 * For splits
 */
class ModuleSplit extends ArrayIterator {

	/**
	 * Split possible values
	 *
	 * @var mixed
	 */
	protected $split;

	/**
	 * Split current position
	 *
	 * @var int
	 */
	protected $current = 0;

	/**
	 * Split last position
	 *
	 * @var int
	 */
	protected $to = 0;

	public function __construct($split) {

		$this->split = $split;

		if(is_int($split)) {
			$this->to = $split;
		} else {
			$this->to = count($split);
		}

	}

	public function rewind(): void {
		$this->current = 0;
	}

	public function next(): void {
		$this->current++;
	}

	public function current(): mixed {

		if(is_int($this->split)) {
			return $this->current;
		} else {
			return $this->split[$this->current];
		}

	}

	public function valid(): bool {
		return ($this->current < $this->to);
	}

}


/**
 * Describe modules pages
 */
abstract class ModulePage extends Page {

	protected string $module;

	protected Closure $element;
	protected Closure $createElement;
	protected Closure $applyElement;

	public function __construct(
		?Closure $start,
		protected Closure|array $propertiesCreate,
		protected Closure|array $propertiesUpdate
	) {

		$this->createElement = function($data) {
			return ($this->module.'Lib')::getCreateElement();
		};

		$this->element = function($data) {
		};

		$this->applyElement = function($data, Element $e) {
		};

		parent::__construct($start);

	}

	public function getElement(Closure $callback): ModulePage {
		$this->element = $callback;
		return $this;
	}

	public function getCreateElement(Closure $callback): ModulePage {
		$this->createElement = $callback;
		return $this;
	}

	public function applyElement(Closure $callback): ModulePage {
		$this->applyElement = $callback;
		return $this;
	}

	protected function getPropertiesCreate(Element $e, ?array $propertiesCreate): array {
		$propertiesCreate ??= $this->propertiesCreate;
		return is_closure($propertiesCreate) ? $propertiesCreate->call($this, $e) : $propertiesCreate;
	}

	protected function getPropertiesUpdate(Element $e, ?array $propertiesUpdate): array {
		$propertiesUpdate ??= $this->propertiesUpdate;
		return is_closure($propertiesUpdate) ? $propertiesUpdate->call($this, $e) : $propertiesUpdate;
	}

	public function create(?\Closure $action = NULL, string|array $method = 'get', ?array $propertiesCreate = NULL, string $page = 'create', array $validate = ['canCreate']): ModulePage {

		$this->match((array)$method, $page, function($data) use ($action, $propertiesCreate, $validate) {

			$data->e = $this->createElement->call($this, $data);

			$data->e->validate(...$validate);

			$data->properties = $this->getPropertiesCreate($data->e, $propertiesCreate);

			if($action === NULL) {
				throw new \ViewAction($data);
			} else {
				$action->call($this, $data);
			}

		});

		return $this;

	}

	public function doCreate(\Closure $action, ?array $propertiesCreate = NULL, string $page = 'doCreate', array $validate = ['canCreate']): ModulePage {

		$this->post($page, function($data) use ($action, $propertiesCreate, $validate) {

			$fw = new \FailWatch();

			$e = $this->createElement->call($this, $data);
			$e['id'] = NULL;

			$e->validate(...$validate);

			$properties = $this->getPropertiesCreate($e, $propertiesCreate);
			$e->build($properties, $_POST, for: 'create');

			$fw->validate();

			($this->module.'Lib')::create($e);

			$fw->validate();

			$data->e = $e;
			$action->call($this, $data);

		});

		return $this;

	}

	public function update(\Closure $action = NULL, string|array $method = 'get', ?array $propertiesUpdate = NULL, string $page = 'update', array $validate = ['canUpdate']): ModulePage {

		$this->match((array)$method, $page, function($data) use ($action, $method, $propertiesUpdate, $validate) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = match($method) {
					'get' => GET('id', '?int'),
					'post' => POST('id', '?int')
				};

				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);

			$data->e = $e;

			$data->properties = $this->getPropertiesUpdate($e, $propertiesUpdate);

			if($action === NULL) {
				throw new \ViewAction($data);
			} else {
				$action->call($this, $data);
			}

		});

		return $this;

	}

	public function doUpdate(\Closure $action, ?array $propertiesUpdate = NULL, string $page = 'doUpdate', array $validate = ['canUpdate']): ModulePage {

		$this->post($page, function($data) use ($action, $propertiesUpdate, $validate) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = POST('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);

			$fw = new \FailWatch();

			$properties = $this->getPropertiesUpdate($e, $propertiesUpdate);

			$e->build($properties, $_POST, for: 'update');

			$fw->validate();

			($this->module.'Lib')::update($e, $properties);

			$fw->validate();

			$data->e = $e;
			$action->call($this, $data);

		});

		return $this;

	}

	public function quick(array $propertiesAllowed, array $callbacks = [], array $validate = ['canUpdate']): ModulePage {

		$this->post('/@module/'.str_replace('\\', '/', $this->module).'/quick', function($data) use ($propertiesAllowed, $callbacks) {

			$property = POST('property');

			if(in_array($property, $propertiesAllowed) === FALSE) {
				throw new NotAllowedAction('Property '.$this->module.'::'.$property.' not allowed for quick update');
			}

			$data->e = $this->element->call($this, $data);

			if($data->e === NULL) {

				$id = POST('id', '?int');
				$data->e = ($this->module.'Lib')::getById($id);

				if($data->e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$data->e->setQuick(TRUE);

			$this->applyElement->call($this, $data, $data->e);

			if(isset($callbacks[$property])) {
				$callbacks[$property]($data);
			}

			throw new JsonAction([
				'field' => (new \util\FormUi())->quick($data->e, $property)
			]);

		});

		$this->post('/@module/'.str_replace('\\', '/', $this->module).'/doQuick', function($data) use ($propertiesAllowed, $validate) {

			$property = POST('property');

			if(in_array($property, $propertiesAllowed) === FALSE) {
				throw new NotAllowedAction('Property '.$this->module.'::'.$property.' not allowed for quick update');
			}

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = POST('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);

			$fw = new \FailWatch();

			$e->build([$property], $_POST, for: 'update');

			$fw->validate();

			($this->module.'Lib')::update($e, [$property]);

			$fw->validate();

			throw new \ReloadLayerAction();

		});

		return $this;

	}

	public function doUpdateProperties(string $page, array|Closure $properties, \Closure $action, array $validate = ['canUpdate']): ModulePage {

		$this->post($page, function($data) use ($properties, $action, $validate) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = POST('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);

			$fw = new \FailWatch();

			if($properties instanceof Closure) {
				$properties = $properties->call($this, $e);
			}

			$e->build($properties, $_POST, for: 'update');

			$fw->validate();

			($this->module.'Lib')::update($e, $properties);

			$fw->validate();

			$data->e = $e;
			$action->call($this, $data);

		});

		return $this;

	}

	public function write(string $page, \Closure $action, array $validate = ['canWrite']): ModulePage {

		$this->post($page, function($data) use ($action, $validate) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = POST('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);
			$data->e = $e;

			$action->call($this, $data, $e);

		});

		return $this;

	}

	public function read(string|array $pageList, \Closure $action, string $method = 'get', array $validate = ['canRead'], ?Closure $onEmpty = NULL): ModulePage {

		$this->match([$method], $pageList, function($data) use ($action, $validate, $onEmpty) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = INPUT('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					if($onEmpty) {
						$onEmpty($data);
					} else {
						throw new \NotExistsAction($this->module.' #'.$id);
					}
				}

			}

			$e->validate(...$validate);

			$this->applyElement->call($this, $data, $e);
			$data->e = $e;

			$action->call($this, $data);

		});

		return $this;

	}

	public function doDelete(\Closure $action, string $page = 'doDelete'): ModulePage {

		$this->post($page, function($data) use ($action) {

			$e = $this->element->call($this, $data);

			if($e === NULL) {

				$id = POST('id', '?int');
				$e = ($this->module.'Lib')::getById($id);

				if($e->empty()) {
					throw new \NotExistsAction($this->module.' #'.$id);
				}

			}

			$e->validate('canDelete');

			$this->applyElement->call($this, $data, $e);

			$fw = new \FailWatch();

			($this->module.'Lib')::delete($e);

			$fw->validate();

			$data->e = $e;
			$action->call($this, $data);

		});

		return $this;

	}

}

abstract class ModuleCrud {

	/**
	 * Returns an element by its ID
	 */
	// abstract public static function getById(mixed $id, array $properties = []): \Element;

	public static function getPropertiesCreate(): array|Closure {
		return [];
	}

	/**
	 * Get a new element for future creation
	 */
	// abstract public static function getCreateElement(): Element;

	/**
	 * Create a new element
	 */
	// abstract public static function create(Element $e): void;

	public static function getPropertiesUpdate(): array|Closure {
		return [];
	}

	/**
	 * Update an existing collection
	 */
	// public static function updateCollection(\Collection $c, Element $e, array $properties): void {
	// 	throw new Exception('Not implemented yet');
	// }

	/**
	 * Update an existing element
	 */
	// abstract public function update(Element $e, array $properties = []): void;

	/**
	 * Delete an existing element
	 */
	// abstract public function delete(Element $e): void;

}
?>
