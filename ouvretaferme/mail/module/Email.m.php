<?php
namespace mail;

abstract class EmailElement extends \Element {

	use \FilterElement;

	private static ?EmailModel $model = NULL;

	const WAITING = 'waiting';
	const SENDING = 'sending';
	const FAIL = 'fail';
	const SUCCESS = 'success';

	public static function getSelection(): array {
		return Email::model()->getProperties();
	}

	public static function model(): EmailModel {
		if(self::$model === NULL) {
			self::$model = new EmailModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Email::'.$failName, $arguments, $wrapper);
	}

}


class EmailModel extends \ModuleModel {

	protected string $module = 'mail\Email';
	protected string $package = 'mail';
	protected string $table = 'mailEmail';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'html' => ['text24', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'text' => ['text24', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'subject' => ['text24', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
			'server' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'fromEmail' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'fromName' => ['text8', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
			'to' => ['json', 'cast' => 'array'],
			'cc' => ['json', 'cast' => 'array'],
			'bcc' => ['json', 'cast' => 'array'],
			'replyTo' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'attachments' => ['binary32', 'cast' => 'binary'],
			'status' => ['enum', [\mail\Email::WAITING, \mail\Email::SENDING, \mail\Email::FAIL, \mail\Email::SUCCESS], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'sentAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'html', 'text', 'subject', 'server', 'fromEmail', 'fromName', 'to', 'cc', 'bcc', 'replyTo', 'attachments', 'status', 'createdAt', 'sentAt'
		]);

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['status']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Email::WAITING;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'to' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'cc' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'bcc' :
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

			case 'cc' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'bcc' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): EmailModel {
		return parent::select(...$fields);
	}

	public function where(...$data): EmailModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): EmailModel {
		return $this->where('id', ...$data);
	}

	public function whereHtml(...$data): EmailModel {
		return $this->where('html', ...$data);
	}

	public function whereText(...$data): EmailModel {
		return $this->where('text', ...$data);
	}

	public function whereSubject(...$data): EmailModel {
		return $this->where('subject', ...$data);
	}

	public function whereServer(...$data): EmailModel {
		return $this->where('server', ...$data);
	}

	public function whereFromEmail(...$data): EmailModel {
		return $this->where('fromEmail', ...$data);
	}

	public function whereFromName(...$data): EmailModel {
		return $this->where('fromName', ...$data);
	}

	public function whereTo(...$data): EmailModel {
		return $this->where('to', ...$data);
	}

	public function whereCc(...$data): EmailModel {
		return $this->where('cc', ...$data);
	}

	public function whereBcc(...$data): EmailModel {
		return $this->where('bcc', ...$data);
	}

	public function whereReplyTo(...$data): EmailModel {
		return $this->where('replyTo', ...$data);
	}

	public function whereAttachments(...$data): EmailModel {
		return $this->where('attachments', ...$data);
	}

	public function whereStatus(...$data): EmailModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): EmailModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereSentAt(...$data): EmailModel {
		return $this->where('sentAt', ...$data);
	}


}


abstract class EmailCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Email {

		$e = new Email();

		if(empty($id)) {
			Email::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Email::getSelection();
		}

		if(Email::model()
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
			$properties = Email::getSelection();
		}

		if($sort !== NULL) {
			Email::model()->sort($sort);
		}

		return Email::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Email {

		return new Email(['id' => NULL]);

	}

	public static function create(Email $e): void {

		Email::model()->insert($e);

	}

	public static function update(Email $e, array $properties): void {

		$e->expects(['id']);

		Email::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Email $e, array $properties): void {

		Email::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Email $e): void {

		$e->expects(['id']);

		Email::model()->delete($e);

	}

}


class EmailPage extends \ModulePage {

	protected string $module = 'mail\Email';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? EmailLib::getPropertiesCreate(),
		   $propertiesUpdate ?? EmailLib::getPropertiesUpdate()
		);
	}

}
?>