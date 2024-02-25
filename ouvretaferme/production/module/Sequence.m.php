<?php
namespace production;

abstract class SequenceElement extends \Element {

	use \FilterElement;

	private static ?SequenceModel $model = NULL;

	const ANNUAL = 'annual';
	const PERENNIAL = 'perennial';

	const BED = 'bed';
	const BLOCK = 'block';

	const GREENHOUSE = 'greenhouse';
	const OUTDOOR = 'outdoor';
	const MIX = 'mix';

	const PRIVATE = 'private';
	const PUBLIC = 'public';

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return Sequence::model()->getProperties();
	}

	public static function model(): SequenceModel {
		if(self::$model === NULL) {
			self::$model = new SequenceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Sequence::'.$failName, $arguments, $wrapper);
	}

}


class SequenceModel extends \ModuleModel {

	protected string $module = 'production\Sequence';
	protected string $package = 'production';
	protected string $table = 'productionSequence';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'summary' => ['text8', 'min' => 1, 'max' => 100, 'null' => TRUE, 'cast' => 'string'],
			'description' => ['editor16', 'null' => TRUE, 'cast' => 'string'],
			'cycle' => ['enum', [\production\Sequence::ANNUAL, \production\Sequence::PERENNIAL], 'cast' => 'enum'],
			'perennialLifetime' => ['int8', 'min' => 2, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'author' => ['element32', 'user\User', 'cast' => 'element'],
			'duplicateOf' => ['element32', 'production\Sequence', 'null' => TRUE, 'cast' => 'element'],
			'plants' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'use' => ['enum', [\production\Sequence::BED, \production\Sequence::BLOCK], 'cast' => 'enum'],
			'bedWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'alleyWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'mode' => ['enum', [\production\Sequence::GREENHOUSE, \production\Sequence::OUTDOOR, \production\Sequence::MIX], 'cast' => 'enum'],
			'comment' => ['editor16', 'null' => TRUE, 'cast' => 'string'],
			'visibility' => ['enum', [\production\Sequence::PRIVATE, \production\Sequence::PUBLIC], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\production\Sequence::ACTIVE, \production\Sequence::CLOSED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'summary', 'description', 'cycle', 'perennialLifetime', 'farm', 'author', 'duplicateOf', 'plants', 'use', 'bedWidth', 'alleyWidth', 'mode', 'comment', 'visibility', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'author' => 'user\User',
			'duplicateOf' => 'production\Sequence',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'author' :
				return \user\ConnectionLib::getOnline();

			case 'mode' :
				return Sequence::OUTDOOR;

			case 'visibility' :
				return Sequence::PRIVATE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Sequence::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'cycle' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'use' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'mode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'visibility' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SequenceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SequenceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SequenceModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): SequenceModel {
		return $this->where('name', ...$data);
	}

	public function whereSummary(...$data): SequenceModel {
		return $this->where('summary', ...$data);
	}

	public function whereDescription(...$data): SequenceModel {
		return $this->where('description', ...$data);
	}

	public function whereCycle(...$data): SequenceModel {
		return $this->where('cycle', ...$data);
	}

	public function wherePerennialLifetime(...$data): SequenceModel {
		return $this->where('perennialLifetime', ...$data);
	}

	public function whereFarm(...$data): SequenceModel {
		return $this->where('farm', ...$data);
	}

	public function whereAuthor(...$data): SequenceModel {
		return $this->where('author', ...$data);
	}

	public function whereDuplicateOf(...$data): SequenceModel {
		return $this->where('duplicateOf', ...$data);
	}

	public function wherePlants(...$data): SequenceModel {
		return $this->where('plants', ...$data);
	}

	public function whereUse(...$data): SequenceModel {
		return $this->where('use', ...$data);
	}

	public function whereBedWidth(...$data): SequenceModel {
		return $this->where('bedWidth', ...$data);
	}

	public function whereAlleyWidth(...$data): SequenceModel {
		return $this->where('alleyWidth', ...$data);
	}

	public function whereMode(...$data): SequenceModel {
		return $this->where('mode', ...$data);
	}

	public function whereComment(...$data): SequenceModel {
		return $this->where('comment', ...$data);
	}

	public function whereVisibility(...$data): SequenceModel {
		return $this->where('visibility', ...$data);
	}

	public function whereCreatedAt(...$data): SequenceModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): SequenceModel {
		return $this->where('status', ...$data);
	}


}


abstract class SequenceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Sequence {

		$e = new Sequence();

		if(empty($id)) {
			Sequence::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Sequence::getSelection();
		}

		if(Sequence::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getByIds(mixed $ids, array $properties = [], mixed $sort = NULL, mixed $index = NULL): \Collection {

		if(empty($ids)) {
			return new \Collection();
		}

		if($properties === []) {
			$properties = Sequence::getSelection();
		}

		if($sort !== NULL) {
			Sequence::model()->sort($sort);
		}

		return Sequence::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Sequence {

		return new Sequence(['id' => NULL]);

	}

	public static function create(Sequence $e): void {

		Sequence::model()->insert($e);

	}

	public static function update(Sequence $e, array $properties): void {

		$e->expects(['id']);

		Sequence::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Sequence $e, array $properties): void {

		Sequence::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Sequence $e): void {

		$e->expects(['id']);

		Sequence::model()->delete($e);

	}

}


class SequencePage extends \ModulePage {

	protected string $module = 'production\Sequence';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SequenceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SequenceLib::getPropertiesUpdate()
		);
	}

}
?>