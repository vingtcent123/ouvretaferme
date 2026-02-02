<?php
namespace cash;

class RegisterLib extends RegisterCrud {

	public static function getPropertiesCreate(): \Closure {
		return function() {

		};
	}

	public static function getPropertiesUpdate(): array {
		return ['color', 'account'];
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

}
?>
