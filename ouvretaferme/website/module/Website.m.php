<?php
namespace website;

abstract class WebsiteElement extends \Element {

	use \FilterElement;

	private static ?WebsiteModel $model = NULL;

	const PENDING = 'pending';
	const CONFIGURED_UNSECURED = 'configured-unsecured';
	const PINGED_UNSECURED = 'pinged-unsecured';
	const FAILURE_UNSECURED = 'failure-unsecured';
	const CERTIFICATE_CREATED = 'certificate-created';
	const FAILURE_CERTIFICATE_CREATED = 'failure-certificate-created';
	const CONFIGURED_SECURED = 'configured-secured';
	const PINGED_SECURED = 'pinged-secured';
	const FAILURE_SECURED = 'failure-secured';

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Website::model()->getProperties();
	}

	public static function model(): WebsiteModel {
		if(self::$model === NULL) {
			self::$model = new WebsiteModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Website::'.$failName, $arguments, $wrapper);
	}

}


class WebsiteModel extends \ModuleModel {

	protected string $module = 'website\Website';
	protected string $package = 'website';
	protected string $table = 'website';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'internalDomain' => ['text8', 'min' => 1, 'max' => NULL, 'unique' => TRUE, 'cast' => 'string'],
			'domain' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'domainStatus' => ['enum', [\website\Website::PENDING, \website\Website::CONFIGURED_UNSECURED, \website\Website::PINGED_UNSECURED, \website\Website::FAILURE_UNSECURED, \website\Website::CERTIFICATE_CREATED, \website\Website::FAILURE_CERTIFICATE_CREATED, \website\Website::CONFIGURED_SECURED, \website\Website::PINGED_SECURED, \website\Website::FAILURE_SECURED], 'null' => TRUE, 'cast' => 'enum'],
			'logo' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'favicon' => ['textFixed', 'min' => 30, 'max' => 30, 'null' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'min' => 1, 'max' => 40, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => 200, 'null' => TRUE, 'cast' => 'string'],
			'customDesign' => ['element32', 'website\Design', 'cast' => 'element'],
			'customColor' => ['color', 'cast' => 'string'],
			'customDisabledFooter' => ['bool', 'cast' => 'bool'],
			'customTitleFont' => ['text8', 'cast' => 'string'],
			'customFont' => ['text8', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\website\Website::ACTIVE, \website\Website::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'internalDomain', 'domain', 'domainStatus', 'logo', 'favicon', 'name', 'description', 'customDesign', 'customColor', 'customDisabledFooter', 'customTitleFont', 'customFont', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'customDesign' => 'website\Design',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm'],
			['internalDomain'],
			['domain']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'customDesign' :
				return \Setting::get('website\designDefaultId');

			case 'customColor' :
				return "#505075";

			case 'customDisabledFooter' :
				return FALSE;

			case 'customTitleFont' :
				return "'PT Serif', serif";

			case 'customFont' :
				return "'PT Serif', serif";

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Website::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'domainStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): WebsiteModel {
		return parent::select(...$fields);
	}

	public function where(...$data): WebsiteModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): WebsiteModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): WebsiteModel {
		return $this->where('farm', ...$data);
	}

	public function whereInternalDomain(...$data): WebsiteModel {
		return $this->where('internalDomain', ...$data);
	}

	public function whereDomain(...$data): WebsiteModel {
		return $this->where('domain', ...$data);
	}

	public function whereDomainStatus(...$data): WebsiteModel {
		return $this->where('domainStatus', ...$data);
	}

	public function whereLogo(...$data): WebsiteModel {
		return $this->where('logo', ...$data);
	}

	public function whereFavicon(...$data): WebsiteModel {
		return $this->where('favicon', ...$data);
	}

	public function whereName(...$data): WebsiteModel {
		return $this->where('name', ...$data);
	}

	public function whereDescription(...$data): WebsiteModel {
		return $this->where('description', ...$data);
	}

	public function whereCustomDesign(...$data): WebsiteModel {
		return $this->where('customDesign', ...$data);
	}

	public function whereCustomColor(...$data): WebsiteModel {
		return $this->where('customColor', ...$data);
	}

	public function whereCustomDisabledFooter(...$data): WebsiteModel {
		return $this->where('customDisabledFooter', ...$data);
	}

	public function whereCustomTitleFont(...$data): WebsiteModel {
		return $this->where('customTitleFont', ...$data);
	}

	public function whereCustomFont(...$data): WebsiteModel {
		return $this->where('customFont', ...$data);
	}

	public function whereCreatedAt(...$data): WebsiteModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): WebsiteModel {
		return $this->where('status', ...$data);
	}


}


abstract class WebsiteCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Website {

		$e = new Website();

		if(empty($id)) {
			Website::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Website::getSelection();
		}

		if(Website::model()
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
			$properties = Website::getSelection();
		}

		if($sort !== NULL) {
			Website::model()->sort($sort);
		}

		return Website::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Website {

		return new Website(['id' => NULL]);

	}

	public static function create(Website $e): void {

		Website::model()->insert($e);

	}

	public static function update(Website $e, array $properties): void {

		$e->expects(['id']);

		Website::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Website $e, array $properties): void {

		Website::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Website $e): void {

		$e->expects(['id']);

		Website::model()->delete($e);

	}

}


class WebsitePage extends \ModulePage {

	protected string $module = 'website\Website';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? WebsiteLib::getPropertiesCreate(),
		   $propertiesUpdate ?? WebsiteLib::getPropertiesUpdate()
		);
	}

}
?>