<?php
namespace cash;

class RegisterLib extends RegisterCrud {

	public static function getPropertiesCreate(): array {
		return ['account', 'paymentMethod'];
	}

	public static function getAll(): \Collection {

		return Register::model()
			->select(Register::getSelection())
			->sort(['id' => SORT_ASC])
			->getCollection();

	}

}
?>
