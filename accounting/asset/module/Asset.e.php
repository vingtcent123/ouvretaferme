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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('startDate.empty', function(?string $startDate): bool {

				$this->expects(['type']);

				return $this['type'] === Asset::GRANT ? $startDate === NULL : $startDate !== NULL;

			})
			->setCallback('accountLabel.check', function(?string $accountLabel): bool {

				return \account\ClassLib::isFromClass($accountLabel, \Setting::get('account\assetClass'))
					or \account\ClassLib::isFromClass($accountLabel, \Setting::get('account\subventionAssetClass'));

			})
			->setCallback('account.check', function(?\account\Account $eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return (\account\ClassLib::isFromClass($eAccount['class'], \Setting::get('account\assetClass'))
					or \account\ClassLib::isFromClass($eAccount['class'], \Setting::get('account\subventionAssetClass')));

			})
			->setCallback('account.consistency', function(?\account\Account $eAccount): bool {

				$this->expects(['accountLabel']);
				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return mb_substr($eAccount['class'], 0, 2) === mb_substr($this['accountLabel'], 0, 2);

			})
			->setCallback('type.consistency', function(?string $type): bool {

				$this->expects(['accountLabel']);

				if(\account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\subventionAssetClass'))) {
					return $type === Asset::GRANT;
				}

				if(\account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\assetClass'))) {
					return $type !== Asset::GRANT;
				}

				return FALSE;

			})
			->setCallback('grant.check', function(?Asset $eAsset): bool {

				$this->expects(['accountLabel']);

				$eAsset = AssetLib::getById($eAsset['id']);

				return $eAsset['type'] === Asset::GRANT
					and $eAsset['asset']->empty()
					and \account\ClassLib::isFromClass($this['accountLabel'], \Setting::get('account\assetClass'));

			})
			;
		parent::build($properties, $input, $p);
	}
}
?>
