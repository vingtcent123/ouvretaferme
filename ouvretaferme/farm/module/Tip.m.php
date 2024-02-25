<?php
namespace farm;

abstract class TipElement extends \Element {

	use \FilterElement;

	private static ?TipModel $model = NULL;

	public static function getSelection(): array {
		return Tip::model()->getProperties();
	}

	public static function model(): TipModel {
		if(self::$model === NULL) {
			self::$model = new TipModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Tip::'.$failName, $arguments, $wrapper);
	}

}


class TipModel extends \ModuleModel {

	protected string $module = 'farm\Tip';
	protected string $package = 'farm';
	protected string $table = 'farmTip';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'unique' => TRUE, 'cast' => 'element'],
			'list' => ['json', 'cast' => 'array'],
			'shown' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'clicked' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'closed' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'unmatched' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastSeniority' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'pickPosition' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'list', 'shown', 'clicked', 'closed', 'unmatched', 'lastSeniority', 'pickPosition'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'list' :
				return [];

			case 'shown' :
				return 0;

			case 'clicked' :
				return 0;

			case 'closed' :
				return 0;

			case 'unmatched' :
				return 0;

			case 'lastSeniority' :
				return 0;

			case 'pickPosition' :
				return 0;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'list' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'list' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): TipModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TipModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TipModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): TipModel {
		return $this->where('user', ...$data);
	}

	public function whereList(...$data): TipModel {
		return $this->where('list', ...$data);
	}

	public function whereShown(...$data): TipModel {
		return $this->where('shown', ...$data);
	}

	public function whereClicked(...$data): TipModel {
		return $this->where('clicked', ...$data);
	}

	public function whereClosed(...$data): TipModel {
		return $this->where('closed', ...$data);
	}

	public function whereUnmatched(...$data): TipModel {
		return $this->where('unmatched', ...$data);
	}

	public function whereLastSeniority(...$data): TipModel {
		return $this->where('lastSeniority', ...$data);
	}

	public function wherePickPosition(...$data): TipModel {
		return $this->where('pickPosition', ...$data);
	}


}


abstract class TipCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Tip {

		$e = new Tip();

		if(empty($id)) {
			Tip::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Tip::getSelection();
		}

		if(Tip::model()
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
			$properties = Tip::getSelection();
		}

		if($sort !== NULL) {
			Tip::model()->sort($sort);
		}

		return Tip::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Tip {

		return new Tip(['id' => NULL]);

	}

	public static function create(Tip $e): void {

		Tip::model()->insert($e);

	}

	public static function update(Tip $e, array $properties): void {

		$e->expects(['id']);

		Tip::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Tip $e, array $properties): void {

		Tip::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Tip $e): void {

		$e->expects(['id']);

		Tip::model()->delete($e);

	}

}


class TipPage extends \ModulePage {

	protected string $module = 'farm\Tip';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TipLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TipLib::getPropertiesUpdate()
		);
	}

}
?>