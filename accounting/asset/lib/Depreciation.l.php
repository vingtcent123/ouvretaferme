<?php
namespace asset;

class DepreciationLib extends \asset\DepreciationCrud {

	public static function getByAsset(Asset $eAsset): \Collection {

		return Depreciation::model()
			->select(Depreciation::getSelection())
			->whereAsset($eAsset)
			->getCollection();

	}
}

?>
