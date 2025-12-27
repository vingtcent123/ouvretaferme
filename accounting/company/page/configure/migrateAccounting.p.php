<?php
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
     ->select(\farm\Farm::getSelection())
     ->whereHasAccounting(TRUE)
     ->whereId(GET('farm'), if: get_exists('farm'))
     ->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);

			$cThirdParty = \account\ThirdPartyLib::getAll(new Search());

			foreach($cThirdParty as $eThirdParty) {
				$normalizedName = \account\ThirdPartyLib::normalizeName($eThirdParty['name']);
				\account\ThirdParty::model()->update($eThirdParty, ['normalizedName' => $normalizedName]);
			}

			// Je pars des cashflow pour remettre les mémos
			$cOperationCashflow = \journal\OperationCashflow::model()
				->select([
					'id',
					'operation' => ['thirdParty'],
					'cashflow' => ['name']
				])
				->getCollection();


			foreach($cOperationCashflow as $eOperationCashflow) {

				if(
					isset($eOperationCashflow['operation']['thirdParty']) === FALSE or  // operation supprimée
					isset($eOperationCashflow['cashflow']['name']) === FALSE // cashflow supprimé
				) {
					continue;
				}

				if(
					$eOperationCashflow['operation']->empty() or
					$eOperationCashflow['operation']['thirdParty']->empty()
				) {
					continue;
				}

				$eThirdParty = \account\ThirdPartyLib::getById($eOperationCashflow['operation']['thirdParty']['id']);

				$normalizedCasfhlowName = \account\ThirdPartyLib::normalizeName($eOperationCashflow['cashflow']['name']);

				foreach($normalizedCasfhlowName as $element) {

					if(isset($eThirdParty['memos'][$element]) === FALSE) {
						$eThirdParty['memos'][$element] = 0;
					}
					$eThirdParty['memos'][$element]++;

				}

				\account\ThirdParty::model()->update(
					$eThirdParty,
					['memos' => $eThirdParty['memos']]
				);

			}

			// Vérification du statut readyForAccounting des factures
			$cInvoice = \selling\Invoice::model()
				->select(\selling\Invoice::getSelection())
				->whereFarm($eFarm)
				->getCollection();

			foreach($cInvoice as $eInvoice) {
				if(\selling\InvoiceLib::isReadyForAccounting($eInvoice)) {
					\selling\Invoice::model()->update($eInvoice, ['readyForAccounting' => TRUE]);
				} else {
					\selling\Invoice::model()->update($eInvoice, ['readyForAccounting' => FALSE]);
				}
			}

			// Vérification du statut readyForAccounting des ventes
			$cSale = \selling\Sale::model()
				->select(\selling\Sale::getSelection())
				->whereProfile('NOT IN', [\selling\Sale::SALE_MARKET])
				->whereInvoice(NULL)
				->whereProfile(\selling\Sale::MARKET)
				->whereFarm($eFarm)
				->getCollection();
			foreach($cSale as $eSale) {
				if(\selling\SaleLib::isReadyForAccounting($eSale)) {
					\selling\Sale::model()->update($eSale, ['readyForAccounting' => TRUE]);
				}
			}

		}

	});
?>
