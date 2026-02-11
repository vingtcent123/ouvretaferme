<?php
namespace preaccounting;

Class CashLib {

	public static function isActive(): bool {

		return (LIME_ENV === 'dev' and in_array(\user\ConnectionLib::getOnline()['id'], [1, 21]));

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search): \Collection {

		return self::filterForAccounting($eFarm, $search)
			->select(\cash\Cash::getSelection())
			->sort(['date' => SORT_DESC])
			->option('count')
			->getCollection(NULL, NULL, 'id');

	}

	private static function filterForAccounting(\farm\Farm $eFarm, \Search $search): \cash\CashModel {

		if($search->has('register') and $search->get('register')->notEmpty()) {

			\cash\Cash::model()
        ->whereRegister($search->get('register'))
			;

		}

		return \cash\Cash::model()
			->whereSource('!=', \cash\Cash::INITIAL)
			->where('date BETWEEN '.\cash\Cash::model()->format($search->get('from')).' AND '.\cash\Cash::model()->format($search->get('to')))
			->where(fn() => 'register IN ('.join(', ', $search->get('cRegisterFilter')->getIds()).')', if: $search->has('cRegisterFilter') and $search->get('cRegisterFilter')->notEmpty())
			// Ajouter un filtre sur le client
		;
		/*
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
			->whereInvoice(NULL)
			->where('m1.type = "'.\selling\Sale::PRIVATE.'"')
			->where(fn() => new \Sql('m1.customer = '.$search->get('customer')['id']), if: $search->get('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->where('m1.deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(m1.deliveredAt) < CURDATE()'));*/

	}
}
