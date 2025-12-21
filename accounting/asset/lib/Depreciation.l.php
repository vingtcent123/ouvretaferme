<?php
namespace asset;

class DepreciationLib extends \asset\DepreciationCrud {

	public static function getByAsset(Asset $eAsset): \Collection {

		return Depreciation::model()
			->select(Depreciation::getSelection())
			->whereAsset($eAsset)
			->getCollection();

	}

	public static function deleteByAsset(Asset $eAsset): void {

		if($eAsset->exists() === FALSE) {
			return;
		}

		Depreciation::model()
			->whereAsset($eAsset)
			->delete();

	}
}

?>
