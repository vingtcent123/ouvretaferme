<?php
namespace preaccounting;

Class InvoiceLib {

	private static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\InvoiceModel {

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

	public static function getForExport(\farm\Farm $eFarm, \Search $search) {

		return \selling\Invoice::model()
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
					->delegateCollection('invoice', 'id'),
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate', 'deliveredAt', 'vat', 'vatByRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale'),
					])
					->delegateCollection('invoice'),
			])
			->join(\selling\Customer::model(), 'm1.customer = m2.id')
			->where('m1.status NOT IN ("'.\selling\Invoice::DRAFT.'", "'.\selling\Invoice::CANCELED.'")')
			->where(
				'm1.date BETWEEN '.\selling\Invoice::model()->format($search->get('from')).' AND '.\selling\Invoice::model()->format($search->get('to')),
				if: $search->get('from') and $search->get('to')
			)
			->where('m1.paymentStatus IS NULL OR m1.paymentStatus != "'.\selling\Invoice::NEVER_PAID.'"')
			->where('m2.type = '.\selling\Customer::model()->format($search->get('type')), if: $search->get('type'))
			->where(fn() => 'm2.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->getCollection();

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport = FALSE) {

		return self::filterForAccounting($eFarm, $search, $forImport)
			->select(\selling\Payment::getSelection() + [
				'sale' => [
					'id', 'document',
					'vatByRate', 'priceIncludingVat', 'taxes', 'hasVat', 'priceExcludingVat', 'shippingExcludingVat', 'shippingVatRate',
					'customer' => ['id', 'legalName', 'name', 'type', 'destination'],
					'cItem' => \selling\Item::model()
						->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
						->delegateCollection('sale'),
					'totalPaid' => new \selling\PaymentModel()
						->select('amountIncludingVat')
						->whereStatus(\selling\Payment::PAID)
						->delegateCollection('sale', callback: fn(\Collection $cPayment) => $cPayment->sum('amountIncludingVat'))
				],
				'invoice' => [
					'id', 'number', 'vatByRate', 'priceIncludingVat', 'taxes', 'hasVat', 'priceExcludingVat', 'document',
					'customer' => ['id', 'legalName', 'name', 'type', 'destination'],
					'cSale' => \selling\Sale::model()
						->select([
							'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
							'cItem' => \selling\Item::model()
								->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
								->delegateCollection('sale')
						])
						->delegateCollection('invoice'),
					'totalPaid' => new \selling\PaymentModel()
						->select('amountIncludingVat')
						->whereStatus(\selling\Payment::PAID)
						->delegateCollection('invoice', callback: fn(\Collection $cPayment) => $cPayment->sum('amountIncludingVat'))
				],
				'cashflow' => \bank\Cashflow::getSelection()
			])
			->getCollection();

	}

	public static function countForAccounting(\farm\Farm $eFarm, \Search $search) {

		return self::filterForAccounting($eFarm, $search, TRUE)->count();

	}

	private static function filterForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \selling\PaymentModel {

		if($forImport) {

			\selling\Payment::model()
				->whereCashflow('!=', NULL)
				->whereAccountingHash(NULL)
				->whereAccountingReady(TRUE)
			;

		}
		return \selling\Payment::model()
			->join(\bank\Cashflow::model(), 'm1.cashflow = m2.id', 'LEFT')
			->join(\selling\Customer::model(), 'm1.customer = m3.id', 'LEFT')
			->where('m1.farm = '.$eFarm['id'])
			->where(
				'm1.paidAt BETWEEN '.\selling\Payment::model()->format($search->get('from')).' AND '.\selling\Payment::model()->format($search->get('to')),
				if: $search->get('from') and $search->get('to'))
			->where('m1.status = '.\selling\Payment::model()->format(\selling\Payment::PAID))
			->where('m1.source = "'.\selling\Payment::INVOICE.'"')
			->where('m3.type = '.\selling\Customer::model()->format($search->get('customerType')), if: $search->get('customerType'))
			->where(fn() => 'm3.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->whereAccountingDifference('!=', NULL, if: $search->get('accountingDifference') === TRUE)
			->whereAccountingDifference('=', NULL, if: $search->get('accountingDifference') === FALSE)
		;

	}

}
