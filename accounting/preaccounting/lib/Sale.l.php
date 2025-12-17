<?php
namespace preaccounting;

Class SaleLib {

	/**
	 * Récupère toutes les ventes concernées
	 *  - si elles ont un moyen de paiement
	 *  - si elles sont clôturées
	 * Vérifie si tous leurs items ont un account
	 * Set ReadyForAccounting à TRUE
	 */
	public static function setReadyForAccountingSaleCollection(\Collection $cSale): void {

		// Re-récupération de toutes ces ventes qui réunissent le critère moyen de paiement et clôturée
		$cSaleFiltered = \selling\Sale::model()
			->select(['id', 'items', 'count' => new \Sql('COUNT(*)')])
			->join(\selling\Item::model(), 'm1.id = m2.sale', 'LEFT')
			->join(\selling\Payment::model(), 'm1.id = m3.sale AND (m3.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR m3.onlineStatus IS NULL)', 'LEFT') // Moyen de paiement OK
			->where('m1.closed = 1') // vente clôturée OK
			->where('m2.account IS NOT NULL') // Exclusion des items avec account à NULL
			->where('m3.id IS NOT NULL') // Moyen de paiement existe
			->where('m1.id IN ('.join(',', $cSale->getIds()).')')
			->where('m1.readyForAccounting = 0')
			->group('m1_id')
			->having('m1_count = m1_items')
			->getCollection();

		if($cSaleFiltered->notEmpty()) {

			\selling\Sale::model()
				->whereId('IN', $cSaleFiltered->getIds())
				->update(['readyForAccounting' => TRUE]);

		}

	}

	public static function setReadyForAccountingByProducts(\Collection $cProduct): void {

		// Ventes qui contiennent ces produits
		$cSale = \selling\Sale::model()
			->select(['id'])
			->join(\selling\Item::model(), 'm1.id = m2.sale', 'LEFT')
			->whereReadyForAccounting(FALSE)
			->where('m2.product IN ('.join(', ', $cProduct->getIds()).')')
			->getCollection();

		if($cSale->notEmpty()) {
			self::setReadyForAccountingSaleCollection($cSale);
		}

		// Factures qui contiennent ces produits
		$cInvoiceFiltered = \selling\Sale::model()
			->select(['invoice', 'total' => new \Sql('COUNT(*)'), 'countOk' => new \Sql('SUM(IF(m1.readyForAccounting = 1, 1, 0))')])
			->join(\selling\Item::model(), 'm1.id = m2.sale', 'LEFT')
			->whereInvoice('!=', NULL)
			->where('m2.product IN ('.join(', ', $cProduct->getIds()).')')
			->group('invoice')
			->having('m1_total = m1_countOk')
			->getCollection()
			->getColumnCollection('invoice');

		if($cInvoiceFiltered->notEmpty()) {

			\selling\Invoice::model()
        ->whereId('IN', $cInvoiceFiltered->getIds())
        ->update(['readyForAccounting' => TRUE]);
		}

	}

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
			->whereStatus('IN', [\selling\Invoice::GENERATED, \selling\Invoice::DELIVERED])
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->where('date BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(date) < CURDATE()'));

	}
	public static function countForAccountingPaymentCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)
			->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
			->where('m2.id IS NULL')
			->whereProfile(\selling\Sale::SALE)
			->whereInvoice(NULL)
			->count();

	}
	public static function countForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): array {

		switch($type) {

			case 'payment':
				$cSale = self::filterForAccountingCheck($eFarm, $search)
					->select(['count' => new \Sql('COUNT(*)'), 'profile'])
					->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
					->where('m2.id IS NULL', if: $searchProblems === TRUE)
					->where('m2.id IS NOT NULL', if: $searchProblems === FALSE)
					->group('profile')
					->getCollection();
				break;

			case 'closed':
				$cSale = self::filterForAccountingCheck($eFarm, $search)
					->select(['count' => new \Sql('COUNT(*)'), 'profile'])
					->whereClosed(FALSE, if: $searchProblems === TRUE)
					->whereClosed(TRUE, if: $searchProblems === FALSE)
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
		if($type === 'closed' and $nInvoice > 0) {
			$count['invoice'] = $nInvoice;
		}

		return $count;

	}

	public static function getForPaymentAccountingCheck(\farm\Farm $eFarm, \Search $search): array {

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

		$nToCheck = self::countForAccountingPaymentCheck($eFarm, $search);

		$cSale = self::filterForAccountingCheck($eFarm, $search)
			->select($selectSale)
			->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
			->where('m2.id IS NULL')
			->whereProfile(\selling\Sale::SALE)
			->whereInvoice(NULL)
			->sort(['deliveredAt' => SORT_DESC])
			->getCollection(NULL, NULL, 'id');

		$nVerified = self::filterForAccountingCheck($eFarm, $search)
			->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
			->where('m2.id IS NOT NULL')
			->whereProfile(\selling\Sale::SALE)
			->whereInvoice(NULL)
			->count();

		return [$nToCheck, $nVerified, $cSale];

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
