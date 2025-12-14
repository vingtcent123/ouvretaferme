<?php
namespace preaccounting;

Class SaleLib {

	public static function filterForAccounting(\farm\Farm $eFarm, \Search $search, bool $forImport): \selling\SaleModel {

		return \selling\Sale::model()
			->wherePreparationStatus('NOT IN', [\selling\Sale::COMPOSITION, \selling\Sale::CANCELED, \selling\Sale::EXPIRED, \selling\Sale::DRAFT, \selling\Sale::BASKET])
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->where('deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')), if: $search->get('from') and $search->get('to'))
			->whereReadyForAccounting(TRUE, if: $forImport)
			->whereId($search->get('id'), if: $search->get('id'))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\SaleModel {

		return \selling\Sale::model()
			->wherePreparationStatus('NOT IN', [\selling\Sale::COMPOSITION, \selling\Sale::CANCELED, \selling\Sale::EXPIRED, \selling\Sale::DRAFT, \selling\Sale::BASKET])
			->where('priceExcludingVat != 0.0')
			->whereProfile('NOT IN', [\selling\Sale::MARKET])
			->whereInvoice(NULL)
			->where('m1.farm = '.$eFarm['id'])
			->where('deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}

	public static function filterInvoiceForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\InvoiceModel {

		return \selling\Invoice::model()
			->whereStatus(\selling\Invoice::GENERATED)
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->where('date BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(date) < CURDATE()'));

	}
	public static function countForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search): array {

		switch($type) {

			case 'payment':
				$cSale = self::filterForAccountingCheck($eFarm, $search)
					->select(['count' => new \Sql('COUNT(*)'), 'profile'])
					->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
					->where('m2.id IS NULL')
					->group('profile')
					->getCollection();
				break;

			case 'closed':
				$cSale = self::filterForAccountingCheck($eFarm, $search)
					->select(['count' => new \Sql('COUNT(*)'), 'profile'])
					->whereClosed(FALSE)
					->whereProfile('!=', \selling\Sale::SALE_MARKET)
					->group('profile')
					->getCollection();
				$nInvoice = self::filterInvoiceForAccountingCheck($eFarm, $search)
					->whereClosed(FALSE)
          ->count();
				break;

		}

		$count = [];
		foreach($cSale as $eSale) {
			$count[$eSale['profile']] = $eSale['count'];
		}
		if($type === 'closed') {
			$count['invoice'] = $nInvoice;
		}

		return $count;

	}

	public static function getForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search): array {

		$selectSale = [
			'id', 'customer' => ['name', 'type', 'destination'], 'preparationStatus', 'priceIncludingVat',
			'deliveredAt', 'document', 'farm', 'profile', 'createdAt', 'taxes', 'hasVat', 'priceExcludingVat',
			'onlinePaymentStatus', 'paymentStatus', 'closed',
			'marketParent' => ['customer' => ['name', 'type', 'destination']],
			'cPayment' => \selling\Payment::model()
			->select(\selling\Payment::getSelection())
			->or(
				fn() => $this->whereOnlineStatus(NULL),
				fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
			)
			->delegateCollection('sale', 'id')
		];

		$selectInvoice = \selling\Invoice::getSelection();

		$nToCheck = self::countForAccountingCheck($type, $eFarm, $search);

		if(!$search->get('tab')) {
			$search->set('tab', first(array_keys($nToCheck)));
		}

		if($search->get('tab') === 'invoice') {

			$cInvoice = self::filterInvoiceForAccountingCheck($eFarm, $search)
				->select($selectInvoice)
				->whereClosed(FALSE)
				->sort(['date' => SORT_DESC])
				->getCollection(NULL, NULL, 'id');

			$cSale = new \Collection();

		} else {

			$cSale = match($type) {
				'payment' => self::filterForAccountingCheck($eFarm, $search)
					->select($selectSale)
					->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
					->where('m2.id IS NULL')
					->whereProfile($search->get('tab'), if: $search->get('tab'))
					->sort(['deliveredAt' => SORT_DESC])
					->getCollection(NULL, NULL, 'id'),

				'closed' => self::filterForAccountingCheck($eFarm, $search)
					->select($selectSale)
					->whereClosed(FALSE)
					->whereProfile($search->get('profile'), if: $search->get('profile'))
					->sort(['deliveredAt' => SORT_DESC])
					->getCollection(NULL, NULL, 'id'),
			};

			$cInvoice = new \Collection();
		}

		$nVerified = match($type) {
			'payment' => self::filterForAccountingCheck($eFarm, $search)
				->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
				->where('m2.id IS NOT NULL')
				->count(),

			'closed' => self::filterForAccountingCheck($eFarm, $search)
				->whereClosed(TRUE)
				->whereProfile('!=', \selling\Sale::SALE_MARKET)
				->group('profile')
				->count() +
				self::filterInvoiceForAccountingCheck($eFarm, $search)
	       ->select($selectInvoice)
	       ->whereStatus(\selling\Invoice::GENERATED)
	       ->count(),

		};

		return [$nToCheck, $nVerified, $cSale, $cInvoice];

	}
}
