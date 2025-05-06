<?php
namespace payment;

class MethodLib extends MethodCrud {

	const CARD = 'card';
	const ONLINE_CARD = 'online-card';
	const CASH = 'cash';
	const CHECK = 'check';
	const TRANSFER = 'transfer';

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Method::model()
       ->select(Method::getSelection())
       ->or(
	       fn() => $this->whereFarm($eFarm),
	       fn() => $this->whereFarm(NULL)
       )
			->sort(['name' => SORT_ASC])
       ->getCollection(NULL, NULL, 'id');

	}

}

