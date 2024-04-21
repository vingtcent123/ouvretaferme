<?php
namespace shop;

abstract class ShopElement extends \Element {

	use \FilterElement;

	private static ?ShopModel $model = NULL;

	const WEEKLY = 'weekly';
	const BIMONTHLY = 'bimonthly';
	const MONTHLY = 'monthly';
	const OTHER = 'other';

	const OPEN = 'open';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return Shop::model()->getProperties();
	}

	public static function model(): ShopModel {
		if(self::$model === NULL) {
			self::$model = new ShopModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Shop::'.$failName, $arguments, $wrapper);
	}

}


class ShopModel extends \ModuleModel {

	protected string $module = 'shop\Shop';
	protected string $package = 'shop';
	protected string $table = 'shop';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'logo' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'email' => ['email', 'cast' => 'string'],
			'frequency' => ['enum', [\shop\Shop::WEEKLY, \shop\Shop::BIMONTHLY, \shop\Shop::MONTHLY, \shop\Shop::OTHER], 'cast' => 'enum'],
			'paymentCard' => ['bool', 'cast' => 'bool'],
			'paymentTransfer' => ['bool', 'cast' => 'bool'],
			'paymentTransferHow' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'paymentOffline' => ['bool', 'cast' => 'bool'],
			'paymentOfflineHow' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'description' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'terms' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'termsField' => ['bool', 'cast' => 'bool'],
			'orderMin' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'shipping' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'shippingUntil' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'status' => ['enum', [\shop\Shop::OPEN, \shop\Shop::CLOSED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'fqn', 'farm', 'logo', 'name', 'email', 'frequency', 'paymentCard', 'paymentTransfer', 'paymentTransferHow', 'paymentOffline', 'paymentOfflineHow', 'description', 'terms', 'termsField', 'orderMin', 'shipping', 'shippingUntil', 'status', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn'],
			['farm', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'frequency' :
				return Shop::WEEKLY;

			case 'paymentCard' :
				return FALSE;

			case 'paymentTransfer' :
				return FALSE;

			case 'paymentOffline' :
				return TRUE;

			case 'termsField' :
				return FALSE;

			case 'status' :
				return Shop::OPEN;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'frequency' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ShopModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ShopModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ShopModel {
		return $this->where('id', ...$data);
	}

	public function whereFqn(...$data): ShopModel {
		return $this->where('fqn', ...$data);
	}

	public function whereFarm(...$data): ShopModel {
		return $this->where('farm', ...$data);
	}

	public function whereLogo(...$data): ShopModel {
		return $this->where('logo', ...$data);
	}

	public function whereName(...$data): ShopModel {
		return $this->where('name', ...$data);
	}

	public function whereEmail(...$data): ShopModel {
		return $this->where('email', ...$data);
	}

	public function whereFrequency(...$data): ShopModel {
		return $this->where('frequency', ...$data);
	}

	public function wherePaymentCard(...$data): ShopModel {
		return $this->where('paymentCard', ...$data);
	}

	public function wherePaymentTransfer(...$data): ShopModel {
		return $this->where('paymentTransfer', ...$data);
	}

	public function wherePaymentTransferHow(...$data): ShopModel {
		return $this->where('paymentTransferHow', ...$data);
	}

	public function wherePaymentOffline(...$data): ShopModel {
		return $this->where('paymentOffline', ...$data);
	}

	public function wherePaymentOfflineHow(...$data): ShopModel {
		return $this->where('paymentOfflineHow', ...$data);
	}

	public function whereDescription(...$data): ShopModel {
		return $this->where('description', ...$data);
	}

	public function whereTerms(...$data): ShopModel {
		return $this->where('terms', ...$data);
	}

	public function whereTermsField(...$data): ShopModel {
		return $this->where('termsField', ...$data);
	}

	public function whereOrderMin(...$data): ShopModel {
		return $this->where('orderMin', ...$data);
	}

	public function whereShipping(...$data): ShopModel {
		return $this->where('shipping', ...$data);
	}

	public function whereShippingUntil(...$data): ShopModel {
		return $this->where('shippingUntil', ...$data);
	}

	public function whereStatus(...$data): ShopModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): ShopModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): ShopModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class ShopCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Shop {

		$e = new Shop();

		if(empty($id)) {
			Shop::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Shop::getSelection();
		}

		if(Shop::model()
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
			$properties = Shop::getSelection();
		}

		if($sort !== NULL) {
			Shop::model()->sort($sort);
		}

		return Shop::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Shop {

		$e = new Shop();

		if(empty($fqn)) {
			Shop::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Shop::getSelection();
		}

		if(Shop::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Shop::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Shop::getSelection();
		}

		return Shop::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Shop {

		return new Shop(['id' => NULL]);

	}

	public static function create(Shop $e): void {

		Shop::model()->insert($e);

	}

	public static function update(Shop $e, array $properties): void {

		$e->expects(['id']);

		Shop::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Shop $e, array $properties): void {

		Shop::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Shop $e): void {

		$e->expects(['id']);

		Shop::model()->delete($e);

	}

}


class ShopPage extends \ModulePage {

	protected string $module = 'shop\Shop';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ShopLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ShopLib::getPropertiesUpdate()
		);
	}

}
?>