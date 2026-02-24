<?php
namespace preaccounting;

Class SaleLib {

	const MARKET_PAYMENT_METHOD_FAKE_ID = 0;

	// Renvoie toutes les ventes payées incluses dans un paiement ou dans une facture elle-même incluse dans un paiement
	public static function getPaidSaleIdsForPeriod(\farm\Farm $eFarm, string $from, string $to): array {

		// Ventes issues de ventes payées
		$saleIdsFromInvoice = \selling\Payment::model()
			->join(\selling\Sale::model(), 'm1.invoice = m2.invoice')
			->where('m1.invoice IS NOT NULL')
			->where('m1.farm = '.$eFarm['id'])
			->where('m1.paidAt BETWEEN '.\selling\Item::model()->format($from).' AND '.\selling\Item::model()->format($to))
			->where('m1.status = "'.\selling\Payment::PAID.'"')
			->where('m2.id IS NOT NULL')
			->getColumn(new \Sql('m2.id', 'int'));

		// Ventes issues de factures payées
		$saleIdsFromSale = \selling\Payment::model()
			->join(\selling\Sale::model(), 'm1.sale = m2.id')
			->where('m1.sale IS NOT NULL')
			->where('m1.status = "'.\selling\Payment::PAID.'"')
			->where('m1.farm = '.$eFarm['id'])
			->where('m1.paidAt BETWEEN '.\selling\Item::model()->format($from).' AND '.\selling\Item::model()->format($to))
			->where('m2.id IS NOT NULL')
			->getColumn(new \Sql('m2.id', 'int'));

		return array_merge($saleIdsFromInvoice, $saleIdsFromSale);

	}

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\SaleModel {

		\selling\Sale::model()
			->join(\selling\Customer::model(), 'm1.customer = m2.id')
			->join(\selling\Payment::model(), 'm1.id = m3.sale AND m3.status = '.\selling\Payment::model()->format(\selling\Payment::PAID), 'LEFT'); // Moyen de paiement OK

		if($search->get('method') and $search->get('method')->notEmpty()) {

			\selling\Sale::model()
				->where(fn() => new \Sql('m3.method = '.$search->get('method')['id']), if: $search->get('method')->notEmpty() and $search->get('method')['id'] !== self::MARKET_PAYMENT_METHOD_FAKE_ID)
				->whereProfile('IN', [\selling\Sale::SALE, \selling\Sale::MARKET], if: ($search->get('method')->empty() or $search->get('method')['id'] !== self::MARKET_PAYMENT_METHOD_FAKE_ID))
				->whereProfile('=', \selling\Sale::MARKET, if: $search->get('method')->notEmpty() and $search->get('method')['id'] === self::MARKET_PAYMENT_METHOD_FAKE_ID);

		}

		return \selling\Sale::model()
			->wherePreparationStatus(\selling\Sale::DELIVERED)
			->where('priceExcludingVat != 0.0')
			->where('m1.invoice IS NULL')
			->where('m1.type = "'.\selling\Sale::PRIVATE.'"')
			->where(fn() => new \Sql('m1.customer = '.$search->get('customer')['id']), if: $search->get('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->where('m1.deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(m1.deliveredAt) < CURDATE()'));

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search): \Collection {

		$selectSale = [
			'id', 'customer' => ['name', 'type', 'destination', 'user', 'document'], 'preparationStatus',
			'deliveredAt', 'document', 'farm', 'profile', 'createdAt', 'taxes',
			'hasVat', 'priceExcludingVat', 'priceIncludingVat', 'vat', 'vatByRate',
			'shipping', 'shippingExcludingVat', 'shippingVatRate',
			'paymentStatus', 'closed', 'invoice',
			'marketParent' => ['customer' => ['name', 'type', 'destination']],
			'shopDate' => ['id', 'deliveryDate', 'status', 'orderStartAt', 'orderEndAt'], 'createdBy',
			'cPayment' => \selling\Payment::model()
				->select(\selling\Payment::getSelection())
				->whereStatus(\selling\Payment::PAID)
				->delegateCollection('sale', 'id'),
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
					->delegateCollection('sale')
		];

		return self::filterForAccountingCheck($eFarm, $search)
			->select($selectSale)
			->sort(['m1_deliveredAt' => SORT_DESC])
			->option('count')
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByInvoiceForFec(\selling\Invoice $eInvoice): \Collection {

		return \selling\Sale::model()
			->select([
				'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate', 'deliveredAt', 'vat', 'vatByRate',
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
					->delegateCollection('sale'),
			])
			->whereInvoice($eInvoice)
			->getCollection();

	}

}
