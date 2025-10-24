<?php
namespace journal;

abstract class OperationElement extends \Element {

	use \FilterElement;

	private static ?OperationModel $model = NULL;

	const ACH = 'ach';
	const VEN = 'ven';
	const BAN = 'ban';
	const KS = 'ks';
	const OD = 'od';

	const DEBIT = 'debit';
	const CREDIT = 'credit';

	const PARTIAL = 'partial';
	const TOTAL = 'total';

	public static function getSelection(): array {
		return Operation::model()->getProperties();
	}

	public static function model(): OperationModel {
		if(self::$model === NULL) {
			self::$model = new OperationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Operation::'.$failName, $arguments, $wrapper);
	}

}


class OperationModel extends \ModuleModel {

	protected string $module = 'journal\Operation';
	protected string $package = 'journal';
	protected string $table = 'journalOperation';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'number' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'journalCode' => ['enum', [\journal\Operation::ACH, \journal\Operation::VEN, \journal\Operation::BAN, \journal\Operation::KS, \journal\Operation::OD], 'null' => TRUE, 'cast' => 'enum'],
			'account' => ['element32', 'account\Account', 'cast' => 'element'],
			'accountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'thirdParty' => ['element32', 'account\ThirdParty', 'null' => TRUE, 'cast' => 'element'],
			'date' => ['date', 'min' => toDate('NOW - 2 YEARS'), 'max' => toDate('NOW + 1 YEARS'), 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'document' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'documentDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'documentStorage' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'type' => ['enum', [\journal\Operation::DEBIT, \journal\Operation::CREDIT], 'cast' => 'enum'],
			'vatRate' => ['decimal', 'digits' => 5, 'decimal' => 2, 'cast' => 'float'],
			'vatAccount' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'operation' => ['element32', 'journal\Operation', 'null' => TRUE, 'cast' => 'element'],
			'asset' => ['element32', 'asset\Asset', 'null' => TRUE, 'cast' => 'element'],
			'comment' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'paymentDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'paymentMethod' => ['element32', 'payment\Method', 'null' => TRUE, 'cast' => 'element'],
			'letteringStatus' => ['enum', [\journal\Operation::PARTIAL, \journal\Operation::TOTAL], 'null' => TRUE, 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'number', 'financialYear', 'journalCode', 'account', 'accountLabel', 'thirdParty', 'date', 'description', 'document', 'documentDate', 'documentStorage', 'invoice', 'amount', 'type', 'vatRate', 'vatAccount', 'operation', 'asset', 'comment', 'paymentDate', 'paymentMethod', 'letteringStatus', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'account' => 'account\Account',
			'thirdParty' => 'account\ThirdParty',
			'invoice' => 'selling\Invoice',
			'vatAccount' => 'account\Account',
			'operation' => 'journal\Operation',
			'asset' => 'asset\Asset',
			'paymentMethod' => 'payment\Method',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['document']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'vatRate' :
				return 0;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'journalCode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'letteringStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): OperationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): OperationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): OperationModel {
		return $this->where('id', ...$data);
	}

	public function whereNumber(...$data): OperationModel {
		return $this->where('number', ...$data);
	}

	public function whereFinancialYear(...$data): OperationModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereJournalCode(...$data): OperationModel {
		return $this->where('journalCode', ...$data);
	}

	public function whereAccount(...$data): OperationModel {
		return $this->where('account', ...$data);
	}

	public function whereAccountLabel(...$data): OperationModel {
		return $this->where('accountLabel', ...$data);
	}

	public function whereThirdParty(...$data): OperationModel {
		return $this->where('thirdParty', ...$data);
	}

	public function whereDate(...$data): OperationModel {
		return $this->where('date', ...$data);
	}

	public function whereDescription(...$data): OperationModel {
		return $this->where('description', ...$data);
	}

	public function whereDocument(...$data): OperationModel {
		return $this->where('document', ...$data);
	}

	public function whereDocumentDate(...$data): OperationModel {
		return $this->where('documentDate', ...$data);
	}

	public function whereDocumentStorage(...$data): OperationModel {
		return $this->where('documentStorage', ...$data);
	}

	public function whereInvoice(...$data): OperationModel {
		return $this->where('invoice', ...$data);
	}

	public function whereAmount(...$data): OperationModel {
		return $this->where('amount', ...$data);
	}

	public function whereType(...$data): OperationModel {
		return $this->where('type', ...$data);
	}

	public function whereVatRate(...$data): OperationModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereVatAccount(...$data): OperationModel {
		return $this->where('vatAccount', ...$data);
	}

	public function whereOperation(...$data): OperationModel {
		return $this->where('operation', ...$data);
	}

	public function whereAsset(...$data): OperationModel {
		return $this->where('asset', ...$data);
	}

	public function whereComment(...$data): OperationModel {
		return $this->where('comment', ...$data);
	}

	public function wherePaymentDate(...$data): OperationModel {
		return $this->where('paymentDate', ...$data);
	}

	public function wherePaymentMethod(...$data): OperationModel {
		return $this->where('paymentMethod', ...$data);
	}

	public function whereLetteringStatus(...$data): OperationModel {
		return $this->where('letteringStatus', ...$data);
	}

	public function whereCreatedAt(...$data): OperationModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): OperationModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): OperationModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class OperationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Operation {

		$e = new Operation();

		if(empty($id)) {
			Operation::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Operation::getSelection();
		}

		if(Operation::model()
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
			$properties = Operation::getSelection();
		}

		if($sort !== NULL) {
			Operation::model()->sort($sort);
		}

		return Operation::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Operation {

		return new Operation(['id' => NULL]);

	}

	public static function create(Operation $e): void {

		Operation::model()->insert($e);

	}

	public static function update(Operation $e, array $properties): void {

		$e->expects(['id']);

		Operation::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Operation $e, array $properties): void {

		Operation::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Operation $e): void {

		$e->expects(['id']);

		Operation::model()->delete($e);

	}

}


class OperationPage extends \ModulePage {

	protected string $module = 'journal\Operation';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? OperationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? OperationLib::getPropertiesUpdate()
		);
	}

}
?>