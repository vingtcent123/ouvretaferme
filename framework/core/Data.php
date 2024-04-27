<?php
/**
 * Groups of elements
 */
class Collection extends ArrayIterator {

	public int $depth = 1;

	private array $properties = [];

	/**
	 * Checks if the collection is not empty
	 *
	 * @return bool
	 */
	public function notEmpty(): bool {
		return ($this->count() > 0);
	}

	/**
	 * Checks if the collection is empty
	 *
	 * @return bool
	 */
	public function empty(): bool {
		return ($this->count() === 0);
	}

	/**
	 * Set the depth of the group
	 */
	public function setDepth(int $depth): Collection {
		$this->depth = $depth;
		return $this;
	}

	/**
	 * Returns the depth of the group
	 */
	public function getDepth(): int {
		return $this->depth;
	}

	/**
	 * Set a property to the collection
	 */
	public function setProperty(string $name, mixed $value): void {
		$this->properties[$name] = $value;
	}

	/**
	 * Returns a property of the collection
	 */
	public function getProperty(string $name): mixed {
		if(array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		} else {
			throw new Exception('Property \''.$name.'\' does not exist');
		}
	}

	public function slice(int $offset, ?int $length = NULL, bool $preserveKeys = FALSE) {

		$c = new Collection();
		$length ??= $this->count() - $offset;

		if($length <= 0 or $offset < 0) {
			return new Collection();
		}

		for($position = 0; $position < $length; $position++) {

			$this->seek($offset + $position);

			if($preserveKeys) {
				$c[$this->key()] = $this->current();
			} else {
				$c[] = $this->current();
			}

		}

		return $c;

	}

	public function __toString(): string {
		return $this->toString(4);
	}

	/**
	 * Convert element group as a string
	 *
	 */
	public function toString(?int $recursion = NULL): string {

		$output = 'Collection ('."\n";

		foreach($this as $key => $value) {

			if($value instanceof Element === FALSE and $value instanceof Collection === FALSE) {
				$output .= '   ['.$key.'] => '.gettype($value)."\n";
			} else {
				if($recursion > 0) {
					$string = trim($value->toString($recursion === NULL ? NULL : $recursion - 1));
				} else {
					$string = get_class($value).' [...]';
				}
				$output .= '   ['.$key.'] => '.str_replace("\n", "\n   ", $string)."\n";
			}

		}

		$output .= ')'."\n";

		return $output;

	}

	/**
	 * Push a new key
	 *
	 */
	public function push($keys, Element $e) {

		$keys = (array)$keys;

		if(count($keys) !== $this->depth) {
			throw new Exception('Number of $keys ('.count($keys).') should be equal to Collection depth ('.$this->depth.')');
		}

		$key = array_shift($keys);

		if($this->depth === 1) {

			if($key === NULL) {
				$this->append($e);
			} else {
				$this->offsetSet($key, $e);
			}

		} else {

			if($key === NULL) {
				throw new Exception('Key must not be NULL when Collection depth is greater than 1');
			}

			if($this->offsetExists($key) === FALSE) {
				$this->offsetSet($key, (new Collection())->setDepth($this->depth - 1));
			}

			$this[$key]->push($keys, $e);

		}

	}

	/**
	 * Get value for a key
	 *
	 * @param mixed $index
	 * @param mixed $default
	 */
	public function get($index, $default = NULL) {

		if($index === NULL) {
			$index = '';
		}

		if($this->offsetExists($index)) {
			return $this->offsetGet($index);
		} else {
			return $default;
		}

	}

	/**
	 * Get an array copy of the element group
	 *
	 * @return array
	 */
	public function getArrayCopy(): array {

		$values = [];

		foreach($this as $key => $entry) {
			$values[$key] = $entry->getArrayCopy();
		}

		return $values;

	}

	/**
	 * Format a Collection as a array using the given rules
	 * $rules is an associative array. Each value can be either:
	 * - 'propertyName' (put as is in the returned array)
	 * - 'propertyName' => 'newPropertyName' (put using the new namle is the returned array)
	 * - 'propertyName' => function($eElement) { ... } (custom property)
	 *
	 * @return array $rules
	 */
	public function format(array $rules): array {

		if($this->depth > 1) {

			$values = [];

			foreach($this as $key => $entry) {
				$values[$key] = $entry->format($rules);
			}

			return $values;

		} else {

			$values = [];

			foreach($this as $eElement) {

				$value = [];

				foreach($rules as $destination => $source) {

					if(is_closure($source)) {
						$value[$destination] = $source($eElement);
					} else {

						if(is_string($destination)) {
							$value[$destination] = $eElement[$source];
						} else {
							$value[$source] = $eElement[$source];
						}

					}

				}

				$values[] = $value;

			}

			return $values;

		}

	}

	/**
	 * Add a property with the given value for all elements present in the element group
	 *
	 * @param string $property Property name
	 * @param mixed $value Property value (may be a scalar value or a callack function taking the element in parameter)
	 */
	public function setColumn(string $property, $value): \Collection {

		foreach($this as $key => $entry) {

			if($this->depth > 1) {
				$entry->addColumn($property, $value);
			} else {

				if(is_closure($value)) {
					$this[$key][$property] = $value($entry);
				} else {
					$this[$key][$property] = $value;
				}

			}

		}

		return $this;

	}

	/**
	 * Drop a property
	 *
	 * @param string $property Property name
	 */
	public function removeColumn(string $property): \Collection {

		foreach($this as $key => $entry) {

			if($this->depth > 1) {
				$entry->removeColumn($property);
			} else {
				unset($this[$key][$property]);
			}

		}

		return $this;

	}

	/*
	 * Get IDs in the element group
	 *
	 * @return array
	 */
	public function getIds(): array {
		return $this->getColumn('id');
	}

	/*
	 * Get values of a property in the element group
	 * Option can be a boolean (returns references) or an int (array level)
	 *
	 * @return array
	 */
	public function getColumn(string $property, ?string $index = NULL): array {

		return $this->getRecursiveColumn($property, $index);

	}

	/**
	 * Idéalement :
	 * $cUser = user\User::newCollection();
	 * Produit un objet du type UserCollection extends Collection
	 * Du coup on peut supprimer cette méthode et rendre getColumn() plus intelligent pour fabriquer automatiquement une collection pertinente
	 */
	public function getColumnCollection(string $property, ?string $index = NULL, int $depth = 1): \Collection {

		$values = $this->getRecursiveColumn($property, $index);

		$collection = new Collection($values);

		if($depth > 1) {
			$collection->setDepth($depth);
		}

		return $collection;

	}

	private function getRecursiveColumn(string $property, ?string $index): array {

		if($this->depth > 1) {

			$values = [];

			foreach($this as $entry) {

				if($index !== NULL) {

					$values += $entry->getRecursiveColumn($property, $index);

				} else {

					$values = array_merge(
						$values,
						$entry->getRecursiveColumn($property, $index)
					);

				}

			}

			return $values;

		} else {

			if($index !== NULL) {

				$values = [];

				foreach($this as $e) {

					if(isset($e[$property])) {

						if(isset($e[$index]))  {
							if($e[$index] instanceof Element) {
								$key = $e[$index]['id'];
							} else {
								$key = $e[$index];
							}
						} else {
							$key = NULL;
						}

						$values[$key] = $e[$property];

					}

				}

				return $values;

			} else {

				$values = [];

				foreach($this as $e) {
					if(isset($e[$property])) {
						$values[] = $e[$property];
					}
				}

				return $values;

			}

		}

	}

	/**
	 * Applies the diff between two Collections
	 * according to IDs from the elements
	 *
	 * @param Collection $c
	 * @param bool $preserveKeys Keep the keys if asked. Otherwise, keys are reset
	 * @return \Collection
	 */
	public function diff(Collection $c, bool $preserveKeys = TRUE): Collection {

		$keys1 = $this->getIds();
		$keys2 = $c->getIds();

		$diff = array_diff($keys1, $keys2);

		$newC = new Collection();

		foreach($this as $key => $eElement) {
			if(in_array($eElement['id'], $diff)) {
				if($preserveKeys) {
					$newC[$key] = $eElement;
				} else {
					$newC->append($eElement);
				}
			}
		}

		return $newC;

	}

	/**
	 * Keep only $number elements in the collection
	 * DO NOT USE WITH LARGE COLLECTIONS
	 *
	 * @return bool TRUE if elements have been removed, FALSE otherwise
	 */
	public function cut(int $number, int $depth = 1): bool {

		$cut = FALSE;
		$this->recursiveCut($this, $number, $depth, $cut);

		return $cut;

	}

	protected function recursiveCut(\Collection $c, int &$number, int $depth, bool &$cut): void {

		if($depth > 1) {
			foreach($c as $offset => $newC) {
				if($number === 0) {
					$c->offsetUnset($offset);
					$cut = TRUE;
				} else {
					$this->recursiveCut($newC, $number, $depth - 1, $cut);
				}
			}
			return;
		}

		if($c->count() <= $number) {
			$number .= $c->count();
			return;
		}

		for($c->rewind(); $c->valid(); ) {

			if($number === 0) {
				$c->offsetUnset($c->key());
				$cut = TRUE;
			} else {
				$number--;
				$c->next();
			}

		}

	}

	/**
	 * Apply a closure to each element of the collection
	 *
	 * @param callable $closure
	 */
	public function map(callable $closure, int $depth = 1): Collection {

		$this->recursiveMap($this, $closure, $depth);

		return $this;

	}

	protected function recursiveMap(\Collection $c, callable $closure, int $depth): void {

		if($depth > 1) {
			foreach($c as $newC) {
				$this->recursiveMap($newC, $closure, $depth - 1);
			}
			return;
		}

		foreach($c as $e) {
			$closure($e);
		}

	}

	/**
	 * Apply a closure to each element of the collection
	 *
	 * @param callable $closure
	 */
	public function expects(array $keys, ?callable $callback = NULL): Collection {

		return $this->map(function(Element $e) use ($keys, $callback) {
			$e->expects($keys, $callback);
		});

	}

	/**
	 * Filter collection
	 *
	 */
	public function filter(Closure $filter, int $depth = 1): Collection {

		if($depth > 1) {

			for($this->rewind(); $this->valid(); ) {

				$c = $this
					->current()
					->filter($filter, $depth - 1);

				if($c->empty()) {
					$this->offsetUnset($this->key());
				} else {
					$this->next();
				}

			}

		} else {

			for($this->rewind(); $this->valid(); ) {

				if($filter($this->current()) === FALSE) {
					$this->offsetUnset($this->key());
				} else {
					$this->next();
				}

			}

		}

		return $this;

	}

	/**
	 * Find from collection with filter
	 *
	 * @param mixed $filter Filter can be a callable function (works just as array_filter do) or a property name (delete entries which do not define the given property)
	 */
	public function find(?Closure $filter = NULL, bool $preserveKeys = TRUE, ?int $limit = NULL, int $depth = 1, bool $clone = TRUE, Closure|Element $default = new Element()): Collection|Element {

		if($depth > 1) {

			$object = new Collection();

			$this->map(fn($e) => $object[] = $e, depth: $depth);

		} else {
			$object = $this;
		}

		$c = new Collection();
		$found = 0;

		foreach($object as $key => $e) {

			if($filter === NULL or $filter($e)) {

				if($preserveKeys) {
					$c[$key] = $clone ? clone $e : $e;
				} else {
					$c[] = $clone ? clone $e : $e;
				}
				$found++;
				if($limit === 1 and $found === 1) {
					return $clone ? clone $e : $e;
				} else if($limit !== NULL and $found >= $limit) {
					return $c;
				}

			}

		}

		if($c->empty() and $limit === 1) {
			return is_closure($default) ? $default() : $default;
		}

		return $c;

	}

	/**
	 * Reduce collection
	 *
	 */
	public function reduce(Closure $callback, mixed $value): mixed {

		for($this->rewind(); $this->valid(); ) {
			$value = $callback($this->current(), $value);
			$this->next();
		}

		return $value;

	}

	/**
	 * Sum values of a property
	 *
	 */
	public function sum(string|Closure $property, int $depth = 1): mixed {

		if($depth > 1) {
			return $this->reduce(fn($c, $n) => $c->sum($property, $depth - 1) + $n, 0);
		} else {
			if(is_string($property)) {
				return $this->reduce(fn($e, $n) => $n + $e[$property], 0);
			} else {
				return $this->reduce(fn($e, $n) => $n + $property($e), 0);
			}
		}

	}

	/**
	 * Returns TRUE if at least one $number matches the callback
	 *
	 */
	public function match(Closure $callback, int $number = 1): bool {

		$count = 0;

		for($this->rewind(); $this->valid(); ) {

			$match = $callback($this->current());

			if($match === TRUE and ++$count >= $number) {
				return TRUE;
			}

			$this->next();

		}

		return FALSE;

	}

	/**
	 * Returns TRUE if all elements matches the callback
	 *
	 */
	public function matchAll(Closure $callback): bool {

		for($this->rewind(); $this->valid(); ) {
			if($callback($this->current())) {
				return TRUE;
			}
			$this->next();
		}

		return FALSE;

	}

	/**
	 * @param Element $e
	 * @return bool
	 */
	public function search(Element $e, Element $default = new Element()): Element {

		if($e->empty()) {
			return $default;
		}

		$e->expects(['id']);

		foreach($this as $eTested) {

			if($eTested->empty()) {
				continue;
			}

			$eTested->expects(['id']);

			if($eTested['id'] === $e['id']) {
				return $eTested;
			}

		}

		return $default;

	}

	/**
	 * Transform to string
	 *
	 */
	public function makeString(Closure $callback): string {

		$output = '';

		for($this->rewind(); $this->valid(); ) {
			$output .= $callback($this->current());
			$this->next();
		}

		return $output;

	}

	/**
	 * Transform to array
	 *
	 */
	public function makeArray(Closure $callback): array {

		$output = [];

		for($this->rewind(); $this->valid(); ) {
			$key = NULL;
			$value = $callback($this->current(), $key);
			if($key === NULL) {
				$output[] = $value;
			} else {
				$output[$key] = $value;
			}
			$this->next();
		}

		return $output;

	}

	/**
	 * Returns first element of a collection
	 */
	public function first(): Element|Collection|null {

		$this->rewind();
		return $this->current();

	}

	/**
	 * Returns last element of a collection
	 */
	public function last(): Element|Collection|null {

		$this->seek($this->count() - 1);
		return $this->current();

	}

	/*
	 * Sort the collection using a property
	 */
	public function sort(Closure|array|string $properties, bool $binary = FALSE, bool $natural = FALSE): Collection {

		if(is_closure($properties) === FALSE) {

			$properties = (array)$properties;
			$order = [];

			foreach($properties as $key => $value) {

				// ['id' => SORT_ASC|SORT_DESC]
				if($value === SORT_ASC or $value === SORT_DESC) {

					$order[] = [
						(array)$key,
						$value
					];


				// ['id']
				} else if(is_string($value)) {

					$order[] = [
						(array)$value,
						SORT_ASC
					];

				// ['user' => ['login' => SORT_ASC|SORT_DESC]]
				} else if(is_array($value)) {

					$list = [$key];
					$sort = SORT_ASC;

					while($value) {

						$subValue = first($value);

						if($subValue === SORT_ASC or $subValue === SORT_DESC) {

							$list[] = array_key_first($value);
							$sort = $subValue;
							break;

						} if(is_string($subValue)) {

							$list[] = $subValue;
							break;

						} else if(is_array($subValue)) {

							$list[] = array_key_first($subValue);
							$value = $subValue;

						} else {
							break;
						}

					}

					$order[] = [
						$list,
						$sort
					];
				}

			}

			if($binary) {
				if($natural) {
					$compare = fn($value1, $value2) => strnatcmp(mb_strtolower($value1), mb_strtolower($value2));
				} else {
					$compare = fn($value1, $value2) => strcmp($value1, $value2);
				}
			} else {
				$collator = get_collator();
				if($natural) {
					$collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
				}
				$compare = fn($value1, $value2) => $collator->compare($value1, $value2);
			}

			$callback = function($eElement1, $eElement2) use ($order, $compare) {

				foreach($order as [$list, $sort]) {

					$value1 = $eElement1;
					$value2 = $eElement2;

					foreach($list as $property) {
						$value1 = $value1[$property] ?? NULL;
						$value2 = $value2[$property] ?? NULL;
					}

					if($value1 === $value2) {
						continue;
					}

					$mul = ($sort === SORT_ASC) ? 1 : -1;

					if($value1 === NULL) {
						return -1 * $mul;
					} else if($value2 === NULL) {
						return 1 * $mul;
					} else if(is_string($value1)) {
						return $compare($value1, $value2) * $mul;
					} else if(is_scalar($value1)) {
						return ($value1 < $value2 ? -1 : 1) * $mul;
					} else if($value1 instanceof Element) {
						return ($value1['id'] < $value2['id'] ? -1 : 1) * $mul;
					}

				}

				return 0;

			};

		} else {
			$callback = $properties;
		}

		$this->uasort($callback);

		return $this;

	}

	/**
	 * Validate tests
	 */
	public function validate(...$tests): \Collection {

		if($this->empty()) {
			throw new NotExistsAction('Empty collection');
		}

		$this->map(function(Element $e) use ($tests) {
			$e->validate(...$tests);
		});

		return $this;

	}

	/**
	 * Same as array + operator
	 * @return Collection the union of the current Collection with the Collection sent as parameter
	 */
	public function appendCollection(Collection $cElement): Collection {

		foreach($cElement as $key => $eElement) {
			if($this->offsetExists($key) === FALSE) {
				$this[$key] = $eElement;
			}
		}

		return $this;

	}

	/**
	 * Chunk group
	 *
	 * @param int $size
	 */
	public function chunk($size): array {

		$splits = [];

		for($i = 0; $i < $size; $i++) {
			$splits[$i] = new Collection();
		}

		$position = 0;

		foreach($this as $eElement) {

			$splits[$position % $size][] = $eElement;
			$position++;

		}

		return $splits;

	}

	/**
	 * Creates a new collection from an array of values
	 *
	 * @param array $values Array of IDs
	 * @param string $element Element name
	 */
	public static function fromArray(array $values, string $element): Collection {

		$cElement = new Collection();

		foreach($values as $key => $value) {

			$eElement = cast($value, $element);

			if($eElement->notEmpty()) {
				$cElement[$key] = $eElement;
			}

		}

		return $cElement;

	}

	/**
	 * Creates an array from a collection
	 *
	 */
	public function toArray(\Closure $callback, $keys = FALSE): array {

		$values = [];

		foreach($this as $eElement) {

			$output = $callback($eElement);

			if($keys) {
				[$key, $value] = $output;
				$values[$key] = $value;
			} else {
				$values[] = $output;
			}

		}

		return $values;

	}

}

/**
 * Handle set fields
 */
class Set {

	protected int $values;

	/**
	 * Build Set with initial values
	 *
	 */
	public function __construct($values = 0) {

		if(is_array($values)) {
			$this->values = 0;
			foreach($values as $value) {
				$this->values |= (int)$value;
			}
		} else {
			$this->values = (int)$values;
		}

	}

	/**
	 * Manipulate content of Set using bit value
	 *
	 * @param int $bit Bit value (1, 2, 4, 8, 16, ...)
	 * @param bool $newValue New value (TRUE/FALSE) or NULL to get current value
	 * @return \Set
	 */
	public function value(int $bit, bool $newValue = NULL): Set|bool {

		if($newValue === TRUE) {

			$this->values = $this->values | $bit;
			return $this;

		} else if($newValue === FALSE) {

			$this->values = $this->values & ~$bit;
			return $this;

		} else {
			return (bool)($this->values & $bit);
		}

	}

	/**
	 * Manipulate content of Set using bit position
	 *
	 * @param int $position Bit position (0, 1, 2, 3, 4, ...)
	 * @param bool $newValue New value (TRUE/FALSE) or NULL to get current value
	 * @return \Set
	 */
	public function position(int $position, bool $newValue = NULL): Set|bool {

		$bit = pow(2, $position);

		return $this->value($bit, $newValue);

	}

	/**
	 * Set all Set values to 0
	 */
	public function reset(): void {
		$this->values = 0;
	}

	/**
	 * Get Set full value
	 *
	 * @return int
	 */
	public function get(): int {
		return $this->values;
	}

	/**
	 * Get all values
	 *
	 * @return int
	 */
	public function values(): array {

		if($this->values === 0) {
			return [];
		}

		$values = [];
		$bits = log($this->values, 2);

		for($i = 0; $i <= $bits; $i++) {
			if($this->position($i)) {
				$values[] = pow(2, $i);
			}
		}

		return $values;
	}

	public function __toString(): string {
		return (string)$this->values;
	}

	public function __sleep(): array {
		return ['values'];
	}

}


/**
 * Describe Element
 */
class Element extends ArrayObject {

	private mixed $ghost = NULL;
	private bool $quick = FALSE;

	public function setGhost(mixed $value) {
		$this->ghost = $value;
	}

	public function add(array $properties): Element {

		foreach($properties as $key => $value) {
			if($this->offsetExists($key) === FALSE) {
				$this->offsetSet($key, $value);
			}
		}

		return $this;

	}

	public function merge(array|ArrayObject $properties): Element {

		foreach($properties as $key => $value) {
			$this->offsetSet($key, $value);
		}

		return $this;

	}

	public function is(Element $e): bool {

		return (
			$e instanceof $this and (
				$e->empty() === $this->empty() or
				($this['id'] ?? NULL) === ($e['id'] ?? NULL)
			)
		);

	}

	public function setQuick(bool $quick): void {
		$this->quick = $quick;
	}

	public function isQuick(): bool {
		return $this->quick;
	}

	public function quick(string $property, string $html, string $class = 'util-quick', array $validate = ['canUpdate']): string {

		foreach($validate as $check) {
			if($this->$check() === FALSE) {
				return $html;
			}
		}

		Asset::js('util', 'form.js');
		Asset::css('util', 'form.css');

		$h = '<div class="'.$class.'" '.$this->getQuickAttributes($property).'">';
			$h .= $html;
		$h .= '</div>';

		return $h;

	}

	public function getQuickAttributes(string $property): string {

		return attrs([
			'onclick' => 'Lime.Quick.start("'.str_replace('\\', '/', $this->getModule()).'", this)',
			'post-id' => $this['id'],
			'post-property' => $property
		]);

	}

	public function getModule(): string {
		return get_class($this);
	}

	public function notEmpty(): bool {
		return ($this->count() > 0);
	}

	public function empty(): bool {
		return ($this->count() === 0);
	}

	public function exists(): bool {

		return (
			$this->offsetExists('id') and
			$this['id'] !== NULL
		);

	}

	/**
	 * Check validity of an element
	 */
	public function validate(...$tests): Element {

		if($this->empty()) {

			if($this->ghost !== NULL) {
				throw new NotExistsAction($this->getModule().' #'.$this->ghost);
			} else {
				throw new NotExistsAction($this->getModule());
			}

		}

		$onFail = function(?string $test) {

			if($test !== NULL and str_starts_with($test, 'can')) {
				throw new NotAllowedAction($this, error: 'can not validate '.$test);
			} else {
				throw new NotExpectedAction($this, error: 'can not validate '.$test);
			}

		};

		foreach($tests as $test) {

			if(is_closure($test)) {

				if($test($this) === FALSE) {
					$onFail('closure');
				}

			} else if(is_bool($test)) {

				if($test === FALSE) {
					$onFail('bool');
				}

			} else {

				if(method_exists($this, $test) === FALSE) {
					throw new Exception('Invalid test \''.$test.'\'');
				}

				if($this->$test() === FALSE) {
					$onFail($test);
				}

			}

		}

		return $this;

	}

	/**
	 * Check validity of a property of an element
	 */
	public function validateProperty(string $property, mixed $value): Element {

		if($this->empty()) {

			if($this->ghost !== NULL) {
				throw new NotExistsAction($this->getModule().' #'.$this->ghost);
			} else {
				throw new NotExistsAction($this->getModule());
			}

		}

		$this->expects($property);

		if($this->model()->getPropertyToModule($property)) {

			if(
				$this[$property] instanceof Element === FALSE or
				$value instanceof Element === FALSE or
				$this[$property]->empty() !== $value->empty() or
				(
					$this[$property]->notEmpty() and
					(($this[$property]['id'] ?? NULL) !== ($value['id'] ?? NULL))
				)
			) {
				throw new NotExpectedAction($this, error: 'can not validate property '.$property);
			}

		} else {

			if($this[$property] !== $value) {
				throw new NotExpectedAction($this, error: 'can not validate property '.$property);
			}

		}

		return $this;

	}

	/**
	 * Get properties for further selection
	 */
	public static function getSelection(): array {
		return [];
	}

	public function expects(string|array $keys, callable $callback = NULL): Element {

		$lacks = $this->checkExpected($this, (array)$keys);

		if($lacks) {

			if($callback !== NULL) {
				$callback($this);
			} else {
				throw new ElementException(p(
					'Property '.implode(', ', $lacks).' of Element '.get_class($this).' is not set',
					'Properties '.implode(', ', $lacks).' of Element '.get_class($this).' are not set',
					count($lacks)
				));
			}
		}

		return $this;

	}

	public function extracts($keys): array {

		$output = [];

		foreach($keys as $key) {
			if($this->offsetExists($key)) {
				$output[$key] = $this->offsetGet($key);
			}
		}

		return $output;

	}

	private function checkExpected(Element $e, array $keys): array {

		$lacks = [];

		foreach($keys as $key => $value) {

			if(is_string($key)) {
				$property = $key;
				$value = (array)$value;
			} else if(is_string($value)) {
				$property = $value;
			} else {
				throw new Exception('Invalid keys');
			}

			if($e->offsetExists($property) === FALSE) {
				$lacks[] = $property;
			} else if(is_array($value)) {

				if($e[$key] instanceof Element) {
					$result = $this->checkExpected($e[$key], $value);
				} else if(is_array($e[$key])) {
					$result = array_expects($e[$key], $value, function(array $result) {
						return $result;
					});
				} else {
					$result = ['Invalid type'];
				}

				if($result) {
					$lacks[] = $property.'['.implode(', ', $result).']';
				}

			}

		}

		return $lacks;

	}

	public static function fail(string|FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		throw new Exception('Invalid call of Element::fail()');
	}

	/**
	 * Create an element with properties $properties using $input (ie: $_POST)
	 *
	 * @param array $properties List of properties (ie: ['email', 'sex' (in $input) => 'gender' (element property)]
	 * @param array $input List of values indexed by property name
	 * @param array $callbacks Callback function for additional checks
	 * @param string $for create, update
	 */
	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$model = $this->model();

		$callbackWrapper = $callbacks['wrapper'] ?? fn($property) => $property;
		$newProperties = [];

		foreach($properties as $key => $property) {

			$callbacksProperty = [];

			foreach($callbacks as $name => $callback) {

				if(str_contains($name, '.') and $property === explode('.', $name)[0]) {
					$callbacksProperty[$name] = $callback;
				}

			}

			if(isset($callbacksProperty[$property.'.argument'])) {
				$argument = $callbacksProperty[$property.'.argument']($property);
			} else {
				$argument = $property;
			}

			$value = $input[$argument] ?? NULL;
			$newProperties[] = $property;

			if($model->hasProperty($property)) {

				$callbackCast = $callbacksProperty[$property.'.cast'] ?? function(&$value, $newProperties) use ($model, $property): bool {

					$model->cast($property, $value);
					return TRUE;

				};

				$callbackPrepare = $callbacksProperty[$property.'.prepare'] ?? function(&$value, $newProperties) use ($model, $property): bool {

					if(strpos($model->getPropertyType($property), 'editor') === 0) {
						$value = (new \editor\XmlLib())->fromHtml($value);
					}

					return TRUE;

				};

				$callbackCheck = $callbacksProperty[$property.'.check'] ?? function(&$value, $newProperties) use ($model, $property): bool {

					if(
						$model->isPropertyNull($property) and
						$value === ''
					) {
						$value = NULL;
					}

					return $model->check($property, $value);

				};

				$callbackSet = $callbacksProperty[$property.'.set'] ?? function($value, $newProperties) use ($property) {
					$this[$property] = $value;
				};

				unset($callbacksProperty[$property.'.prepare'], $callbacksProperty[$property.'.check'], $callbacksProperty[$property.'.set']);

				$callbacksSelected = [];
				$callbacksSelected[$property.'.cast'] = $callbackCast;
				$callbacksSelected[$property.'.prepare'] = $callbackPrepare;
				$callbacksSelected[$property.'.check'] = $callbackCheck;
				$callbacksSelected += $callbacksProperty;
				$callbacksSelected[$property.'.set'] = $callbackSet;

			} else {
				$callbacksSelected = $callbacksProperty;
			}

			// Check callback function
			$wrapper = $callbackWrapper($property);
			$success = TRUE;

			foreach($callbacksSelected as $name => $callback) {

				$onError = function() use ($name, $property, $wrapper, &$success) {

					$class = $this->getModule($this);
					$error = explode('.', $name)[1];

					$class::fail($property.'.'.$error, wrapper: $wrapper);

					$success = FALSE;

				};

				try {

					if($callback($value, $newProperties) === FALSE) {
						throw new BuildPropertyError();
					}

				} catch(BuildPropertySkip) {
					break;
				} catch(BuildPropertySuccess) {
				} catch(FailException $e) {
					Fail::log($e, wrapper: $wrapper);
					$success = FALSE;
					break;
				} catch(BuildPropertyError) {
					$onError();
					break;
				} catch(BuildElementError) {
					$onError();
					return $newProperties;
				}

			}

		}

		return $newProperties;

	}

	public function buildIndex(array $properties, array $input, $index, array $callbacks = []): array {

		$callbacks['wrapper'] = function(string $property) use ($index) {
			return $property.'['.$index.']';
		};

		$values = [];

		foreach($input as $inputProperty => $inputValues) {
			if(is_array($inputValues) and array_key_exists($index, $inputValues)) {
				$values[$inputProperty] = $inputValues[$index];
			}
		}
		
		return $this->build(
			$properties,
			$values,
			$callbacks
		);

	}

	public function buildProperty(string $property, $value, array $callbacks = []): array {

		return $this->build(
			[$property],
			[$property => $value],
			$callbacks
		);

	}

	public function format(string $property, array $options = []): ?string {
		return '';
	}

	public function canCreate(): bool {
		return $this->canWrite();
	}

	public function canRead(): bool {
		return TRUE;
	}

	public function canWrite(): bool {
		return $this->canRead();
	}

	public function canUpdate(): bool {
		return $this->canWrite();
	}

	public function canDelete(): bool {
		return $this->canWrite();
	}

	public function __toString(): string {
		return $this->toString(3);
	}

	public function toString(?int $recursion = NULL): string {

		$output = 'Element::'.get_class($this).' ('."\n";

		foreach($this as $key => $value) {

			if($value instanceof Element or $value instanceof Collection) {
				if($recursion > 0) {
					$string = trim($value->toString($recursion === NULL ? NULL : $recursion - 1));
				} else {
					$string = get_class($value).' [...]';
				}
				$output .= '   ['.$key.'] => '.str_replace("\n", "\n   ", $string)."\n";
			} else {

				switch(gettype($value)) {

					case 'integer' :
					case 'double' :
						$displayValue = $value."\n";
						break;

					case 'boolean' :
						$displayValue = ($value ? 'TRUE' : 'FALSE')."\n";
						break;

					case 'NULL' :
						$displayValue = 'NULL'."\n";
						break;

					case 'string' :
						$displayValue = '"'.$value.'"'."\n";
						break;

					default :
						ob_start();
						var_dump($value);
						$displayValue = ob_get_clean();

				}
				$output .= '   ['.$key.'] => '.$displayValue;
			}

		}

		$output .= ')'."\n";

		return $output;

	}

}

class BuildPropertySkip extends Exception {

}

class BuildPropertySuccess extends Exception {

}

class BuildPropertyError extends Exception {

}

class BuildElementError extends Exception {

}

trait FilterElement {

	public static function POST(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_POST[$value] ?? NULL, $property, $default);
	}

	public static function GET(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_GET[$value] ?? NULL, $property, $default);
	}

	public static function REQUEST(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_REQUEST[$value] ?? NULL, $property, $default);
	}

	public static function INPUT(mixed $value, string $property, $default = NULL): mixed {

		return match(Route::getRequestMethod()) {
			'GET' => self::GET($value, $property, $default),
			'POST' => self::POST($value, $property, $default),
			default => throw new Exception('Unhandled method '.Route::getRequestMethod())
		};

	}

	public static function filter(mixed $value, string $property, $default = NULL): mixed {

		if(str_starts_with($property, '?')) {
			$allowNull = TRUE;
			$property = substr($property, 1);
		} else {
			$allowNull = FALSE;
		}

		if(
			$allowNull === FALSE and
			$value === NULL
		) {
			return is_closure($default) ? $default($value) : $default;
		}

		self::model()->cast($property, $value);

		if(self::model()->check($property, $value)) {
			return $value;
		} else {
			return is_closure($default) ? $default($value) : $default;
		}

	}

}


class ElementException extends Exception {

}

/**
 * Db value
 */
class Sql {

	protected string $value;
	protected string $type;

	public function __construct(string $value, string $type = 'text') {
		$this->value = $value;
		$this->type = $type;
	}

	public function __toString(): string {
		return (string)$this->value;
	}

	public function getType(): string {
		return $this->type;
	}

}

class Search {

	private bool $sortStatus = TRUE;

	public function __construct(
		public array $properties = [],
		public ?string $sort = NULL
	) {

	}

	public function sort(?string $sort): self {

		$this->sort = $sort;
		return $this;

	}

	public function validateSort(array $possibleProperties, ?string $default = NULL): self {

		if(empty($this->sort)) {
			$this->sort = $default ?? first($possibleProperties);
		} else if($this->sort instanceof Sql) {
		} else {

			[$property] = self::splitSort($this->sort);

			if(in_array($property, $possibleProperties, TRUE) === FALSE) {
				$this->sort = $default;
			}

		}

		return $this;

	}

	public function buildSort(array $override = []): array|Sql {

		if($this->sort === NULL) {
			return [];
		} else if($this->sort instanceof Sql) {
			return $this->sort;
		} else {

			[$property, $direction] = self::splitSort($this->sort);

			if(isset($override[$property])) {
				return $override[$property]($direction);
			} else {

				return [
					$property => $direction
				];

			}

		}

	}
	
	private static function splitSort(string $sort): array {
		
		if(str_ends_with($sort, '-')) {
			$direction = SORT_DESC;
			$property = substr($sort, 0, -1);
		} else if(str_ends_with($sort, '+')) {
			$direction = SORT_ASC;
			$property = substr($sort, 0, -1);
		} else {
			$direction = SORT_ASC;
			$property = $sort;
		}

		return [$property, $direction];
		
	}

	public function setSortStatus(bool $status): void {
		$this->sortStatus = $status;
	}

	public function linkSort(string $property, string $label, int $defaultDirection = SORT_ASC): string {

		if($this->sortStatus === FALSE) {
			return $label;
		}

		if($this->sort !== NULL and str_ends_with($this->sort, '-')) {
			$currentDirection = '-';
			$currentProperty = substr($this->sort, 0, -1);
		} else if($this->sort !== NULL and str_ends_with($this->sort, '+')) {
			$currentDirection = '+';
			$currentProperty = substr($this->sort, 0, -1);
		} else {
			$currentDirection = '';
			$currentProperty = $this->sort;
		}

		if($property === $currentProperty) {
			$direction = ($currentDirection === '') ? '-' : '';
		} else {
			$direction = ($defaultDirection === SORT_ASC) ? '' : '-';
		}

		$request = LIME_REQUEST;
		$request = \util\HttpUi::setArgument($request, 'sort', $property.$direction);

		$h = '<span style="white-space: nowrap">';

			$h .= '<a href="'.$request.'">';
				$h .= $label;
			$h .= '</a>';

			if($property === $currentProperty) {
				$h .= '&nbsp;'.\Asset::icon('sort-alpha-down'.($currentDirection === '' ? '' : '-alt'));
			}

		$h .= '</span>';

		return $h;

	}

	public function notEmpty(array $exclude = []): bool {
		return ($this->getFiltered($exclude) !== []);
	}

	public function empty(array $exclude = []): bool {
		return ($this->getFiltered($exclude) === []);
	}

	public function count(): int {
		return count($this->getFiltered());
	}

	public function getFiltered(array $exclude = []): array {
		return array_filter($this->properties, fn($value, $key) => (
			in_array($key, $exclude) === FALSE and $this->isFiltered($key)
		),  ARRAY_FILTER_USE_BOTH);
	}

	public function isFiltered(string $property): bool {

		return $this->has($property) and !(
			($this->properties[$property] instanceof Element and $this->properties[$property]->empty()) or
			empty($this->properties[$property])
		);

	}

	public function filter(string $property, Closure $callback): bool {

		if($this->isFiltered($property)) {
			$callback($this->properties[$property]);
			return TRUE;
		} else {
			return FALSE;
		}

	}

	public function toArray(array $exclude = []): array {

		$export = [];

		foreach($this->getFiltered($exclude) as $key => $value) {
			$export[$key] = ($value instanceof Element) ? $value['id'] : $value;
		}

		return $export;

	}

	public function toQuery(array $exclude = [], string $prefix = ''): string {

		$query = http_build_query(self::toArray($exclude));

		return $query ? $prefix.$query : '';

	}

	public function has(string $property): bool {
		return array_key_exists($property, $this->properties);
	}

	public function get(string $property, mixed $defaultValue = NULL): mixed {
		return $this->properties[$property] ?? $defaultValue;
	}

	public function getSort(): mixed {
		return $this->sort;
	}

	public function set(string $property, mixed $value): void {
		$this->properties[$property] = $value;
	}

}

class PropertyDescriber {

	/**
	 * Property name
	 */
	public ?string $property = NULL;

	/**
	 * Property label
	 */
	public ?string $label = NULL;

	/**
	 * Property type
	 */
	public ?string $type = NULL;

	/**
	 * Property range
	 */
	public ?array $range = NULL;

	/**
	 * Property enum
	 */
	public ?array $enum = NULL;

	/**
	 * Property attributes
	 */
	public array|Closure $attributes = [];

	/**
	 * Property set
	 */
	public ?array $set = NULL;

	/**
	 * Property module
	 */
	public ?string $module = NULL;

	/**
	 * Property default value
	 */
	public $default = NULL;

	/**
	 * List of values for the type
	 */
	public array|Collection|Closure $values = [];

	/**
	 * User-defined values
	 */
	private array $magic = [];

	/**
	 * Attributes for dynamic groups
	 */
	public array|Closure $group = [];

	/**
	 * Attributes for dynamic input groups
	 */
	public array|Closure $inputGroup = [];

	/**
	 * Override field type
	 */
	public $field = 'default';

	/**
	 * Display as mandatory
	 */
	public $asterisk = FALSE;

	public function __construct(string $property, array $values = []) {

		$this->property = $property;

		foreach($values as $key => $value) {
			$this->$key = $value;
		}

	}

	public function __get(string $key) {
		return $this->magic[$key] ?? NULL;
	}

	public function __set(string $key, $value): void {
		$this->magic[$key] = $value;
	}

	public function __toString(): string {
		return (string)$this->label;
	}

}
?>