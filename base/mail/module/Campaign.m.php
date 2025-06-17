<?php
namespace mail;

abstract class CampaignElement extends \Element {

	use \FilterElement;

	private static ?CampaignModel $model = NULL;

	public static function getSelection(): array {
		return Campaign::model()->getProperties();
	}

	public static function model(): CampaignModel {
		if(self::$model === NULL) {
			self::$model = new CampaignModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Campaign::'.$failName, $arguments, $wrapper);
	}

}


class CampaignModel extends \ModuleModel {

	protected string $module = 'mail\Campaign';
	protected string $package = 'mail';
	protected string $table = 'mailCampaign';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

	}

	public function select(...$fields): CampaignModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CampaignModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CampaignModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CampaignModel {
		return $this->where('farm', ...$data);
	}


}


abstract class CampaignCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Campaign {

		$e = new Campaign();

		if(empty($id)) {
			Campaign::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Campaign::getSelection();
		}

		if(Campaign::model()
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
			$properties = Campaign::getSelection();
		}

		if($sort !== NULL) {
			Campaign::model()->sort($sort);
		}

		return Campaign::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Campaign {

		return new Campaign(['id' => NULL]);

	}

	public static function create(Campaign $e): void {

		Campaign::model()->insert($e);

	}

	public static function update(Campaign $e, array $properties): void {

		$e->expects(['id']);

		Campaign::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Campaign $e, array $properties): void {

		Campaign::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Campaign $e): void {

		$e->expects(['id']);

		Campaign::model()->delete($e);

	}

}


class CampaignPage extends \ModulePage {

	protected string $module = 'mail\Campaign';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CampaignLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CampaignLib::getPropertiesUpdate()
		);
	}

}
?>