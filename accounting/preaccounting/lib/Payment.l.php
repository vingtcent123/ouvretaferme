<?php
namespace preaccounting;

Class PaymentLib {

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\PaymentModel {

		return \selling\Payment::model()
			->whereStatus(\selling\Payment::NOT_PAID)
			->whereFarm($eFarm)
			->where('paidAt IS NULL OR paidAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')));

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): \Collection {

		return self::filterForAccountingCheck($eFarm, $search)
			->select(\selling\Payment::getSelection() + [
					'invoice' => ['id', 'number', 'document', 'date', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']],
					'sale' => ['id', 'document', 'deliveredAt', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']]
				])
			->getCollection(NULL, NULL, 'id');

	}
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): int {

		return (self::filterForAccountingCheck($eFarm, $search, $searchProblems)
			->select(['count' => new \Sql('COUNT(DISTINCT(m1.id))', 'int')])
			->get()['count'] ?? 0);

	}

	public static function getByHash(string $hash): \selling\Payment {

		return \selling\Payment::model()
			->select(\selling\Payment::getSelection() + [
					'invoice' => ['id', 'number', 'document', 'date', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']],
					'sale' => ['id', 'document', 'deliveredAt', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']]
				])
			->whereAccountingHash($hash)
			->get();

	}

	public static function updateAccountingDifference(\selling\Payment $ePayment, string $accountingDifference): void {

		$update = ['accountingDifference' => $accountingDifference];

		$fw = new \FailWatch();

		$ePayment->build(['accountingDifference'], $update);

		$fw->validate();

		if($ePayment->isAccountingReady()) {
			$update['accountingReady'] = TRUE;
		}

		\selling\Payment::model()->update($ePayment, $update);

	}

	public static function getPaymentSelection(): array {
		return \selling\Payment::getSelection() + [
			'sale' => \selling\Sale::getSelection() + [
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
					->delegateCollection('sale')
			],
			'invoice' => \selling\Invoice::getSelection() + [
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale')
					])
					->delegateCollection('invoice')
			],
			'cashflow' => \bank\Cashflow::getSelection() + ['account' => 'account']
		];
	}

	public static function filterForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \selling\PaymentModel {

		// Pas de contrainte de date dans le cas d'un import => Les factures peuvent avoir été payées l'année d'après mais on veut quand même les importer
		// Attention, raisonnement tenable en compta de trésorerie et pas en compta d'engagement (ACCRUAL)
		if($forImport) {

			\selling\Payment::model()
				->whereCashflow('!=', NULL)
				->whereAccountingHash(NULL)
				->whereAccountingReady(TRUE)
			;

		} else {

			\selling\Payment::model()
				->where(
					'm1.paidAt BETWEEN '.\selling\Payment::model()->format($search->get('from')).' AND '.\selling\Payment::model()->format($search->get('to')),
					if: $search->get('from') and $search->get('to')
			);

		}
		return \selling\Payment::model()
			->join(\bank\Cashflow::model(), 'm1.cashflow = m2.id', 'LEFT')
			->where('m1.status = '.\selling\Payment::model()->format(\selling\Payment::PAID))
			//->join(\selling\Customer::model(), 'm1.customer = m2.id')
			//->where('m1.status NOT IN ("'.\selling\Invoice::DRAFT.'", "'.\selling\Invoice::CANCELED.'")')
			//->where('m1.paymentStatus IS NULL OR m1.paymentStatus != "'.\selling\Invoice::NEVER_PAID.'"')
			//->where('m2.type = '.\selling\Customer::model()->format($search->get('type')), if: $search->get('type'))
			//->where(fn() => 'm2.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->whereAccountingDifference('!=', NULL, if: $search->get('accountingDifference') === TRUE)
			->whereAccountingDifference('=', NULL, if: $search->get('accountingDifference') === FALSE)
		;

	}

	public static function setAccountingReady(\farm\Farm $eFarm): void {

		$cPayment = \selling\Payment::model()
			->select(self::getPaymentSelection())
			->whereFarm($eFarm)
			->whereAccountingReady(FALSE)
			->whereStatus(\selling\Payment::PAID)
			->whereCashflow('!=', NULL)
			->whereAccountingHash(NULL)
			->getCollection();

		foreach($cPayment as $ePayment) {

			if($ePayment->acceptAccountingImport()) {
				$update = ['accountingReady' => TRUE];
				if($ePayment['amountIncludingVat'] !== $ePayment['cashflow']['amount']) {
					if(abs($ePayment['amountIncludingVat'] - $ePayment['cashflow']['amount']) < 1) {
						$update['accountingDifference'] = \selling\Payment::AUTOMATIC;
					} else {
						$update['accountingDifference'] = \selling\Payment::NOTHING;
					}
				}
				\selling\Payment::model()->update($ePayment, $update);
			}

		}

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
				'id', 'number', 'vatByRate', 'priceIncludingVat', 'taxes', 'hasVat', 'priceExcludingVat',
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
}
