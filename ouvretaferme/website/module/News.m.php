<?php
namespace website;

abstract class NewsElement extends \Element {

	use \FilterElement;

	private static ?NewsModel $model = NULL;

	const DRAFT = 'draft';
	const READY = 'ready';

	public static function getSelection(): array {
		return News::model()->getProperties();
	}

	public static function model(): NewsModel {
		if(self::$model === NULL) {
			self::$model = new NewsModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('News::'.$failName, $arguments, $wrapper);
	}

}


class NewsModel extends \ModuleModel {

	protected string $module = 'website\News';
	protected string $package = 'website';
	protected string $table = 'websiteNews';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'website' => ['element32', 'website\Website', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'title' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'content' => ['editor24', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'status' => ['enum', [\website\News::DRAFT, \website\News::READY], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'publishedAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'website', 'farm', 'title', 'content', 'status', 'createdAt', 'publishedAt'
		]);

		$this->propertiesToModule += [
			'website' => 'website\Website',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['website']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return News::DRAFT;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): NewsModel {
		return parent::select(...$fields);
	}

	public function where(...$data): NewsModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): NewsModel {
		return $this->where('id', ...$data);
	}

	public function whereWebsite(...$data): NewsModel {
		return $this->where('website', ...$data);
	}

	public function whereFarm(...$data): NewsModel {
		return $this->where('farm', ...$data);
	}

	public function whereTitle(...$data): NewsModel {
		return $this->where('title', ...$data);
	}

	public function whereContent(...$data): NewsModel {
		return $this->where('content', ...$data);
	}

	public function whereStatus(...$data): NewsModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): NewsModel {
		return $this->where('createdAt', ...$data);
	}

	public function wherePublishedAt(...$data): NewsModel {
		return $this->where('publishedAt', ...$data);
	}


}


abstract class NewsCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): News {

		$e = new News();

		if(empty($id)) {
			News::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = News::getSelection();
		}

		if(News::model()
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
			$properties = News::getSelection();
		}

		if($sort !== NULL) {
			News::model()->sort($sort);
		}

		return News::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): News {

		return new News(['id' => NULL]);

	}

	public static function create(News $e): void {

		News::model()->insert($e);

	}

	public static function update(News $e, array $properties): void {

		$e->expects(['id']);

		News::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, News $e, array $properties): void {

		News::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(News $e): void {

		$e->expects(['id']);

		News::model()->delete($e);

	}

}


class NewsPage extends \ModulePage {

	protected string $module = 'website\News';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? NewsLib::getPropertiesCreate(),
		   $propertiesUpdate ?? NewsLib::getPropertiesUpdate()
		);
	}

}
?>