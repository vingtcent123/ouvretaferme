<?php
namespace cash;

class RegisterLib extends RegisterCrud {

	public static function getPropertiesCreate(): array {
		return ['color', 'account', 'paymentMethod'];
	}

	public static function getPropertiesUpdate(): array {

		return ['color', 'account', 'bankAccount'];

	}

	public static function getAll(): \Collection {

		return Register::model()
			->select(Register::getSelection())
			->sort([
				new \Sql('FIELD(status, "'.Register::ACTIVE.'", "'.Register::INACTIVE.'") ASC'),
				'id' => SORT_ASC
			])
			->getCollection(index: 'id');

	}

	public static function countPending(Register $e): Cash {

		static $count = Cash::model()
			->select([
				'draft' => new \Sql('SUM(status = "'.Cash::DRAFT.'")', 'int'),
				'balance' => new \Sql('SUM(source = "'.Cash::BALANCE.'")', 'int'),
				'firstDraft' => new \Sql('MIN(date)', 'string'),
				'lastDraft' => new \Sql('MAX(date)', 'string'),
			])
			->whereRegister($e)
			->whereStatus(Cash::DRAFT)
			->get();

		$count['draft'] ??= 0;
		$count['balance'] ??= 0;
		$count['firstDraft'] ??= NULL;
		$count['lastDraft'] ??= NULL;

		return $count;

	}

	public static function update(Register $e, array $properties): void {

		Register::model()->beginTransaction();

			parent::update($e, $properties);

			if(
				in_array('status', $properties) and
				$e['status'] === Register::INACTIVE
			) {
				CashLib::deleteWaiting($e);
			}

		Register::model()->commit();

	}

	public static function close(Register $e, string $date): void {

		Register::model()->beginTransaction();

			// Vérifications de sécurité dans la transaction pour garantir la consistence des données
			if(
				Cash::model()
					->whereRegister($e)
					->whereStatus(Cash::DRAFT)
					->whereDate('<=', $date)
					->exists()
			) {
				Register::model()->rollBack();
				return;
			}

			Register::model()
				->where('closedAt', '<', $date)
				->update([
					'closedAt' => $date
				]);

		Register::model()->commit();

	}

}
?>
