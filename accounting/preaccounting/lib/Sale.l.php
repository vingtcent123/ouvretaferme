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
			->where('m1.farm = '.$eFarm['id'])
			->where('deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}
	public static function countForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search): int {

		switch($type) {

			case 'delivered':
				return self::filterForAccountingCheck($eFarm, $search)
					->wherePreparationStatus('!=', \selling\Sale::DELIVERED)
					->count();

			case 'payment':
				return self::filterForAccountingCheck($eFarm, $search)
					->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
					->where('m2.id IS NULL')
					->count();

			case 'closed':
				return self::filterForAccountingCheck($eFarm, $search)
					->whereClosed(FALSE)
					->whereProfile('!=', \selling\Sale::SALE_MARKET)
					->count();

		}

	}

	public static function getForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search): array {

		$select = [
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

		$nToCheck = self::countForAccountingCheck($type, $eFarm, $search);

		$cSale = match($type) {
			'payment' => self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
				->where('m2.id IS NULL')
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id'),

			'closed' => self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->whereClosed(FALSE)
				->whereProfile('!=', \selling\Sale::SALE_MARKET)
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id'),

			'delivered' => self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->wherePreparationStatus('!=', \selling\Sale::DELIVERED)
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id'),
		};

		$nVerified = match($type) {
			'payment' => self::filterForAccountingCheck($eFarm, $search)
				->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
				->where('m2.id IS NOT NULL')
				->count(),

			'closed' => self::filterForAccountingCheck($eFarm, $search)
				->whereClosed(TRUE)
				->whereProfile('!=', \selling\Sale::SALE_MARKET)
				->count(),

			'delivered' => self::filterForAccountingCheck($eFarm, $search)
				->wherePreparationStatus(\selling\Sale::DELIVERED)
				->count(),
		};

		return [$nToCheck, $nVerified, $cSale];

	}
}
