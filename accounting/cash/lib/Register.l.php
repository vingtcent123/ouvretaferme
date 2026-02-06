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

}
?>
