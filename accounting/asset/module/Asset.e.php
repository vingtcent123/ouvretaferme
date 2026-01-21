<?php
namespace asset;

class Asset extends AssetElement {

	public function canManage(): bool {
		if($this->empty()) {
			return FALSE;
		}

		return TRUE;
	}

	public function acceptUdpate(): bool {

		return (AssetLib::hasAmortization($this) === FALSE);

	}

	public function acceptAttach(): bool {

		return ($this->exists() and \journal\Operation::model()->whereAsset($this)->count() === 0);

	}

	public function acceptDelete(): bool {

		return $this->acceptUdpate();

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

	public function isAmortizable(): bool {

		return $this['economicMode'] !== Asset::WITHOUT;

	}

	public function isTangible(): bool {

		return AssetLib::isTangibleAsset($this['accountLabel']);

	}

	public function isIntangible(): bool {

		return AssetLib::isIntangibleAsset($this['accountLabel']);

	}

	public function isTangibleLiving(): bool {

		return AssetLib::isTangibleLivingAsset($this['accountLabel']);

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
				'account' => \account\Account::getSelection(),
				'cAmortization' => Amortization::model()
	        ->select(Amortization::getSelection())
					->sort(['date' => SORT_ASC])
	        ->delegateCollection('asset'), // Ne pas modifier l'index (qui correspond à année 1, année 2 etc.)
				'cDepreciation' => Depreciation::model()
	        ->select(Depreciation::getSelection())
					->sort(['date' => SORT_ASC])
	        ->delegateCollection('asset'),
			];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.check', function(?\account\Account $eAccount): bool {

				if($eAccount->empty()) {
					return FALSE;
				}

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return (\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::ASSET_GENERAL_CLASS)
					or \account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::GRANT_ASSET_CLASS));

			})
			->setCallback('account.consistency', function(?\account\Account $eAccount) use($p): bool {

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return mb_substr($eAccount['class'], 0, 2) === mb_substr($this['accountLabel'], 0, 2);

			})
			->setCallback('accountLabel.check', function(?string $accountLabel): bool {

				return \account\AccountLabelLib::isFromClass($accountLabel, \account\AccountSetting::ASSET_GENERAL_CLASS)
					or \account\AccountLabelLib::isFromClass($accountLabel, \account\AccountSetting::GRANT_ASSET_CLASS);

			})
			->setCallback('type.consistency', function(?string $type) use($p): bool {

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				if(\account\AccountLabelLib::isFromClass($this['accountLabel'], \account\AccountSetting::GRANT_ASSET_CLASS)) {
					return $type === NULL or in_array($type, [Asset::WITHOUT]);
				}

				if(\account\AccountLabelLib::isFromClass($this['accountLabel'], \account\AccountSetting::ASSET_GENERAL_CLASS)) {
					return in_array($type, [Asset::LINEAR, Asset::WITHOUT, Asset::DEGRESSIVE]);
				}

				return FALSE;

			})
			->setCallback('amortizableBase.checkValue', function(?float $amortizableBase) use($p): bool {

				if($p->isBuilt('value') === FALSE or $amortizableBase === NULL) {
					return TRUE;
				}

				return($this['value'] >= $amortizableBase);

			})
			->setCallback('economicMode.incompatible', function(?string $economicMode) use($p): bool {

				if(
					$p->isBuilt('accountLabel') === FALSE or $economicMode === NULL or $economicMode === Asset::WITHOUT or
					AssetLib::isGrant($this['accountLabel'])
				) {
					return TRUE;
				}

				return AmortizationLib::isAmortizable($this);

			})
			->setCallback('economicDuration.unexpected', function(?int $economicDuration) use($p): bool {

				if(
					$p->isBuilt('economicMode') === FALSE or
					$this['economicMode'] === Asset::WITHOUT
				) {
					return TRUE;
				}

				return ((int)$economicDuration !== 0);

			})
			->setCallback('economicDuration.missing', function(?int $economicDuration) use($p): bool {

				if(
					$p->isBuilt('economicMode') === FALSE or
					$this['economicMode'] === Asset::WITHOUT
				) {
					return TRUE;
				}

				return ($economicDuration !== NULL);

			})
			->setCallback('economicDuration.degressive', function(?int $economicDuration) use($p): bool {

				if(
					$p->isBuilt('economicMode') === FALSE or
					$this['economicMode'] !== Asset::DEGRESSIVE
				) {
					return TRUE;
				}

				return ($economicDuration >= 36);

			})
			->setCallback('fiscalMode.incompatible', function(?string $fiscalMode) use($p): bool {

				if(
					$p->isBuilt('accountLabel') === FALSE or $fiscalMode === NULL or $fiscalMode === Asset::WITHOUT or
					AssetLib::isGrant($this['accountLabel'])
				) {
					return TRUE;
				}

				return AmortizationLib::isAmortizable($this);

			})
			->setCallback('fiscalDuration.unexpected', function(?int $fiscalDuration) use($p): bool {

				if(
					$p->isBuilt('fiscalMode') === FALSE or
					$this['fiscalMode'] === Asset::WITHOUT
				) {
					return TRUE;
				}

				return ((int)$fiscalDuration !== 0);

			})
			->setCallback('fiscalDuration.missing', function(?int $fiscalDuration) use($p): bool {

				if(
					$p->isBuilt('fiscalMode') === FALSE or
					$this['fiscalMode'] === Asset::WITHOUT
				) {
					return TRUE;
				}

				return ($fiscalDuration !== NULL);

			})
			->setCallback('fiscalDuration.degressive', function(?int $fiscalDuration) use($p): bool {

				if(
					$p->isBuilt('fiscalMode') === FALSE or
					$this['fiscalMode'] !== Asset::DEGRESSIVE
				) {
					return TRUE;
				}

				return ($fiscalDuration >= 36);

			})
			->setCallback('startDate.missing', function(?string $startDate) use($p): bool {

				if($p->isBuilt('economicMode') === FALSE or $this['economicMode'] !== Asset::LINEAR) {
					return TRUE;
				}

				return $startDate !== NULL;

			})
			->setCallback('startDate.inconsistency', function(?string $startDate) use($p): bool {

				if($p->isBuilt('acquisitionDate') === FALSE) {
					return TRUE;
				}

				return $startDate >= $this['acquisitionDate'];

			})
			->setCallback('resumeDate.inconsistent', function(?string $resumeDate) use($p): bool {

				if($resumeDate === NULL) {
					return TRUE;
				}

				if($p->isBuilt('acquisitionDate') === FALSE) {
					return TRUE;
				}

				if($this['acquisitionDate'] > $resumeDate) {
					return FALSE;
				}

				$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();
				foreach($cFinancialYear as $eFinancialYear) {
					if($resumeDate === $eFinancialYear['startDate']) {
						return TRUE;
					}
				}

				return FALSE;

			})
			->setCallback('economicAmortization.inconsistent', function(?float $economicAmortization) use($p): bool {

				if($p->isBuilt('value') === FALSE or $p->isBuilt('residualValue') === FALSE) {
					return TRUE;
				}

				return ($this['value'] - $this['residualValue']) >= $economicAmortization;

			})
			;
		parent::build($properties, $input, $p);
	}
}
?>
