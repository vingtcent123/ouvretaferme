<?php
namespace asset;

class Asset extends AssetElement {

	public function canManage(): bool {
		if($this->empty()) {
			return FALSE;
		}

		if($this['status'] !== AssetElement::ONGOING) {
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

	public static function getSelection(): array {

		return parent::getSelection() + [
				'asset' => ['account', 'accountLabel', 'duration', 'value', 'type', 'startDate', 'endDate', 'acquisitionDate'],
				'grant' => ['account', 'accountLabel', 'duration', 'value', 'type', 'startDate', 'endDate', 'acquisitionDate'],
				'account' => \account\Account::getSelection(),
			];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('startDate.empty', function(?string $startDate): bool {

				$this->expects(['type']);

				return in_array($this['type'], [Asset::LINEAR, Asset::DEGRESSIVE]) ? $startDate !== NULL : TRUE;

			})
			->setCallback('accountLabel.check', function(?string $accountLabel): bool {

				return \account\ClassLib::isFromClass($accountLabel, \Setting::get('account\assetClass'))
					or \account\ClassLib::isFromClass($accountLabel, \Setting::get('account\grantAssetClass'));

			})
			->setCallback('account.check', function(?\account\Account $eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return (\account\ClassLib::isFromClass($eAccount['class'], \Setting::get('account\assetClass'))
					or \account\ClassLib::isFromClass($eAccount['class'], \Setting::get('account\grantAssetClass')));

			})
			->setCallback('account.consistency', function(?\account\Account $eAccount): bool {

				$this->expects(['accountLabel']);
				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return mb_substr($eAccount['class'], 0, 2) === mb_substr($this['accountLabel'], 0, 2);

			})
			->setCallback('type.consistency', function(?string $type): bool {

				$this->expects(['accountLabel']);

				if(\account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\grantAssetClass'))) {
					return $type === NULL or in_array($type, [Asset::WITHOUT, Asset::GRANT_RECOVERY]);
				}

				if(\account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\assetClass'))) {
					return in_array($type, [Asset::LINEAR, Asset::WITHOUT, Asset::DEGRESSIVE]);
				}

				return FALSE;

			})
			->setCallback('grant.check', function(?Asset $eAsset): bool {

				if($eAsset->empty()) {
					return TRUE;
				}

				$this->expects(['accountLabel']);

				$eAsset = AssetLib::getById($eAsset['id']);

				return ($eAsset['type'] === NULL or $eAsset['type'] === Asset::GRANT_RECOVERY)
					and $eAsset['asset']->empty()
					and \account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\assetClass'));

			})
			->setCallback('asset.check', function(?Asset $eAsset): bool {

				if($eAsset->empty()) {
					return TRUE;
				}

				$this->expects(['accountLabel']);

				$eAsset = AssetLib::getById($eAsset['id']);

				return (in_array($eAsset['type'], [Asset::LINEAR, Asset::DEGRESSIVE]))
					and $eAsset['grant']->empty()
					and \account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\grantAssetClass'));

			})
			;
		parent::build($properties, $input, $p);
	}
}
?>
