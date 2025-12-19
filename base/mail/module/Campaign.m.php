<?php
namespace mail;

abstract class CampaignElement extends \Element {

	use \FilterElement;

	private static ?CampaignModel $model = NULL;

	const PERIOD = 'period';
	const SHOP = 'shop';
	const GROUP = 'group';
	const NEWSLETTER = 'newsletter';

	const CONFIRMED = 'confirmed';
	const SENT = 'sent';

	public static function getSelection(): array {
		return Campaign::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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
			'source' => ['enum', [\mail\Campaign::PERIOD, \mail\Campaign::SHOP, \mail\Campaign::GROUP, \mail\Campaign::NEWSLETTER], 'null' => TRUE, 'cast' => 'enum'],
			'sourceShop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'sourceGroup' => ['element32', 'selling\CustomerGroup', 'null' => TRUE, 'cast' => 'element'],
			'sourcePeriod' => ['int8', 'min' => 1, 'max' => 12, 'null' => TRUE, 'cast' => 'int'],
			'to' => ['json', 'cast' => 'array'],
			'subject' => ['text8', 'min' => 1, 'max' => 100, 'cast' => 'string'],
			'content' => ['editor16', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'consent' => ['json', 'cast' => 'array'],
			'limited' => ['json', 'cast' => 'array'],
			'scheduled' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'sent' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'delivered' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'opened' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'failed' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'spam' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'status' => ['enum', [\mail\Campaign::CONFIRMED, \mail\Campaign::SENT], 'cast' => 'enum'],
			'scheduledAt' => ['datetime', 'cast' => 'string'],
			'sentAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'source', 'sourceShop', 'sourceGroup', 'sourcePeriod', 'to', 'subject', 'content', 'consent', 'limited', 'scheduled', 'sent', 'delivered', 'opened', 'failed', 'spam', 'status', 'scheduledAt', 'sentAt', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'sourceShop' => 'shop\Shop',
			'sourceGroup' => 'selling\CustomerGroup',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'consent' :
				return [];

			case 'limited' :
				return [];

			case 'scheduled' :
				return 0;

			case 'sent' :
				return 0;

			case 'delivered' :
				return 0;

			case 'opened' :
				return 0;

			case 'failed' :
				return 0;

			case 'spam' :
				return 0;

			case 'status' :
				return Campaign::CONFIRMED;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'source' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'to' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'consent' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'limited' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'to' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'consent' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'limited' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

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

	public function whereSource(...$data): CampaignModel {
		return $this->where('source', ...$data);
	}

	public function whereSourceShop(...$data): CampaignModel {
		return $this->where('sourceShop', ...$data);
	}

	public function whereSourceGroup(...$data): CampaignModel {
		return $this->where('sourceGroup', ...$data);
	}

	public function whereSourcePeriod(...$data): CampaignModel {
		return $this->where('sourcePeriod', ...$data);
	}

	public function whereTo(...$data): CampaignModel {
		return $this->where('to', ...$data);
	}

	public function whereSubject(...$data): CampaignModel {
		return $this->where('subject', ...$data);
	}

	public function whereContent(...$data): CampaignModel {
		return $this->where('content', ...$data);
	}

	public function whereConsent(...$data): CampaignModel {
		return $this->where('consent', ...$data);
	}

	public function whereLimited(...$data): CampaignModel {
		return $this->where('limited', ...$data);
	}

	public function whereScheduled(...$data): CampaignModel {
		return $this->where('scheduled', ...$data);
	}

	public function whereSent(...$data): CampaignModel {
		return $this->where('sent', ...$data);
	}

	public function whereDelivered(...$data): CampaignModel {
		return $this->where('delivered', ...$data);
	}

	public function whereOpened(...$data): CampaignModel {
		return $this->where('opened', ...$data);
	}

	public function whereFailed(...$data): CampaignModel {
		return $this->where('failed', ...$data);
	}

	public function whereSpam(...$data): CampaignModel {
		return $this->where('spam', ...$data);
	}

	public function whereStatus(...$data): CampaignModel {
		return $this->where('status', ...$data);
	}

	public function whereScheduledAt(...$data): CampaignModel {
		return $this->where('scheduledAt', ...$data);
	}

	public function whereSentAt(...$data): CampaignModel {
		return $this->where('sentAt', ...$data);
	}

	public function whereCreatedAt(...$data): CampaignModel {
		return $this->where('createdAt', ...$data);
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