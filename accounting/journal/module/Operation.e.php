<?php
namespace journal;

class Operation extends OperationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'operation' => ['id', 'asset', 'accountLabel'],
			'account' => \account\Account::getSelection(),
			'journalCode' => fn($e) => $e->notEmpty() ? \journal\JournalCodeLib::ask($e['journalCode'], \farm\Farm::getConnected()) : new JournalCode(),
			'vatAccount' => ['class', 'vatRate', 'description'],
			'thirdParty' => \account\ThirdParty::getSelection(),
			'paymentMethod' => \payment\Method::getSelection(),
			'financialYear' => \account\FinancialYear::getSelection(),
			'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
			'createdBy' => ['id', 'firstName', 'lastName'],
			'cashflow' => \bank\Cashflow::model()
				->select(\bank\Cashflow::getSelection())
				->delegateElement('hash', propertyParent: 'hash'),
			'cOperationHash' => \journal\Operation::model()
			->select(['id', 'hash', 'accountLabel', 'asset', 'type', 'amount', 'number', 'financialYear' => ['id', 'status', 'closeDate']])
			->delegateCollection('hash', propertyParent: 'hash'),
		];

	}

	public function acceptNewAsset(): bool {

		return $this['asset']->empty() and (\asset\AssetLib::isAsset($this['accountLabel']) or \asset\AssetLib::isGrant($this['accountLabel']));

	}

	public function acceptDeferral(): bool {

		$cDeferral = Deferral::model()
			->select([
				'status',
				'count' => new \Sql('COUNT(*)')
			])
			->whereOperation($this)
			->group('status')
			->getCollection(NULL, NULL, 'status');

		return (
			$cDeferral->empty() and
			(($cDeferral[Deferral::PLANNED] ?? 0) === 0) and
			(($cDeferral[Deferral::RECORDED] ?? 0) === 0) and
			(($cDeferral[Deferral::DEFERRED] ?? 0) === 0) and
			(\account\AccountLabelLib::isChargeClass($this['accountLabel']) or \account\AccountLabelLib::isProductClass($this['accountLabel'])) and
			$this['financialYear']->acceptUpdate()
		);

	}

	public function acceptWrite(): bool {

		return $this['id'] === NULL or (
			mb_substr($this['hash'], -1) !== JournalSetting::HASH_LETTER_RETAINED and
			$this['financialYear']->acceptUpdate() and
			$this['number'] === NULL
		);

	}

	public function isNotLinkedToAsset(): bool {

		return $this['cOperationHash']->notEmpty() and $this['cOperationHash']->getColumnCollection('asset')->find(fn($e) => $e->notEmpty())->empty();

	}

	public function acceptDelete(): bool {
		return ($this->acceptWrite() and $this->isNotLinkedToAsset());
	}

	public static function validateBatch(\Collection $cOperation): void {

		foreach($cOperation as $eOperation) {

			$eOperation->validate('acceptWrite');

		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('journalCode.check', function(?JournalCode &$eJournalCode): bool {

				if($eJournalCode->empty()) {
					return TRUE;
				}

				$eJournalCode = JournalCodeLib::getById($eJournalCode['id']);

				return $eJournalCode->notEmpty();

			})
			->setCallback('financialYear.check', function(?\account\FinancialYear &$eFinancialYear): bool {

				if($eFinancialYear === NULL or $eFinancialYear->empty()) {
					return FALSE;
				}

				$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();

				if(in_array($eFinancialYear['id'], $cFinancialYear->getIds())) {
					$eFinancialYear = $cFinancialYear->find(fn ($e) => $e['id'] === $eFinancialYear['id'])->first();
					return TRUE;
				}

				return FALSE;

			})
			->setCallback('account.notExists', function(\account\Account &$eAccount): bool {

				$eAccount = \account\AccountLib::getById($eAccount['id'] ?? NULL);
				if($eAccount->notEmpty() and $eAccount['vatAccount']->notEmpty()) {
					$eAccount['vatAccount'] = \account\AccountLib::getById($eAccount['vatAccount']['id']);
				}

				return $eAccount->notEmpty();

			})
			->setCallback('accountLabel.format', function(?string &$accountLabel): bool {

				if(mb_strlen($accountLabel) > 8) {
					return FALSE;
				}

				$accountLabel = \account\AccountLabelLib::pad($accountLabel);

				return TRUE;

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel) use ($p): bool {

				if($p->isInvalid('account')) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($this['account']['id']);

				return \account\AccountLabelLib::isFromClass($accountLabel, $eAccount['class']);

			})
			->setCallback('date.empty', function(?string $date): bool {

				return $date !== NULL;

			})
			->setCallback('date.locked', function(?string &$date) use ($p): bool {

				if($p->isBuilt('financialYear') === FALSE) {
					return TRUE;
				}

				$lastValidatedDate = OperationLib::getLastValidationDate($this['financialYear']);
				if($lastValidatedDate === NULL or $lastValidatedDate <= $date) {
					return TRUE;
				}

				throw new \FailException('journal\Operation::date.locked', [$lastValidatedDate]);

			})
			->setCallback('description.empty', function(?string $description): bool {

				return $description !== NULL;

			})
			->setCallback('amount.empty', function(?float $amount): bool {

				return $amount !== NULL;

			})
			->setCallback('type.empty', function(?string $type): bool {

				return $type !== NULL;

			})
			->setCallback('date.check', function(string $date): bool {

				$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();

				foreach($cFinancialYear as $eFinancialYear) {

					if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
						return TRUE;
					}

				}

				return FALSE;

			})
			->setCallback('thirdParty.unknown', function(?\account\ThirdParty &$eThirdParty): bool {

				if($eThirdParty->empty() and ($this['journalCode']->empty() or $this['journalCode']['code'] !== JournalSetting::JOURNAL_CODE_OD_BILAN)) {
					return FALSE;
				}

				// Pas de tiers pour les opérations de bilan
				if($eThirdParty->empty() and ($this['journalCode']->empty() or $this['journalCode']['code'] === JournalSetting::JOURNAL_CODE_OD_BILAN)) {
					return TRUE;
				}

				$eThirdParty = \account\ThirdPartyLib::getById($eThirdParty['id']);
				return $eThirdParty->notEmpty();

			})
			->setCallback('cashflow.check', function(?\bank\Cashflow $eCashflow): bool {

				if($eCashflow->exists() === FALSE) {
					return TRUE;
				}

				$eCashflow = \bank\CashflowLib::getById($eCashflow['id']);

				return $eCashflow->exists();

			})
			->setCallback('invoice.check', function(?\selling\Invoice $eInvoice) use($input): bool {

				if($eInvoice->empty()) {
					return TRUE;
				}

				$eInvoice->expects(['id']);

				$eInvoice = \selling\InvoiceLib::getById($eInvoice['id']);
				$eFarm = new \farm\Farm(['id' => POST('farm', '?int')]);

				return $eInvoice['farm']->is($eFarm);

			})
			->setCallback('amount.negative', function(float $amount): bool {

				return $amount > 0.0;

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
