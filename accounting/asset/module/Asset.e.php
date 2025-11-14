<?php
namespace asset;

class Asset extends AssetElement {

	public function canManage(): bool {
		if($this->empty()) {
			return FALSE;
		}

		return TRUE;
	}

	public function canView(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return TRUE;
	}

	public function canDispose(): bool {

		return $this->canManage() and $this['status'] === Asset::ONGOING;
	}

	public function isTangible(): bool {

		return AssetLib::isTangibleAsset($this['accountLabel']);

	}


	public function isIntangible(): bool {

		return AssetLib::isIntangibleAsset($this['accountLabel']);

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
				'asset' => ['account', 'accountLabel', 'description', 'duration', 'value', 'type', 'startDate', 'endDate', 'acquisitionDate'],
				'grant' => ['account', 'accountLabel', 'description', 'duration', 'value', 'type', 'startDate', 'endDate', 'acquisitionDate'],
				'account' => \account\Account::getSelection(),
				'cAmortization' => Amortization::model()
	        ->select(Amortization::getSelection())
					->sort(['date' => SORT_ASC])
	        ->delegateCollection('asset') // Ne pas modifier l'index (qui correspond à année 1, année 2 etc.)
			];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			/*->setCallback('startDate.empty', function(?string $startDate): bool {

				$this->expects(['type']);

				return in_array($this['type'], [Asset::LINEAR, Asset::DEGRESSIVE]) ? $startDate !== NULL : TRUE;

			})*/
			->setCallback('account.check', function(?\account\Account $eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return (\account\ClassLib::isFromClass($eAccount['class'], \account\AccountSetting::ASSET_GENERAL_CLASS)
					or \account\ClassLib::isFromClass($eAccount['class'], \account\AccountSetting::GRANT_ASSET_CLASS));

			})
			->setCallback('account.consistency', function(?\account\Account $eAccount) use($p): bool {

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return mb_substr($eAccount['class'], 0, 2) === mb_substr($this['accountLabel'], 0, 2);

			})
			->setCallback('accountLabel.check', function(?string $accountLabel): bool {

				return \account\ClassLib::isFromClass($accountLabel, \account\AccountSetting::ASSET_GENERAL_CLASS)
					or \account\ClassLib::isFromClass($accountLabel, \account\AccountSetting::GRANT_ASSET_CLASS);

			})
			->setCallback('type.consistency', function(?string $type) use($p): bool {

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				if(\account\ClassLib::isFromClass($this['accountLabel'], \account\AccountSetting::GRANT_ASSET_CLASS)) {
					return $type === NULL or in_array($type, [Asset::WITHOUT]);
				}

				if(\account\ClassLib::isFromClass($this['accountLabel'], \account\AccountSetting::ASSET_GENERAL_CLASS)) {
					return in_array($type, [Asset::LINEAR, Asset::WITHOUT, Asset::DEGRESSIVE]);
				}

				return FALSE;

			})
			->setCallback('amortizableBase.check', function(float $amortizableBase) use($p): bool {

				if($p->isBuilt('value') === FALSE) {
					return TRUE;
				}

				return($this['value'] >= $amortizableBase);

			})
			/*->setCallback('grant.check', function(?Asset $eAsset) use($p): bool {

				if($eAsset->empty() or $p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				$this->expects(['accountLabel', 'type']);

				$eAsset = AssetLib::getById($eAsset['id']);

				return ($eAsset['type'] === NULL)
					and $eAsset['asset']->empty()
					and \account\ClassLib::isFromClass($this['accountLabel'], \account\AccountSetting::ASSET_GENERAL_CLASS);

			})
			->setCallback('asset.check', function(?Asset $eAsset) use($p): bool {

				if($eAsset->empty() or $p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				$this->expects(['accountLabel', 'type']);

				$eAsset = AssetLib::getById($eAsset['id']);

				return (in_array($eAsset['type'], [Asset::LINEAR, Asset::DEGRESSIVE]))
					and $eAsset['grant']->empty()
					and \account\ClassLib::isFromClass($this['accountLabel'], \account\AccountSetting::GRANT_ASSET_CLASS);

			})*/
			;
		parent::build($properties, $input, $p);
	}
}
?>
