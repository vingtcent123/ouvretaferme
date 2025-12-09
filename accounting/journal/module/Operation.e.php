<?php
namespace journal;

use account\ThirdPartyLib;

class Operation extends OperationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'account' => \account\Account::getSelection(),
			'journalCode' => JournalCode::getSelection(),
			'vatAccount' => ['class', 'vatRate', 'description'],
			'thirdParty' => \account\ThirdParty::getSelection(),
			'paymentMethod' => \payment\Method::getSelection(),
			'financialYear' => \account\FinancialYear::getSelection(),
			'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
			'cOperationLinked' => new OperationModel()
				->select('id', 'operation')
				->delegateCollection('operation'),
			'createdBy' => ['id', 'firstName', 'lastName']
		];

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

	public function acceptUpdate(): bool {

		$this->expects(['hash']);

		return mb_substr($this['hash'], -1) !== JournalSetting::HASH_LETTER_RETAINED;

	}
	public function canUpdate(): bool {

		$this->expects(['date', 'financialYear', 'hash']);

		return
			\account\FinancialYearLib::isDateInOpenFinancialYear($this['date']) and
			$this['financialYear']->canUpdate() and
			$this['hash'] !== NULL;
	}

	public function canUpdateQuick(): bool {

		return $this->canUpdate() and $this['operation']->empty();
	}

	public function canDelete(): bool {

		$this->expects(['operation']);

		return (
			$this->notEmpty() and
			$this->canUpdate() and
			$this['operation']->empty() and
			($this['financialYear']->isAccrualAccounting() === FALSE or LetteringLib::isOperationLinkedInLettering($this) === FALSE)
		);

	}

	public function isClassAccount(int $class): bool {

		$this->expects(['accountLabel']);

		$stringClass = (string)$class;
		return str_starts_with($this['accountLabel'], $stringClass);

	}

	public function isVatAdjustement(?array $period): bool {

		if($period === NULL) {
			return FALSE;
		}

		return $this['date'] < $period['start'];

	}

	public function isFromImport(): bool {

		return in_array(substr($this['hash'], -1), [
			JournalSetting::HASH_LETTER_IMPORT_INVOICE, JournalSetting::HASH_LETTER_IMPORT_SALE, JournalSetting::HASH_LETTER_IMPORT_MARKET
		]);

	}

	public function importType(): ?string {

		if($this->isFromImport() === FALSE) {
			return NULL;
		}

		return substr($this['hash'], -1);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
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
			->setCallback('account.empty', function(?\account\Account $account): bool {

				return $account !== NULL;

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['account']);

				$eAccount = \account\AccountLib::getById($this['account']['id']);

				return \account\AccountLabelLib::isFromClass($accountLabel, $eAccount['class']);

			})
			->setCallback('date.empty', function(?string $date): bool {

				return $date !== NULL;

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
			->setCallback('thirdParty.empty', function(?\account\ThirdParty $eThirdParty): bool {

				return $eThirdParty !== NULL;

			})
			->setCallback('thirdParty.check', function(?\account\ThirdParty $eThirdParty): bool {

				if($eThirdParty->empty()) {
					return TRUE;
				}

				return ThirdPartyLib::getById($eThirdParty['id'])->notEmpty();

			})
			->setCallback('cashflow.check', function(?\bank\Cashflow $eCashflow): bool {

				if($eCashflow->exists() === FALSE) {
					return TRUE;
				}

				$eCashflow = \bank\CashflowLib::getById($eCashflow['id']);

				return $eCashflow->exists();

			})
			->setCallback('paymentDate.empty', function(?string $paymentDate) use ($p): bool {

				$this->expects(['financialYear']);

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				if($this['financialYear']->isAccrualAccounting() or $this['financialYear']->isCashAccrualAccounting()) {
					return TRUE;
				}

				return $paymentDate !== NULL;
			})
			->setCallback('paymentMethod.empty', function(?string $paymentDate): bool {

				$this->expects(['financialYear']);

				if($this['financialYear']->isAccrualAccounting()) {
					return TRUE;
				}

				return $paymentDate !== NULL;
			})
			->setCallback('document.empty', function(?string $document) use($input): bool {

				if(!var_filter($input['invoiceFile'] ?? NULL, 'string')) {
					return TRUE;
				}

				$ePartner = \account\DropboxLib::getPartner();
				if($ePartner->empty()) {
					return TRUE;
				}

				return $document !== NULL;
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
			->setCallback('asset.check', function(?\asset\Asset $eAsset) use($input, $p): bool {

				if($p->isBuilt('accountLabel') === FALSE) {
					return TRUE;
				}

				if(\asset\AssetLib::isAsset($this['accountLabel']) === FALSE) {
					return TRUE;
				}

				return $eAsset->notEmpty();

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
