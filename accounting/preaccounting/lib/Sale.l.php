<?php
namespace preaccounting;

Class SaleLib {

	const MARKET_PAYMENT_METHOD_FAKE_ID = 0;

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

	public static function countEligible(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)->count();

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search): \Collection {

		$selectSale = [
			'id', 'customer' => ['name', 'type', 'destination', 'user'], 'preparationStatus', 'priceIncludingVat',
			'deliveredAt', 'document', 'farm', 'profile', 'createdAt', 'taxes', 'hasVat', 'priceExcludingVat',
			'paymentStatus', 'closed', 'invoice',
			'shipping', 'shippingExcludingVat', 'shippingVatRate',
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
}
