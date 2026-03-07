<?php
namespace farm;

abstract class SurveyElement extends \Element {

	use \FilterElement;

	private static ?SurveyModel $model = NULL;

	public static function getSelection(): array {
		return Survey::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SurveyModel {
		if(self::$model === NULL) {
			self::$model = new SurveyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Survey::'.$failName, $arguments, $wrapper);
	}

}


class SurveyModel extends \ModuleModel {

	protected string $module = 'farm\Survey';
	protected string $package = 'farm';
	protected string $table = 'farmSurvey';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'createdAt' => ['date', 'cast' => 'string'],
			'achatRevente' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'depotVente' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'autofacturation' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'cagnotte' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'createdAt', 'achatRevente', 'depotVente', 'autofacturation', 'cagnotte'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm']
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

	public function select(...$fields): SurveyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SurveyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SurveyModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): SurveyModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): SurveyModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereAchatRevente(...$data): SurveyModel {
		return $this->where('achatRevente', ...$data);
	}

	public function whereDepotVente(...$data): SurveyModel {
		return $this->where('depotVente', ...$data);
	}

	public function whereAutofacturation(...$data): SurveyModel {
		return $this->where('autofacturation', ...$data);
	}

	public function whereCagnotte(...$data): SurveyModel {
		return $this->where('cagnotte', ...$data);
	}


}


abstract class SurveyCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Survey {

		$e = new Survey();

		if(empty($id)) {
			Survey::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Survey::getSelection();
		}

		if(Survey::model()
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
			$properties = Survey::getSelection();
		}

		if($sort !== NULL) {
			Survey::model()->sort($sort);
		}

		return Survey::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Survey {

		return new Survey($properties);

	}

	public static function create(Survey $e): void {

		Survey::model()->insert($e);

	}

	public static function update(Survey $e, array $properties): void {

		$e->expects(['id']);

		Survey::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Survey $e, array $properties): void {

		Survey::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Survey $e): void {

		$e->expects(['id']);

		Survey::model()->delete($e);

	}

}


class SurveyPage extends \ModulePage {

	protected string $module = 'farm\Survey';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SurveyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SurveyLib::getPropertiesUpdate()
		);
	}

}
?>