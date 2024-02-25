<?php
namespace series;

abstract class CommentElement extends \Element {

	use \FilterElement;

	private static ?CommentModel $model = NULL;

	public static function getSelection(): array {
		return Comment::model()->getProperties();
	}

	public static function model(): CommentModel {
		if(self::$model === NULL) {
			self::$model = new CommentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Comment::'.$failName, $arguments, $wrapper);
	}

}


class CommentModel extends \ModuleModel {

	protected string $module = 'series\Comment';
	protected string $package = 'series';
	protected string $table = 'seriesComment';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'cast' => 'element'],
			'text' => ['text8', 'min' => 1, 'max' => 250, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'user', 'series', 'cultivation', 'task', 'text', 'createdAt', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
			'series' => 'series\Series',
			'cultivation' => 'series\Cultivation',
			'task' => 'series\Task',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['task'],
			['series']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'user' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): CommentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CommentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CommentModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CommentModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): CommentModel {
		return $this->where('user', ...$data);
	}

	public function whereSeries(...$data): CommentModel {
		return $this->where('series', ...$data);
	}

	public function whereCultivation(...$data): CommentModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereTask(...$data): CommentModel {
		return $this->where('task', ...$data);
	}

	public function whereText(...$data): CommentModel {
		return $this->where('text', ...$data);
	}

	public function whereCreatedAt(...$data): CommentModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): CommentModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class CommentCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Comment {

		$e = new Comment();

		if(empty($id)) {
			Comment::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Comment::getSelection();
		}

		if(Comment::model()
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
			$properties = Comment::getSelection();
		}

		if($sort !== NULL) {
			Comment::model()->sort($sort);
		}

		return Comment::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Comment {

		return new Comment(['id' => NULL]);

	}

	public static function create(Comment $e): void {

		Comment::model()->insert($e);

	}

	public static function update(Comment $e, array $properties): void {

		$e->expects(['id']);

		Comment::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Comment $e, array $properties): void {

		Comment::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Comment $e): void {

		$e->expects(['id']);

		Comment::model()->delete($e);

	}

}


class CommentPage extends \ModulePage {

	protected string $module = 'series\Comment';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CommentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CommentLib::getPropertiesUpdate()
		);
	}

}
?>