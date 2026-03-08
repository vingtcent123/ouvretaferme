<?php
namespace data;

Class DataLib extends DataCrud {

	use \ModuleDeferred;

	/**
	 * @return \Collection<Data>
	 */
	public static function deferred(): \Collection {

		$callback = fn() => Data::model()
			->select(Data::getSelection())
			->getCollection(index: 'fqn');

		return self::getCache('data', $callback);

	}

}
