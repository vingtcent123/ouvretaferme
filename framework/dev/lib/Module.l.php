<?php
namespace dev;

/**
 * Generate module files from DIA diagram
 */
class ModuleLib {

	protected string $package;

	protected array $elements = [];
	protected array $generalizations = [];

	private array $buildDefaults = [];

	/**
	 * Get selected classes
	 *
	 * @return array
	 */
	public function getClasses() {
		return array_keys($this->elements);
	}

	/**
	 * Build modules
	 *
	 * @param string $elementName
	 */
	public function buildModule(string $elementName): void {

		if(isset($this->elements[$elementName]) === FALSE) {
			throw new \Exception("Class '".$elementName."' does not exist");
		}

		list($package) = explode('\\', $elementName);
		$element = $this->elements[$elementName];

		$content = [];

		$content[] = '<?php';
		$content[] = 'namespace '.$package.';';
		$content[] = '';
		$content = array_merge($content, $this->buildAbstractElement($package, $element));
		$content[] = '';
		$content[] = '';

		if($this->hasGeneralization($package, $element['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $element['name']).'Model ';
		} else {
			$extends = 'extends \\ModuleModel ';
		}

		if($element['meta']['ABSTRACT']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		if($package === lcfirst($element['name'])) {
			$table = $package;
		} else {
			$table = $package.$element['name'];
		}

		$content[] = $abstract.'class '.$element['name'].'Model '.$extends.'{';

		$content[] = '';
		$content[] = '	protected string $module = \''.$package.'\\'.$element['name'].'\';';
		$content[] = '	protected string $package = \''.$package.'\';';
		$content[] = '	protected string $table = \''.$table.'\';';
		$content = array_merge($content, $this->createSplit($element, $package));
		$content = array_merge($content, $this->createCache($element));
		$content = array_merge($content, $this->createCharset($element));
		$content = array_merge($content, $this->createStorage($element));
		$content[] = '';
		$content[] = '	public function __construct() {';
		$content[] = '';
		$content[] = '		parent::__construct();';
		$content[] = '';
		$content = array_merge($content, $this->createProperties($element));
		$content = array_merge($content, $this->createIndexes($element));
		$content = array_merge($content, $this->createSpatials($element));
		$content = array_merge($content, $this->createUniques($element));
		$content = array_merge($content, $this->createSearchs($element));
		$content[] = '	}';
		$content[] = '';
		$content = array_merge($content, $this->createDefaults());

		if($element['meta']['SPLIT']) {
			$content = array_merge($content, $this->createSplitSequence($package, $element, $element['meta']['SPLIT']));
		} else if($element['meta']['SPLITLIST']) {
			$content = array_merge($content, $this->createSplitList());
		}

		if($element['meta']['ENCODE']) {
			$content = array_merge($content, $this->createEncodeOrDecode('encode', $element['meta']['ENCODE']));
		}

		if($element['meta']['DECODE']) {
			$content = array_merge($content, $this->createEncodeOrDecode('decode', $element['meta']['DECODE']));
		}

		$content = array_merge($content, $this->createFunctions($element));

		$content[] = '';
		$content[] = '}';
		$content[] = '';
		$content[] = '';

		$content = array_merge($content, $this->buildLib($element));
		$content[] = '';
		$content[] = '';
		$content = array_merge($content, $this->buildPage($package, $element));

		$content[] = '?>';

		$fileContent = implode("\n", $content);
		$file = $this->getDirectory($package, 'module').'/'.$element['name'].'.m.php';

		file_put_contents($file, $fileContent);

		$this->buildElement($package, $element);

	}

	protected function buildAbstractElement(string $package, array $element): array {

		if($this->hasGeneralization($package, $element['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $element['name']).' ';
		} else {
			$extends = 'extends \\Element ';
		}

		$content = [];

		$content[] = 'abstract class '.$element['name'].'Element '.$extends.'{';
		$content[] = '';
		$content[] = '	use \FilterElement;';
		$content[] = '';

		if($element['meta']['ABSTRACT'] === FALSE) {
			$content[] = '	private static ?'.$element['name'].'Model $model = NULL;';
			$content[] = '';
		}

		$content = array_merge($content, $this->createConstants($element));

		if($element['meta']['ABSTRACT'] === FALSE) {

			$content[] = '	public static function getSelection(): array {';
			$content[] = '		return '.$element['name'].'::model()->getProperties();';
			$content[] = '	}';
			$content[] = '';

			$content[] = '	public static function model(): '.$element['name'].'Model {';
			$content[] = '		if(self::$model === NULL) {';
			$content[] = '			self::$model = new '.$element['name'].'Model();';
			$content[] = '		}';
			$content[] = '		return self::$model;';
			$content[] = '	}';
			$content[] = '';

			$content[] = '	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {';
			$content[] = '		return \Fail::log(\''.$element['name'].'::\'.$failName, $arguments, $wrapper);';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '}';

		return $content;

	}

	public function buildElement(string $package, array $element): void {

		$file = $this->getDirectory($package, 'module').'/'.$element['name'].'.e.php';

		if(is_file($file)) {
			return;
		}

		if($element['meta']['ABSTRACT']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		$content = [];

		$content[] = '<?php';
		$content[] = 'namespace '.$package.';';
		$content[] = '';
		$content[] = $abstract.'class '.$element['name'].' extends '.$element['name'].'Element {';
		$content[] = '';
		$content[] = '}';
		$content[] = '?>';

		$fileContent = implode("\n", $content);

		file_put_contents($file, $fileContent);

	}

	protected function buildLib(array $element): array {

		$content = [];

		$content[] = 'abstract class '.$element['name'].'Crud extends \ModuleCrud {';
		$content[] = '';
		$content[] = '	public static function getById(mixed $id, array $properties = []): '.$element['name'].' {';
		$content[] = '';
		$content[] = '		$e = new '.$element['name'].'();';
		$content[] = '';
		$content[] = '		if(empty($id)) {';
		$content[] = '			'.$element['name'].'::model()->reset();';
		$content[] = '			return $e;';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if($properties === []) {';
		$content[] = '			$properties = '.$element['name'].'::getSelection();';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if('.$element['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->whereId($id)';
		$content[] = '			->get($e) === FALSE) {';
		$content[] = '				$e->setGhost($id);';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		return $e;';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function getByIds(mixed $ids, array $properties = [], mixed $sort = NULL, mixed $index = NULL): \Collection {';
		$content[] = '';
		$content[] = '		if(empty($ids)) {';
		$content[] = '			return new \Collection();';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if($properties === []) {';
		$content[] = '			$properties = '.$element['name'].'::getSelection();';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if($sort !== NULL) {';
		$content[] = '			'.$element['name'].'::model()->sort($sort);';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		return '.$element['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->whereId(\'IN\', $ids)';
		$content[] = '			->getCollection(NULL, NULL, $index);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';

		if($element['meta']['FQN']) {

			$content[] = '	public static function getByFqn(string $fqn, array $properties = []): '.$element['name'].' {';
			$content[] = '';
			$content[] = '		$e = new '.$element['name'].'();';
			$content[] = '';
			$content[] = '		if(empty($fqn)) {';
			$content[] = '			'.$element['name'].'::model()->reset();';
			$content[] = '			return $e;';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if($properties === []) {';
			$content[] = '			$properties = '.$element['name'].'::getSelection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if('.$element['name'].'::model()';
			$content[] = '			->select($properties)';
			$content[] = '			->whereFqn($fqn)';
			$content[] = '			->get($e) === FALSE) {';
			$content[] = '				$e->setGhost($fqn);';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		return $e;';
			$content[] = '';
			$content[] = '	}';
			$content[] = '';

			$content[] = '	public static function getByFqns(array $fqns, array $properties = []): \Collection {';
			$content[] = '';
			$content[] = '		if(empty($fqns)) {';
			$content[] = '			'.$element['name'].'::model()->reset();';
			$content[] = '			return new \Collection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if($properties === []) {';
			$content[] = '			$properties = '.$element['name'].'::getSelection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		return '.$element['name'].'::model()';
			$content[] = '			->select($properties)';
			$content[] = '			->whereFqn(\'IN\', $fqns)';
			$content[] = '			->getCollection(NULL, NULL, \'fqn\');';
			$content[] = '';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '	public static function getCreateElement(): '.$element['name'].' {';
		$content[] = '';
		$content[] = '		return new '.$element['name'].'([\'id\' => NULL]);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function create('.$element['name'].' $e): void {';
		$content[] = '';
		$content[] = '		'.$element['name'].'::model()->insert($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function update('.$element['name'].' $e, array $properties): void {';
		$content[] = '';
		$content[] = '		$e->expects([\'id\']);';
		$content[] = '';
		$content[] = '		'.$element['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->update($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function updateCollection(\Collection $c, '.$element['name'].' $e, array $properties): void {';
		$content[] = '';
		$content[] = '		'.$element['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->whereId(\'IN\', $c)';
		$content[] = '			->update($e->extracts($properties));';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function delete('.$element['name'].' $e): void {';
		$content[] = '';
		$content[] = '		$e->expects([\'id\']);';
		$content[] = '';
		$content[] = '		'.$element['name'].'::model()->delete($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '}';

		return $content;

	}

	protected function buildPage(string $package, array $element): array {

		if($this->hasGeneralization($package, $element['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $element['name']).'Page ';
		} else {
			$extends = 'extends \\ModulePage ';
		}

		if($element['meta']['ABSTRACT']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		$content = [];

		$content[] = $abstract.'class '.$element['name'].'Page '.$extends.'{';
		$content[] = '';
		$content[] = '	protected string $module = \''.$package.'\\'.$element['name'].'\';';
		$content[] = '';

		if($element['meta']['ABSTRACT'] === FALSE) {

			$content[] = '	public function __construct(';
			$content[] = '	   ?\Closure $start = NULL,';
			$content[] = '	   \Closure|array|null $propertiesCreate = NULL,';
			$content[] = '	   \Closure|array|null $propertiesUpdate = NULL';
			$content[] = '	) {';
			$content[] = '		parent::__construct(';
			$content[] = '		   $start,';
			$content[] = '		   $propertiesCreate ?? '.$element['name'].'Lib::getPropertiesCreate(),';
			$content[] = '		   $propertiesUpdate ?? '.$element['name'].'Lib::getPropertiesUpdate()';
			$content[] = '		);';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '}';

		return $content;

	}

	protected function createSplitSequence(string $package, array $element, array $split): array {

		$content = [];

		if($split === 'NULL') {
			return $content;
		}

		[$property, $number] = $split;

		if($property === '') {

			$property = 'data';
			$type = NULL;

		} else if($property === 'id') {
			$max = Filter::MAX_INT32;
			$split[2] = 'floor(($id - 1) / floor('.$max.' / $number))';
			$type = 'int';
		} else {

			// The property exists in the element
			if(isset($element['list'][$property])) {
				$type = $element['list'][$property]['type'];
			}
			// Check if the property is in parent classes
			else {

				if($this->hasGeneralization($package, $element['name'])) {

					// TODO !
					$parentClassName = $this->getGeneralization($package, $element['name']).'Model';

					// Parent class may be abstract
					// Create a new temporary class
					$tmpClassName = '_'.$parentClassName;

					if(class_exists($tmpClassName) === FALSE) {
						eval('class '.$tmpClassName.' extends '.$parentClassName.' { }');
					}

					$mParent = new $tmpClassName();
					$propertyType = $mParent->getPropertyType($property);

					if($mParent->getPropertyType($property) === 'element') {
						$type = $mParent->getPropertyToModule($property);
					} else {
						$type = $propertyType;
					}

				} else {
					trigger_error("Can not split class '".$element['name']."' on property '".$property."'", E_USER_ERROR);
				}

			}

			if(strpos($type, '(') !== FALSE) {
				$type = substr($type, 0, strpos($type, '('));
			}

		}

		if(count($split) < 3) {
			if(
				in_array($type, [
					'int', 'int8', 'int16', 'int24', 'int32', 'int64',
					'float', 'float32', 'float64',
					'element8', 'element16', 'element24', 'element32', 'element64'
				]) or
				strpos($type, '\\') !== FALSE
			) {
				return $content;
			} else {
				$value = 'crc32($value) % '.$number;
			}
		} else {
			$value = $split[2];
		}

		$content[] = '	public function split($'.$property.'): int {';

		// This is an element
		if(ucfirst($type) === $type or in_array($type, ['element8', 'element16', 'element24', 'element32', 'element64'])) {
			$content[] = '		$'.$property.'->expects([\'id\']);';
			$value = str_replace('$value', '$'.$property.'[\'id\']', $value);
		} else {
			$value = str_replace('$value', '$'.$property.'', $value);
		}


		$value = str_replace('$number', '$this->split', $value);

		$content[] = '		return '.$value.';';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createSplitList(): array {

		$content = [];

		$content[] = '	public function split($value): int {';
		$content[] = '		throw new Exception("Can not use split() on lists");';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createCache(array $element): array {

		$content = [];

		$cache = $element['meta']['CACHE'];

		if($cache) {

			$content[] = '';
			$content[] = '	protected $cache = \''.$cache.'\';';

		}

		return $content;

	}

	protected function createStorage(array $element): array {

		$content = [];

		$storage = $element['meta']['STORAGE'];

		if($storage) {
			$content[] = '	protected string $storage = \''.$storage.'\';';
		}

		return $content;


	}

	protected function createCharset(array $element): array {

		$content = [];

		$charset = $element['meta']['CHARSET'];

		if($charset) {
			$content[] = '	protected string $charset = \''.$charset.'\';';
		}

		return $content;

	}

	protected function createEncodeOrDecode(string $type, array $entries): array {

		$content = [];

		$content[] = '	public function '.$type.'(string $property, $value) {';
		$content[] = '';
		$content[] = '		switch($property) {';
		$content[] = '';

		foreach($entries as $property => $value) {

			$content[] = '			case \''.$property.'\' :';

			if(is_array($value)) {
				foreach($value as $codeLine) {
					$content[] = '				'.$codeLine;
				}
			} else {
				$content[] = '				return '.$value.';';
			}

			$content[] = '';

		}

		$content[] = '			default :';
		$content[] = '				return parent::'.$type.'($property, $value);';
		$content[] = '';
		$content[] = '		}';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createSplit(array $element, string $package): array {

		$splitSequence = $element['meta']['SPLIT'];

		if($splitSequence) {

			[$property, $number] = $splitSequence;

			if($property === NULL) {
				return ["\n".'	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;'];
			} else {

				if(preg_match("/SETTING\((.*)\)/si", $number, $result)) {

					$setting = $result[1];
					if(str_contains($setting, '\\') === FALSE) {
						$setting = $element['package'].'\\'.$setting;
					}

					$number = \Setting::get($setting);
				} else {
					$number = (int)$number;
				}

				if($number > 1) {

					return ["\n".'	protected ?string $splitMode = \'sequence\';
	protected ?int $split = '.$number.';
	protected ?string $splitOn = '.$property.';'];

				} else {

					return ["\n".'	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;'];

				}

			}

		}

		$splitList = $element['meta']['SPLITLIST'];

		if($splitList) {

			$content = "\n".'	protected ?string $splitMode = \'list\';';
			$content .= "\n".'	protected ?int $split = [\''.implode('\', \'', $splitList).'\'];';

			$splitProperties = $element['meta']['SPLITPROPERTIES'];

			if($splitProperties) {
				$content .= "\n".'	protected ?array $splitProperties = [\''.implode('\', \'', $splitProperties).'\'];';
			}

			return [$content];

		}


		return [];


	}

	protected function createProperties(array &$element): array {

		$content = [];

		if(empty($element['list'])) {
			return $content;
		}

		$this->buildDefaults = [];

		$properties = [];
		$propertiesList = [];
		$propertiesToModule = [];

		foreach($element['list'] as $name => $property) {

			// Check type for ID field
			if(
				$name === 'id' and
				!str_starts_with($property['type'], 'serial') and !str_starts_with($property['type'], 'int')
			) {
				throw new \Exception("Property 'id' must be a serial or int in '".$element['name']."'");
			}

			// Check use for serial types
			if(
				str_starts_with($property['type'], 'serial') and
				$name !== 'id'
			) {
				throw new \Exception("Type '".$property['type']."' can only be used for 'id' property in '".$element['name']."'");
			}

			// Save element property
			if(str_starts_with($property['type'], 'element')) {
				$propertiesToModule[$name] = strpos($property['params'], 'element') === 0 ? NULL : $property['params'];
			}

			$mask = $this->getTypeFilter($element, $property);

			if($property['nullable']) {
				$mask .= ', \'null\' => TRUE';
			}

			if(in_array([$name], $element['meta']['UNIQUE'])) {
				$mask .= ', \'unique\' => TRUE';
			}

			$cast = \ModuleModel::getCast($name, $property['type']);
			if($cast === NULL) {
				$cast = 'NULL';
			} else {
				$cast = '\''.$cast.'\'';
			}

			$mask .= ', \'cast\' => '.$cast.'';

			// Save default value
			if($property['default'] !== NULL) {

				$default = $this->getDefault($element, $property);

				if($default !== NULL) {
					$this->buildDefaults[$name] = $default;
				}

			}

			$properties[$name] = $mask;
			$propertiesList[] = $name;

		}

		if($properties) {
			$content[] = '		$this->properties = array_merge($this->properties, [';
			foreach($properties as $name => $mask) {
				$content[] = '			\''.$name.'\' => ['.$mask.'],';
			}
			$content[] = '		]);';
			$content[] = '';
		}

		if($propertiesList) {
			$content[] = '		$this->propertiesList = array_merge($this->propertiesList, [';
			$content[] = '			\''.implode('\', \'', $propertiesList).'\'';
			$content[] = '		]);';
			$content[] = '';
		}

		if($propertiesToModule) {

			$content[] = '		$this->propertiesToModule += [';
			foreach($propertiesToModule as $name => $type) {
				$content[] = '			\''.$name.'\' => '.($type ? '\''.$type.'\'' : 'NULL').',';
			}
			$content[] = '		];';
			$content[] = '';

		}

		return $content;


	}

	protected function createFunctions(array $element): array {

		$content = [];

		if(empty($element['list'])) {
			return $content;
		}

		$content[] = '	public function select(...$fields): '.$element['name'].'Model {';
		$content[] = '		return parent::select(...$fields);';
		$content[] = '	}';
		$content[] = '';

		$content[] = '	public function where(...$data): '.$element['name'].'Model {';
		$content[] = '		return parent::where(...$data);';
		$content[] = '	}';
		$content[] = '';

		foreach($element['list'] as $name => $value) {

			$content[] = '	public function where'.ucfirst($name).'(...$data): '.$element['name'].'Model {';
			$content[] = '		return $this->where(\''.$name.'\', ...$data);';
			$content[] = '	}';
			$content[] = '';

		}

		return $content;


	}

	protected function createSpatials(array $element): array {
		return $this->createConstraint($element['meta']['SPATIAL'], 'spatial');
	}

	protected function createIndexes(array $element): array {
		return $this->createConstraint($element['meta']['INDEX'], 'index');
	}

	protected function createUniques(array $element): array {
		return $this->createConstraint($element['meta']['UNIQUE'], 'unique');
	}

	protected function createSearchs(array $element): array {
		return $this->createConstraint($element['meta']['SEARCH'], 'search');
	}

	protected function createConstraint(array $constraints, string $name): array {

		if($constraints === []) {
			return [];
		}

		$content = [];
		$content[] = '		$this->'.$name.'Constraints = array_merge($this->'.$name.'Constraints, [';

		$position = 0;

		foreach($constraints as $constraint) {

			$comma = ($position === count($constraints) - 1) ? '' : ',';

			$content[] = '			[\''.implode('\', \'', $constraint).'\']'.$comma;

			$position++;

		}

		$content[] = '		]);';
		$content[] = '';

		return $content;

	}

	protected function createConstants(array $element): array {

		$content = [];

		if(isset($element['list']) === FALSE) {
			return $content;
		}

		$constantsUsed = [];

		foreach($element['list'] as $parse) {

			$position = 1;

			if($parse['type'] === 'enum') {

				if(is_string($parse['params'])) {
					continue;
				}

				$what = 'enum';

			} else if($parse['type'] === 'set') {
				$what = 'set';
			} else {
				continue;
			}

			$hasConstants = FALSE;

			if($parse['params'] === NULL) {
				throw new \Exception('Missing values');
			}

			foreach($parse['params'] as $constant) {

				if(str_contains($constant, '::') === FALSE) {

					if(in_array($constant, $constantsUsed) === TRUE) {
						continue;
					}

					$hasConstants = TRUE;

					if($what === 'enum') {
						$value = '\''.strtolower(str_replace('_', '-', $constant)).'\'';
					} else if($what === 'set') {
						$value = $position;
						$position *= 2;
					}

					if($constant !== '?') {
						$content[] = '	const '.$constant.' = '.$value.';';
					}

					$constantsUsed[] = $constant;

				}

			}

			if($hasConstants) {
				$content[] = '';
			}

		}

		foreach($element['meta']['CONSTANTS'] as [$name, $value]) {
			$content[] = '	const '.$name.' = \''.$value.'\';';
		}

		return $content;

	}

	protected function createDefaults(): array {

		$content = [];

		if($this->buildDefaults) {

			$content[] = '	public function getDefaultValue(string $property) {';
			$content[] = '';
			$content[] = '		switch($property) {';
			$content[] = '';

			foreach($this->buildDefaults as $name => $default) {

				$php = $this->getDefaultValue($default);

				$content[] = '			case \''.$name.'\' :';
				$content[] = '				return '.$php.';';
				$content[] = '';

			}

			$content[] = '			default :';
			$content[] = '				return parent::getDefaultValue($property);';
			$content[] = '';

			$content[] = '		}';
			$content[] = '';

			$content[] = '	}';
			$content[] = '';

		}

		return $content;

	}

	protected function getDefaultValue(array $default): string {

		list($mode, $type, $value) = $default;

		switch($mode) {

			case 'php' :
				return $value;

			case 'sql' :
				return "new \Sql('".$value."')";

			case 'special' :

				switch($value) {

					case 'ip' :
						return "getIp()";

					case 'host' :
						return "gethostbyaddr(getIp())";

					case 'sid' :
						return "session_id()";

					case 'user' :
						return "\user\ConnectionLib::getOnline()";

					case 'now' :

						switch($type) {

							case 'date' :
								return "new \Sql('CURDATE()')";

							case 'time' :
								return "new \Sql('CURTIME()')";

							case 'datetime' :
								return "new \Sql('NOW()')";

							case 'week' :
								return 'currentWeek()';

							case 'month' :
								return "new \Sql('DATE_FORMAT(CURDATE(), \\'%Y-%m\\')')";

						}

				}

		}

		return 'NULL';

	}

	protected array $last = [];

	protected function getTypeFilter(array $element, array $property): string {

		$this->last = $property;

		$typeFilter = '\''.$property['type'].'\'';

		switch($property['type']) {

			case 'element8' :
			case 'element16' :
			case 'element24' :
			case 'element32' :
			case 'element64' :
				if($property['params']) {
					$type = $typeFilter.', \''.$property['params'].'\'';
				} else {
					$type = $typeFilter;
				}
				$this->last['type'] = $property['params'];
				$this->last['params'] = NULL;
				break;

			case 'enum' :
			case 'set' :

				if(is_array($property['params'])) {

					$this->last['values'] = $property['params'];
					$this->last['params'] = NULL;

					$list = [];

					foreach($property['params'] as $param) {
						if(str_contains($param, '::') === FALSE) {
							if($param !== '?') {
								$list[] = '\\'.$element['class'].'::'.$param;
							}
						} else {
							$list[] = $param;
						}
					}

					$property['params'] = '['.implode(', ', $list).']';

				}

				$type = $typeFilter.', '.$property['params'];
				break;

			case 'textFixed' :
			case 'text8' :
			case 'text16' :
			case 'text24' :
			case 'text32' :


				if($property['params'] === NULL) {
					$type = $typeFilter;
				} else {

					$countParams = count($property['params']);

					switch($countParams) {
						case 1 : // Only charset
							$type = $typeFilter.', \'charset\' => \''.(string)$property['params'][0].'\'';
							break;

						case 2 : // min and max values only
							[$min, $max] = $property['params'];

							$type = $typeFilter;
							$type .= $this->buildRange(['min' => $min, 'max' => $max], $element);
							break;

						default : // Nothing
							$type = $typeFilter;
							break;
					}

				}

				break;

			case 'editor8' :
			case 'editor16' :
			case 'editor24' :
			case 'editor32' :
			case 'binaryFixed' :
			case 'binary8' :
			case 'binary16' :
			case 'binary24' :
			case 'binary32' :
			case 'int8' :
			case 'int16' :
			case 'int24' :
			case 'int32' :
			case 'int64' :
			case 'float32' :
			case 'float64' :
				if($property['params'] !== NULL and count($property['params']) === 2) {
					[$min, $max] = $property['params'];
					$type = $typeFilter;
					$type .= $this->buildRange(['min' => $min, 'max' => $max], $element);
				} else {
					$type = $typeFilter;
				}
				break;

			case 'decimal' :
				if(count($property['params']) === 2) {
					[$digits, $decimal] = $property['params'];
					$values = ['digits' => $digits, 'decimal' => $decimal];
				} else if(count($property['params']) === 4) {
					[$digits, $decimal, $min, $max] = $property['params'];
					$values = ['digits' => $digits, 'decimal' => $decimal, 'min' => $min, 'max' => $max];
				} else {
					throw new \Exception('Bad use of decimal type');
				}
				$type = $typeFilter;
				$type .= $this->buildRange($values, $element);
				break;

			case 'collection' :
				list($typeList, $size) = $property['params'];
				$type = $typeFilter.', \''.$typeList.'\', '.$size;
				break;

			case 'date' :
			case 'datetime' :
			case 'week' :
			case 'month' :
				if($property['params'] !== NULL and count($property['params']) === 2) {
					[$min, $max] = $property['params'];
					$type = $typeFilter;
					$type .= $this->buildRange(['min' => $min, 'max' => $max], $element);
				} else {
					$type = $typeFilter;
				}
				break;

			default :
				$type = $typeFilter;
				break;

		}

		if($property['charset'] !== NULL) {
			$type .= ', \'charset\' => \''.$property['charset'].'\'';
		}

		if($property['collate'] !== NULL) {
			$type .= ', \'collate\' => \''.$property['collate'].'\'';
		}

		return $type;

	}

	protected function buildRange(array $values, array $element): string {

		$range = '';

		foreach($values as $type => $value) {

			if($value === NULL) {
				$value = 'NULL';
			} else if(preg_match("/SETTING\((.*)\)/si", $value, $result)) {

				if(str_contains($result[1], '\\') === FALSE) {
					$setting = $element['package'].'\\'.$result[1];
				} else {
					$setting = $result[1];
				}

				$value = '\Setting::get(\''.$setting.'\')';

			} else if(preg_match("/PHP\((.*)\)/si", $value, $result)) {
				$value = $result[1];
			} else if(is_numeric($value)) {
				// Numeric value
			} else {
				$value = '\''.addcslashes($value, '\'').'\'';
			}

			$range .= ', \''.$type.'\' => '.$value;

		}

		return $range;

	}

	protected function getDefault(array $element, array $property): array {

		if(preg_match("/SPECIAL\((.*)\)/si", $property['default'], $result)) {
			
			if($result[1] === 'empty') {

				switch($property['type']) {

					case 'int8' :
					case 'int16' :
					case 'int24' :
					case 'int32' :
					case 'int64' :
						return ['php', $property['type'], "0"];
					case 'set' :
						return ['php', $property['type'], "new \Set(0)"];

					case 'decimal' :
					case 'float32' :
					case 'float64' :
						return ['php', $property['type'], "0.0"];

					case 'bool' :
						return ['php', $property['type'], "FALSE"];

					case 'date' :
						return ['php', $property['type'], "'0000-00-00'"];

					case 'datetime' :
						return ['php', $property['type'], "'0000-00-00 00:00:00'"];

					case 'week' :
						return ['php', $property['type'], "'0000-W00'"];

					case 'month' :
						return ['php', $property['type'], "'0000-00'"];

					case 'json':
					case 'polygon':
						return ['php', $property['type'], "[]"];

					case 'point':
						return ['php', $property['type'], "[0, 0]"];

					default :
						return ['php', $property['type'], "''"];

				}

			} else if($result[1] === 'null') {

				return ['php', $property['type'], "NULL"];

			} else {
				return ['special', $property['type'], $result[1]];
			}
		} else if(preg_match("/ID\((.*)\)/si", $property['default'], $result)) {
			return ['php', $property['type'], '[\'id\' => \''.$result[1].'\']'];
		} else if(preg_match("/PHP\((.*)\)/si", $property['default'], $result)) {
			return ['php', $property['type'], $result[1]];
		} else if(preg_match("/SQL\((.*)\)/si", $property['default'], $result)) {
			return ['sql', $property['type'], $result[1]];
		} else {

			switch($property['type']) {

				case 'int8' :
				case 'int16' :
				case 'int24' :
				case 'int32' :
				case 'int64' :
					$default = strpos($property['default'], '::') !== FALSE ? $property['default'] : (int)$property['default'];
					break;

				case 'decimal' :
				case 'float32' :
				case 'float64' :
					$default = strpos($property['default'], '::') !== FALSE ? $property['default'] : (float)$property['default'];
					break;

				case 'point' :
				case 'polygon' :
					$default = strpos($property['default'], '::') !== FALSE ? $property['default'] : '['.implode(', ', preg_split('/\s*,\s*/si', $property['default'])).']';
					break;

				case 'textFixed' :
				case 'binaryFixed' :
				case 'binary8' :
				case 'binary16' :
				case 'binary24' :
				case 'binary32' :
				case 'text8' :
				case 'text16' :
				case 'text24' :
				case 'text32' :
				case 'editor8' :
				case 'editor16' :
				case 'editor24' :
				case 'editor32' :
				case 'pcre' :
				case 'email' :
				case 'url' :
				case 'pcre' :
				case 'fqn' :
					$default = "\"".addcslashes($property['default'], '\\"')."\"";
					break;

				case 'enum' :
					$default = $this->getConstant($property['default'], $element['name']);
					break;

				case 'set' :
					if($property['default'] === '*') {

						$defaultValues = [];
						foreach($property['params'] as $property['default']) {
							$defaultValues[] = $this->getConstant($property['default'], $element['name']);
						}

					} else if($property['default'] === '0') {
						$defaultValues = ['0'];
					} else {

						$defaultValues = preg_split("/\s*\|\s*/si", $property['default']);
						foreach($defaultValues as $key => $defaultValue) {
							$defaultValues[$key] = $this->getConstant($defaultValue, $element['name']);
						}

					}

					$default = 'new \Set('.implode(' | ', $defaultValues).')';
					break;

				case 'bool' :
					$default = ($property['default'] === 'TRUE' ? 'TRUE' : 'FALSE');
					break;

				case 'date' :
				case 'datetime' :
				case 'week' :
				case 'month' :
					$default = "\"".addcslashes($property['default'], '\\"')."\"";
					break;

				default :
					return ['php', $property['type'], $property['default']];

			}

			return ['php', $property['type'], $default];

		}

	}


	/**
	 * Build environment for the specified DIA file
	 *
	 */
	public function load() {

		foreach(\Package::getList() as $package => $app) {

			foreach(glob(\Package::getPath($package).'/*.yml') as $file) {

				$yml = yaml_parse_file($file);

				if($yml === FALSE) {
					throw new \Exception("File '".$file."' is not a XML file");
				}

				if($yml !== NULL) {
					$this->initGeneralization($package, $yml);
					$this->initClasses($package, $yml);
				}

			}

		}

		$this->initElements();

	}

	protected function initElements() {

		foreach($this->elements as $elementName => ['list' => $properties]) {

			foreach($properties as $propertyName => $property) {

				if(str_contains($property['type'], '\\')) {

					if(isset($this->elements[$property['type']]) === FALSE) {
						throw new \Exception('Could not find element '.$property['type'].' for property \''.$propertyName.'\' in class \''.$elementName.'\'');
					} else {

						// Search for ID in the given class
						$typeId = $this->getTypeFromClass($property['type'], 'id');

						if($typeId === NULL) {
							throw new \Exception('Missing ID field in class '.$property['type'].' for property \''.$elementName.'::'.$propertyName.'\'');
						}

						$this->elements[$elementName]['list'][$propertyName]['params'] = $property['type']; // Element name is a param
						$this->elements[$elementName]['list'][$propertyName]['type'] = str_replace(['serial', 'int'], ['element', 'element'], $typeId); // Type if elementX

					}

				}

			}

		}

	}

	protected function initGeneralization(string $package, array $yml): void {

		foreach($yml as $element => $lines) {

			foreach($lines as $key => $value) {

				if($key === 'EXTENDS') {
					$this->generalizations[$package.'\\'.$element] = $value;
				}

			}

		}

	}

	protected function initClasses(string $package, array $yml): void {

		foreach($yml as $elementName => $lines) {

			$this->elements[$package.'\\'.$elementName] = $this->initProperties($package, $elementName, $lines);

		}

	}

	protected function initProperties(string $package, string $elementName, array $lines): array {

		$properties = [
			'package' => $package,
			'name' => $elementName,
			'class' => $package.'\\'.$elementName,
			'meta' => [
				'FQN' => FALSE,
				'CACHE' => FALSE,
				'ABSTRACT' => FALSE,
				'CHARSET' => NULL,
				'STORAGE' => NULL,
				'CONNECTION' => NULL,
				'INDEX' => [],
				'UNIQUE' => [],
				'SPATIAL' => [],
				'SEARCH' => [],
				'SPLITLIST' => [],
				'SPLITPROPERTIES' => [],
				'ENCODE' => [],
				'DECODE' => [],
				'CONSTANTS' => [],
				'SPLIT' => NULL,
			],
			'list' => []
		];

		foreach($lines as $name => $value) {

			switch($name) {

				case 'EXTENDS' :
					break;

				case 'CACHE' :
				case 'ABSTRACT' :
					if(is_bool($value) === FALSE) {
						throw new \Exception('Expected bool for CACHE instruction');
					}
					$properties['meta'][$name] = $value;
					break;

				case 'CHARSET' :
				case 'STORAGE' :
				case 'CONNECTION' :
					if(is_string($value) === FALSE) {
						throw new \Exception('Expected string for '.$name.' instruction');
					}
					$properties['meta'][$name] = $value;
					break;

				case 'INDEX' :
				case 'UNIQUE' :
				case 'SPATIAL' :
				case 'SEARCH' :
				case 'SPLITLIST' :
				case 'SPLITPROPERTIES' :
					if(is_array($value) === FALSE) {
						throw new \Exception('Expected list [val1, val2, ...] for '.$name.' instruction');
					}
					$properties['meta'][$name] = $value;
					break;

				case 'CONSTANTS' :
				case 'ENCODE' :
				case 'DECODE' :
					if(is_array($value) === FALSE) {
						throw new \Exception('Expected object {key => value, ...} for '.$name.' instruction');
					}
					$properties['meta'][$name] = $value;
					break;

				case 'SPLIT' :
					if(is_array($value) === FALSE or count($value) !== 2) {
						throw new \Exception('Expected [property, number] for SPLIT instruction');
					}
					$properties['meta'][$name] = $value;
					break;

				default :

					$parse = $this->parseProperty($name, $value);

					if($parse['type'] === 'fqn') {

						if($name !== 'fqn') {
							throw new \Exception('Type \'fqn\' must be \'fqn\' property name');
						}

						$properties['meta']['UNIQUE'] ??= [];
						$properties['meta']['UNIQUE'][] = [$name];
						$properties['meta']['FQN'] = TRUE;

					}

					$properties['list'][$name] = $parse;

					// Special cases
					$encode = NULL;
					$decode = NULL;

					$options = [];

					if($parse['compress']) {
						$options[] = 'compress';
					}

					if(str_starts_with($parse['type'], 'json')) {
						$options[] = 'json';
					}

					if($options) {

						$encode = '$value';
						$decode = '$value';

						foreach($options as $option) {

							switch($option) {
								case 'compress' :
									$encode = 'gzcompress('.$encode.', 9)';
									break;
								case 'json' :
									$encode = 'json_encode('.$encode.', JSON_UNESCAPED_UNICODE)';
									break;
							}

						}

						foreach(array_reverse($options) as $option) {

							switch($option) {
								case 'compress' :
									$decode = 'gzuncompress('.$decode.')';
									break;
								case 'json' :
									$decode = 'json_decode('.$decode.', TRUE)';
									break;
							}

						}

						$encode = '$value === NULL ? NULL : '.$encode;
						$decode = '$value === NULL ? NULL : '.$decode;

					} else if($parse['type'] === 'ipv4') {
						$encode = '$value === NULL ? NULL : (int)first(unpack(\'l\', pack(\'l\', ip2long($value))))';
						$decode = '$value === NULL ? NULL : long2ip($value)';
					} else if($parse['type'] === 'password' or $parse['type'] === 'md5') {
						$encode = '$value === NULL ? NULL : hex2bin($value)';
						$decode = '$value === NULL ? NULL : bin2hex($value)';
					} else if($parse['type'] === 'enum') {
						$encode = '($value === NULL) ? NULL : (string)$value';
					} else if($parse['type'] === 'point') {
						$encode = '$value === NULL ? NULL : new \Sql($this->pdo()->api->getPoint($value))';
						$decode = '$value === NULL ? NULL : json_encode(json_decode($value, TRUE)[\'coordinates\'])';
					} else if($parse['type'] === 'polygon') {
						$encode = '$value === NULL ? NULL : new \Sql($this->pdo()->api->getPolygon($value))';
						$decode = '$value === NULL ? NULL : json_encode(json_decode($value, TRUE)[\'coordinates\'][0])';
					}

					if($encode !== NULL) {
						$properties['meta']['ENCODE'] ??= [];
						$properties['meta']['ENCODE'][$name] = $encode;
					}

					if($decode !== NULL) {
						$properties['meta']['DECODE'] ??= [];
						$properties['meta']['DECODE'][$name] = $decode;
					}
					break;

			}

		}

		return $properties;

	}

	protected function parseProperty(string $name, string $value): array {

		$parse = [
			'nullable' => str_starts_with($value, '?')
		];

		if($parse['nullable']) {
			$value = substr($value, 1);
		}

		if(preg_match("/^([a-z\\\\0-9]+)(\((.*)\))?( |$)/si", $value, $match) === 0) {
			throw new \Exception("Empty type detected for property '".$name."' (".$value.")");
		}

		if(preg_match('/(\s+@compress)( |$)/si', $value, $match) > 0) {
			$parse['compress'] = TRUE;
			$value = str_replace($match[1], '', $value);
		} else {
			$parse['compress'] = FALSE;
		}

		if(preg_match('/\s+@charset\((.*)\)/si', $value, $match) > 0) {
			$parse['charset'] = $match[1];
			$value = str_replace($match[0], '', $value);
		} else {
			$parse['charset'] = NULL;
		}

		if(preg_match('/\s+@collate\((.*)\)/si', $value, $match) > 0) {
			$parse['collate'] = $match[1];
			$value = str_replace($match[0], '', $value);
		} else {
			$parse['collate'] = NULL;
		}

		if(preg_match('/\s+\=\s+(.*)$/si', $value, $match) > 0) {
			$parse['default'] = $match[1];
			$value = str_replace($match[0], '', $value);
		} else {
			$parse['default'] = NULL;
		}

		if(preg_match("/^([a-z\\\\0-9]+)(\((.*)\))?( |$)/si", $value, $match) === 0) {
			throw new \Exception("Empty type detected for property '".$name."' (".$value.")");
		}

		$parse += [
			'type' => $match[1],
			'params' => $match[3]
		];

		if($parse['params'] !== '') {

			$convert = function(&$params) {

				if($params === NULL) {
					return;
				}

				$params = preg_split("/\s*,\s*/", $params);

				foreach($params as $key => $value) {
					if(strtolower($value) === 'null') {
						$params[$key] = NULL;
					}
				}

			};

			switch($parse['type']) {

				case 'textFixed' :
				case 'text8' :
				case 'text16' :
				case 'text24' :
				case 'text32' :
				case 'text' :
				case 'editor8' :
				case 'editor16' :
				case 'editor24' :
				case 'editor32' :
				case 'editor' :
				case 'binary8' :
				case 'binary16' :
				case 'binary24' :
				case 'binary32' :
				case 'binary' :
				case 'json' :
				case 'int8' :
				case 'int16' :
				case 'int24' :
				case 'int32' :
				case 'int64' :
				case 'int' :
				case 'decimal' :
				case 'float32' :
				case 'float64' :
				case 'float' :
				case 'binaryFixed' :
				case 'date' :
				case 'datetime' :
				case 'week' :
				case 'month' :
				case 'set' :
				case 'point' :
				case 'polygon' :

					$convert($parse['params']);

					break;

				case 'enum' :

					if(preg_match("/PHP\((.*)\)/si", $parse['params'], $match) > 0) {
						$parse['params'] = $match[1];
					} else {
						$convert($parse['params']);
					}

					break;

				case 'collection' :

					if(preg_match("/^([a-z\\\\0-9]+)(\((.*)\))?,\s*([0-9]+)$/si", $parse['params'], $match) === 0) {
						throw new \Exception("Invalid syntax for property '".$name."' (".$value.")");
					}

					$possibleListTypes = ['serial8', 'serial16', 'serial32', 'serial64'];

					if(in_array($match[1], $possibleListTypes) === FALSE) {
						throw new \Exception("Invalid type for property '".$name."' (".$value."), expected ".implode(', ', $possibleListTypes)."");
					}

					$parse['params'] = [
						$match[1], // type such as int32, user\User
						$match[4], // list size
					];

					$parse['params'][] = NULL; // min
					$parse['params'][] = NULL; // max

					break;

			}

			// Transform generic types (float, int, serial...)
			switch($parse['type']) {

				case 'text' :
				case 'editor' :
				case 'binary' :
					$parse['type'] = $parse['type'].'24';
					break;

				case 'float' :
					$parse['type'] = $parse['type'].'32';
					break;

				case 'int' :
				case 'serial' :

					if(is_array($parse['params']) === FALSE or count($parse['params']) !== 2) {
						$parse['type'] = $parse['type'].'32';
					} else {

						[$min, $max] = $parse['params'];

						$min = is_numeric($min) ? (int)$min : NULL;
						$max = is_numeric($max) ? (int)$max : NULL;

						if($min < 0 or $min === NULL) {
							if($min === NULL) {
								$parse['type'] = $parse['type'].'32';
							} else if($min >= -128 and $max <= 127) {
								$parse['type'] = $parse['type'].'8';
							} else if($min >= -32768 and $max <= 32767) {
								$parse['type'] = $parse['type'].'16';
							} else {
								$parse['type'] = $parse['type'].'32';
							}
						} else { // $min >= 0
							if($max === NULL) {
								$parse['type'] = $parse['type'].'32';
							} else if($max <= 255) {
								$parse['type'] = $parse['type'].'8';
							} else if($max <= 65535) {
								$parse['type'] = $parse['type'].'16';
							} else {
								$parse['type'] = $parse['type'].'32';
							}
						}

					}

					break;

			}

		} else {
			$parse['params'] = NULL;
		}
		
		return $parse;

	}

	protected function hasGeneralization(string $package, string $element): bool {
		return isset($this->generalizations[$package.'\\'.$element]);
	}

	protected function getGeneralization(string $package, string $element) {
		return $this->generalizations[$package.'\\'.$element];
	}

	protected function getTypeFromClass(string $elementName, string $propertyName) {

		$typeId = NULL;

		if(isset($this->elements[$elementName]['list'][$propertyName])) {
			$typeId = $this->elements[$elementName]['list'][$propertyName]['type'];
		} else {

			$elementGeneralization = $elementName;

			while(isset($this->generalizations[$elementGeneralization])) {

				$elementGeneralization = $this->generalizations[$elementGeneralization];

				if(isset($this->elements[$elementGeneralization]['list'][$propertyName])) {
					$typeId = $this->elements[$elementGeneralization]['list'][$propertyName]['type'];
					break;
				}

			}

		}

		return $typeId;

	}

	protected function getDirectory(string $package, string $type): string {

		$directory = \Package::getPath($package).'/'.$type;

		if(is_dir($directory) === FALSE) {
			mkdir($directory, 0755, TRUE);
		}

		return $directory;

	}

	protected function getConstant(string $value, string $elementName): string {

		if(strpos($value, '::') === FALSE) {
			return $elementName."::".$value;
		} else {
			return $value;
		}

	}

}
?>
