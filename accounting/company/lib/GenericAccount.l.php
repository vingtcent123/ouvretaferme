<?php
namespace company;

class GenericAccountLib extends GenericAccountCrud {

	public static function getByClass(string $class, string $type = GenericAccount::AGRICULTURAL): GenericAccount {

		return GenericAccount::model()
			->select(GenericAccount::getSelection())
			->whereClass($class)
			->whereType($type)
			->get();

	}
}
?>
