<?php
namespace preaccounting;

Class InvoiceLib {

	public static function setReadyForAccounting(\farm\Farm $eFarm): void {

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'cashflow', 'priceIncludingVat', 'accountingDifference',
				'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection() + ['cashflow' => \bank\Cashflow::getSelection()])
					->whereStatus(\selling\Payment::PAID)
					->whereCashflow('!=', NULL)
					->delegateCollection('sale', 'id')
			])
			->join(\selling\Payment::model(), 'm1.id = m2.invoice AND m2.status = "'.\selling\Payment::PAID.'" AND m2.cashflow IS NOT NULL AND m1.priceIncludingVat = m2.amountIncludingVat')
			->where('m1.farm', $eFarm)
			->where('m1.status', 'NOT IN', [\selling\Invoice::DRAFT, \selling\Invoice::CANCELED])
			->wherePaymentStatus(\selling\Invoice::PAID)
			->whereReadyForAccounting(FALSE)
			->getCollection();


		foreach($cInvoice as $eInvoice) {

			if($eInvoice['cPayment']->count() === 0) {
				continue;
			}

			if($eInvoice['cPayment']->count() !== 1 or $eInvoice['cPayment']->first()['amountIncludingVat'] !== $eInvoice['priceIncludingVat']) {
				continue;
			}

			$ePayment = $eInvoice['cPayment']->first();

			$updateFields = [];

			if($eInvoice['priceIncludingVat'] === $ePayment['cashflow']['amount']) { // Si les montants sont identiques

				$updateFields['readyForAccounting'] = TRUE;

			} else if($ePayment['accountingDifference'] === NULL) { // Ou si on n'a pas encore traité comment gérer la différence

				$updateFields['readyForAccounting'] = TRUE;
				$updateFields['accountingDifference'] = \selling\Invoice::AUTOMATIC;

			}

			\selling\Invoice::model()->update($eInvoice, $updateFields);
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

		return self::filterForAccounting($eFarm, $search, $forImport)
			->select([
				'id', 'date', 'number', 'document', 'farm',
				'priceExcludingVat', 'priceIncludingVat', 'vat', 'taxes', 'hasVat', 'vatByRate',
				'customer' => [
					'id', 'name', 'type', 'destination',
					'thirdParty' => \account\ThirdParty::model()
						->select('id')
						->delegateElement('customer')

				],
				'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection() + ['cashflow' => ['id', 'amount', 'account' => ['account']]])
					->whereStatus(\selling\Payment::PAID)
					->delegateCollection('sale', 'id'),
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate', 'deliveredAt', 'vat', 'vatByRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale'),
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
