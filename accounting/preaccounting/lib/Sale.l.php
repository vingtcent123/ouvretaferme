<?php
namespace preaccounting;

Class SaleLib {

	/**
	 * Récupère toutes les ventes concernées
	 *  - si elles ont un moyen de paiement
	 *  - si elles sont clôturées
	 *  - si leur nombre d'items OK est égal au nombre d'items enregistrés
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
			->where('m1.readyForAccounting = 0') // Uniquement des ventes non déjà prêtes
			->where('m1.accountingHash IS NULL')
			->group('m1_id')
			->having('m1_count = m1_items') // Nombre d'items OK  = nombre d'items totaux
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
			->whereAccountingHash(NULL)
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
			->where('m1.accountingHash IS NULL')
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
			->join(\selling\Customer::model(), 'm1.customer = m2.id')
			->where(fn() => new \Sql('JSON_CONTAINS('.\selling\Customer::model()->field('groups').', \''.$search->get('group')['id'].'\')'), if: $search->get('group')->notEmpty())
			->join(\selling\Payment::model(), 'm1.id = m3.sale AND (m3.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR m3.onlineStatus IS NULL)', 'LEFT') // Moyen de paiement OK
			->where(fn() => new \Sql('m3.method = '.$search->get('method')['id']), if: $search->get('method')->notEmpty())
			->wherePreparationStatus('NOT IN', [\selling\Sale::COMPOSITION, \selling\Sale::CANCELED, \selling\Sale::EXPIRED, \selling\Sale::DRAFT, \selling\Sale::BASKET])
			->where('priceExcludingVat != 0.0')
			->whereInvoice(NULL)
			->where('m1.type = "'.\selling\Sale::PRIVATE.'"')
			->where(fn() => new \Sql('m1.customer = '.$search->get('customer')['id']), if: $search->get('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->whereReadyForAccounting(FALSE)
			->whereProfile(\selling\Sale::SALE)
			->where('deliveredAt BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}

	public static function applyConditions(string $type, bool $searchProblems): ?\selling\SaleModel {

		switch($type) {

			case 'payment':
				return \selling\Sale::model()
          ->join(\selling\Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.\selling\Payment::model()->format(\selling\Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
					->where('m2.id IS NULL', if: $searchProblems === TRUE)
					->where('m2.id IS NOT NULL', if: $searchProblems === FALSE);

			case 'closed':
				return \selling\Sale::model()
					->whereClosed(FALSE, if: $searchProblems === TRUE)
					->whereClosed(TRUE, if: $searchProblems === FALSE);

		}

		return NULL;

	}
	public static function countForAccountingCheck(string $type, \farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): int {

		self::filterForAccountingCheck($eFarm, $search);
		self::applyConditions($type, $searchProblems);
		return \selling\Sale::model()->count();

	}

	public static function getForAccounting(\farm\Farm $eFarm, \Search $search): array {

		$selectSale = [
			'id', 'customer' => ['name', 'type', 'destination', 'user'], 'preparationStatus', 'priceIncludingVat',
			'deliveredAt', 'document', 'farm', 'profile', 'createdAt', 'taxes', 'hasVat', 'priceExcludingVat',
			'onlinePaymentStatus', 'paymentStatus', 'closed', 'invoice',
			'marketParent' => ['customer' => ['name', 'type', 'destination']],
			'shopDate' => ['id', 'deliveryDate', 'status', 'orderStartAt', 'orderEndAt'], 'createdBy',
			'cPayment' => \selling\Payment::model()
				->select(\selling\Payment::getSelection())
				->or(
					fn() => $this->whereOnlineStatus(NULL),
					fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
				)
				->delegateCollection('sale', 'id'),
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
					->delegateCollection('sale')
		];

		self::filterForAccountingCheck($eFarm, $search);

		$cSale = \selling\Sale::model()
			->select($selectSale)
			->sort(['deliveredAt' => SORT_DESC])
			->option('count')
			->getCollection(NULL, NULL, 'id');

		$nSale = \selling\Sale::model()->found();

		return [$cSale, $nSale];

	}
}
