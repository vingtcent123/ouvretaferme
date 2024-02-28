<?php
namespace series;

abstract class SeriesElement extends \Element {

	use \FilterElement;

	private static ?SeriesModel $model = NULL;

	const BED = 'bed';
	const BLOCK = 'block';

	const GREENHOUSE = 'greenhouse';
	const OUTDOOR = 'outdoor';
	const MIX = 'mix';

	const ANNUAL = 'annual';
	const PERENNIAL = 'perennial';

	const GROWING = 'growing';
	const CONTINUED = 'continued';
	const FINISHED = 'finished';

	const OPEN = 'open';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return Series::model()->getProperties();
	}

	public static function model(): SeriesModel {
		if(self::$model === NULL) {
			self::$model = new SeriesModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Series::'.$failName, $arguments, $wrapper);
	}

}


class SeriesModel extends \ModuleModel {

	protected string $module = 'series\Series';
	protected string $package = 'series';
	protected string $table = 'series';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'use' => ['enum', [\series\Series::BED, \series\Series::BLOCK], 'cast' => 'enum'],
			'mode' => ['enum', [\series\Series::GREENHOUSE, \series\Series::OUTDOOR, \series\Series::MIX], 'cast' => 'enum'],
			'plants' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'area' => ['int24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'areaPermanent' => ['int24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'areaTarget' => ['int24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'length' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'lengthPermanent' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'lengthTarget' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'bedWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'bedStartCalculated' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'bedStartUser' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'bedStopCalculated' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'bedStopUser' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'alleyWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'comment' => ['editor16', 'null' => TRUE, 'cast' => 'string'],
			'sequence' => ['element32', 'production\Sequence', 'null' => TRUE, 'cast' => 'element'],
			'cycle' => ['enum', [\series\Series::ANNUAL, \series\Series::PERENNIAL], 'cast' => 'enum'],
			'perennialLifetime' => ['int8', 'min' => 2, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'perennialSeason' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'perennialFirst' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'perennialStatus' => ['enum', [\series\Series::GROWING, \series\Series::CONTINUED, \series\Series::FINISHED], 'null' => TRUE, 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'status' => ['enum', [\series\Series::OPEN, \series\Series::CLOSED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'season', 'use', 'mode', 'plants', 'area', 'areaPermanent', 'areaTarget', 'length', 'lengthPermanent', 'lengthTarget', 'bedWidth', 'bedStartCalculated', 'bedStartUser', 'bedStopCalculated', 'bedStopUser', 'alleyWidth', 'comment', 'sequence', 'cycle', 'perennialLifetime', 'perennialSeason', 'perennialFirst', 'perennialStatus', 'createdAt', 'createdBy', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'sequence' => 'production\Sequence',
			'perennialFirst' => 'series\Series',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'season'],
			['perennialFirst']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'mode' :
				return Series::OUTDOOR;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'status' :
				return Series::OPEN;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'use' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'mode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'cycle' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'perennialStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SeriesModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SeriesModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SeriesModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): SeriesModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): SeriesModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): SeriesModel {
		return $this->where('season', ...$data);
	}

	public function whereUse(...$data): SeriesModel {
		return $this->where('use', ...$data);
	}

	public function whereMode(...$data): SeriesModel {
		return $this->where('mode', ...$data);
	}

	public function wherePlants(...$data): SeriesModel {
		return $this->where('plants', ...$data);
	}

	public function whereArea(...$data): SeriesModel {
		return $this->where('area', ...$data);
	}

	public function whereAreaPermanent(...$data): SeriesModel {
		return $this->where('areaPermanent', ...$data);
	}

	public function whereAreaTarget(...$data): SeriesModel {
		return $this->where('areaTarget', ...$data);
	}

	public function whereLength(...$data): SeriesModel {
		return $this->where('length', ...$data);
	}

	public function whereLengthPermanent(...$data): SeriesModel {
		return $this->where('lengthPermanent', ...$data);
	}

	public function whereLengthTarget(...$data): SeriesModel {
		return $this->where('lengthTarget', ...$data);
	}

	public function whereBedWidth(...$data): SeriesModel {
		return $this->where('bedWidth', ...$data);
	}

	public function whereBedStartCalculated(...$data): SeriesModel {
		return $this->where('bedStartCalculated', ...$data);
	}

	public function whereBedStartUser(...$data): SeriesModel {
		return $this->where('bedStartUser', ...$data);
	}

	public function whereBedStopCalculated(...$data): SeriesModel {
		return $this->where('bedStopCalculated', ...$data);
	}

	public function whereBedStopUser(...$data): SeriesModel {
		return $this->where('bedStopUser', ...$data);
	}

	public function whereAlleyWidth(...$data): SeriesModel {
		return $this->where('alleyWidth', ...$data);
	}

	public function whereComment(...$data): SeriesModel {
		return $this->where('comment', ...$data);
	}

	public function whereSequence(...$data): SeriesModel {
		return $this->where('sequence', ...$data);
	}

	public function whereCycle(...$data): SeriesModel {
		return $this->where('cycle', ...$data);
	}

	public function wherePerennialLifetime(...$data): SeriesModel {
		return $this->where('perennialLifetime', ...$data);
	}

	public function wherePerennialSeason(...$data): SeriesModel {
		return $this->where('perennialSeason', ...$data);
	}

	public function wherePerennialFirst(...$data): SeriesModel {
		return $this->where('perennialFirst', ...$data);
	}

	public function wherePerennialStatus(...$data): SeriesModel {
		return $this->where('perennialStatus', ...$data);
	}

	public function whereCreatedAt(...$data): SeriesModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): SeriesModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereStatus(...$data): SeriesModel {
		return $this->where('status', ...$data);
	}


}


abstract class SeriesCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Series {

		$e = new Series();

		if(empty($id)) {
			Series::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Series::getSelection();
		}

		if(Series::model()
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
			$properties = Series::getSelection();
		}

		if($sort !== NULL) {
			Series::model()->sort($sort);
		}

		return Series::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Series {

		return new Series(['id' => NULL]);

	}

	public static function create(Series $e): void {

		Series::model()->insert($e);

	}

	public static function update(Series $e, array $properties): void {

		$e->expects(['id']);

		Series::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Series $e, array $properties): void {

		Series::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Series $e): void {

		$e->expects(['id']);

		Series::model()->delete($e);

	}

}


class SeriesPage extends \ModulePage {

	protected string $module = 'series\Series';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SeriesLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SeriesLib::getPropertiesUpdate()
		);
	}

}
?>