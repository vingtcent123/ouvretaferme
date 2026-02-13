<?php
namespace data;

Class DataLib extends DataCrud {

	use \ModuleDeferred;

	public static function deferred(): \Collection {

		$callback = fn() => Data::model()
			->select(Data::getSelection())
			->getCollection(index: 'fqn');

		return self::getCache('data', $callback);

	}

}
