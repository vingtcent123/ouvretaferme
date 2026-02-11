<?php
namespace preaccounting;

Class InvoiceLib {

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\InvoiceModel {

		return \selling\Invoice::model()
			->whereStatus('NOT IN', [\selling\Invoice::DRAFT, \selling\Invoice::CANCELED])
			->wherePaymentStatus('=' , NULL)
			->wherePriceExcludingVat('!=', 0.0)
			->whereFarm($eFarm)
			->where('date BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')));

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): \Collection {

		return self::filterForAccountingCheck($eFarm, $search)
			->select(\selling\Invoice::getSelection())
			->whereClosed(FALSE)
			->sort(['date' => SORT_DESC])
			->getCollection(NULL, NULL, 'id');

	}
	public static function countForPaymentAccountingCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)
			->select(\selling\Invoice::getSelection())
			->whereClosed(FALSE)
			->count();

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
				//->whereCashflow('!=', NULL)
				//->whereAccountingHash(NULL)
				//->whereReadyForAccounting(TRUE)
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
			->where('m1.status NOT IN ("'.\selling\Invoice::DRAFT.'", "'.\selling\Invoice::CANCELED.'")')
			->where('m1.paymentStatus IS NULL OR m1.paymentStatus != "'.\selling\Invoice::NEVER_PAID.'"')
			->where('m2.type = '.\selling\Customer::model()->format($search->get('type')), if: $search->get('type'))
			->where(fn() => 'm2.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
		;

	}
}
