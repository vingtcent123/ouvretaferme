<?php
namespace plant;

abstract class QualityElement extends \Element {

	use \FilterElement;

	private static ?QualityModel $model = NULL;

	public static function getSelection(): array {
		return Quality::model()->getProperties();
	}

	public static function model(): QualityModel {
		if(self::$model === NULL) {
			self::$model = new QualityModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Quality::'.$failName, $arguments, $wrapper);
	}

}


class QualityModel extends \ModuleModel {

	protected string $module = 'plant\Quality';
	protected string $package = 'plant';
	protected string $table = 'plantQuality';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'comment' => ['editor16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'yield' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'comment', 'plant', 'farm', 'yield', 'createdAt'
		]);

		$this->propertiesToModule += [
			'plant' => 'plant\Plant',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['plant']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'plant', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): QualityModel {
		return parent::select(...$fields);
	}

	public function where(...$data): QualityModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): QualityModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): QualityModel {
		return $this->where('name', ...$data);
	}

	public function whereComment(...$data): QualityModel {
		return $this->where('comment', ...$data);
	}

	public function wherePlant(...$data): QualityModel {
		return $this->where('plant', ...$data);
	}

	public function whereFarm(...$data): QualityModel {
		return $this->where('farm', ...$data);
	}

	public function whereYield(...$data): QualityModel {
		return $this->where('yield', ...$data);
	}

	public function whereCreatedAt(...$data): QualityModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class QualityCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Quality {

		$e = new Quality();

		if(empty($id)) {
			Quality::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Quality::getSelection();
		}

		if(Quality::model()
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
			$properties = Quality::getSelection();
		}

		if($sort !== NULL) {
			Quality::model()->sort($sort);
		}

		return Quality::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Quality {

		return new Quality(['id' => NULL]);

	}

	public static function create(Quality $e): void {

		Quality::model()->insert($e);

	}

	public static function update(Quality $e, array $properties): void {

		$e->expects(['id']);

		Quality::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Quality $e, array $properties): void {

		Quality::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Quality $e): void {

		$e->expects(['id']);

		Quality::model()->delete($e);

	}

}


class QualityPage extends \ModulePage {

	protected string $module = 'plant\Quality';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? QualityLib::getPropertiesCreate(),
		   $propertiesUpdate ?? QualityLib::getPropertiesUpdate()
		);
	}

}
?>