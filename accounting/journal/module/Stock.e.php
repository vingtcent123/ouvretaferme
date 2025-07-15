<?php
namespace journal;

class Stock extends StockElement {

	public function canDelete(): bool {

		return ($this->exists() and $this['financialYear']->canUpdate());

	}

	public function canSet(): bool {

		return ($this->exists() and $this['reportedTo']->empty());

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
				'account' => \account\Account::getSelection(),
				'variationAccount' => \account\Account::getSelection(),
				'financialYear' => \account\FinancialYear::getSelection(),
			];

	}


	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.empty', function(?\account\Account $eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return $eAccount->notEmpty() and in_array($eAccount['class'], array_keys(\Setting::get('account\stockVariationClasses')));

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['account']);

				$eAccount = \account\AccountLib::getById($this['account']['id']);

				return str_starts_with($accountLabel, $eAccount['class']) === true;

			})
			->setCallback('variationAccount.empty', function(?\account\Account $eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return $eAccount->notEmpty() and in_array($eAccount['class'], \Setting::get('account\stockVariationClasses'));

			})
			->setCallback('variationAccountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['variationAccount']);

				$eAccount = \account\AccountLib::getById($this['variationAccount']['id']);

				return str_starts_with($accountLabel, $eAccount['class']) === true;

			})
			->setCallback('initialStock.empty', function(?float $amount): bool {

				return $amount !== NULL;

			})
			->setCallback('finalStock.empty', function(?float $amount): bool {

				return $amount !== NULL;

			})
			->setCallback('variation.empty', function(?float $amount): bool {

				$this->expects(['initialStock', 'finalStock']);

				return $amount === round($this['finalStock'] - $this['initialStock'], 2);

			})
			->setCallback('financialYear.check', function(?\account\FinancialYear $eFinancialYear): bool {

				$eFinancialYear = \account\FinancialYearLib::getById($eFinancialYear['id']);

				return $eFinancialYear->notEmpty() and $eFinancialYear->canUpdate();

			})
		;

		parent::build($properties, $input, $p);

	}
}
?>
