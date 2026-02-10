<?php
namespace preaccounting;

Class InvoiceLib {

	public static function updateAccountingDifference(\selling\Invoice $eInvoice, string $accountingDifference): void {

		$update = ['accountingDifference' => $accountingDifference];

		$fw = new \FailWatch();

		$eInvoice->build(['accountingDifference'], $update);

		$fw->validate();

		if($eInvoice->isReadyForAccounting()) {
			$update['readyForAccounting'] = TRUE;
		}

		\selling\Invoice::model()->update($eInvoice, $update);

	}

	private static function getReadyForAccountingFields(\selling\Invoice $eInvoice, \bank\Cashflow $eCashflow): array {

		$updateFields = [];

		if($eInvoice['priceIncludingVat'] === $eCashflow['amount']) { // Si les montants sont identiques

			$updateFields['readyForAccounting'] = TRUE;

		} else if($eInvoice['accountingDifference'] === NULL) { // Ou si on n'a pas encore traité comment gérer la différence

			$updateFields['readyForAccounting'] = TRUE;
			$updateFields['accountingDifference'] = \selling\Invoice::AUTOMATIC;

		}

		$updateFields['accountingHash'] = $eCashflow['hash'];

		return $updateFields;
	}

	public static function setReadyForAccounting(\farm\Farm $eFarm): void {

		$cInvoice = \selling\Invoice::model()
			->select('id', 'cashflow', 'priceIncludingVat', 'accountingDifference')
			->where('m1.farm', $eFarm)
			->where('m1.status', 'NOT IN', [\selling\Invoice::DRAFT, \selling\Invoice::CANCELED])
			->where('paymentStatus IS NULL')
			->where('m1.accountingHash', NULL)
			->whereCashflow('!=', NULL)
			->whereReadyForAccounting(FALSE)
			->getCollection();

		$cCashflow = \bank\CashflowLib::getByIds($cInvoice->getColumnCollection('cashflow')->getIds(), index: 'id');

		foreach($cInvoice as $eInvoice) {

			$eCashflow = $cCashflow->offsetGet($eInvoice['cashflow']['id']);

			$updateFields = self::getReadyForAccountingFields($eInvoice, $eCashflow);

			if(count($updateFields) > 0) {
				\selling\Invoice::model()->update($eInvoice, $updateFields);
			}
		}

	}

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\InvoiceModel {

		return \selling\Invoice::model()
			->where('m1.status', 'NOT IN', [\selling\Invoice::DRAFT, \selling\Invoice::CANCELED])
			->where('paymentStatus IS NULL')
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->where('date BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')));

	}

	public static function countForAccountingPaymentCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)->count();

	}

	public static function countEligible(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)->count();

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): \Collection {

		return self::filterForAccountingCheck($eFarm, $search)
			->select(\selling\Invoice::getSelection())
			->where('m1.closed', FALSE)
			->sort(['date' => SORT_DESC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function countForAccounting(\farm\Farm $eFarm, \Search $search) {

		return self::filterForAccounting($eFarm, $search, TRUE)->count();

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport = FALSE) {

		return InvoiceLib::filterForAccounting($eFarm, $search, $forImport)
			->select([
				'id', 'date', 'number', 'document', 'farm',
				'priceExcludingVat', 'priceIncludingVat', 'vat', 'taxes', 'hasVat',
				'customer' => [
					'id', 'name', 'type', 'destination',
					'thirdParty' => \account\ThirdParty::model()
						->select('id')
						->delegateElement('customer')

				],
				'cashflow' => \bank\Cashflow::getSelection(),
				'accountingDifference', 'readyForAccounting', 'accountingHash',
				'cPayment' => \selling\PaymentTransactionLib::delegateByInvoice(),
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale')
					])
					->delegateCollection('invoice'),
			])
			->getCollection();

	}

	public static function filterForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \selling\InvoiceModel {

		// Pas de contrainte de date dans le cas d'un import => Les factures peuvent avoir été payées l'année d'après mais on veut quand même les importer
		// Attention, raisonnement tenable en compta de trésorerie et pas en compta d'engagement (ACCRUAL)
		if($forImport) {

			\selling\Invoice::model()
				->whereCashflow('!=', NULL)
				->whereAccountingHash(NULL)
				->whereReadyForAccounting(TRUE)
			;

		} else {

			\selling\Invoice::model()
				->where(
					'm1.date BETWEEN '.\selling\Invoice::model()->format($search->get('from')).' AND '.\selling\Invoice::model()->format($search->get('to')),
					if: $search->get('from') and $search->get('to')
			);

		}
		return \selling\Invoice::model()
			->join(\selling\Customer::model(), 'm1.customer = m2.id')
			->join(\bank\Cashflow::model(), 'm1.cashflow = m3.id', 'LEFT')
			->where('m1.status NOT IN ("'.\selling\Invoice::DRAFT.'", "'.\selling\Invoice::CANCELED.'")')
			->where('m1.paymentStatus IS NULL OR m1.paymentStatus != "'.\selling\Invoice::NEVER_PAID.'"')
			->where('m2.type = '.\selling\Customer::model()->format($search->get('type')), if: $search->get('type'))
			->where(fn() => 'm2.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->whereAccountingDifference('!=', NULL, if: $search->get('accountingDifference') === TRUE)
			->whereAccountingDifference('=', NULL, if: $search->get('accountingDifference') === FALSE)
		;

	}
}
