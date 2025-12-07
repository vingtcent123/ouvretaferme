<?php
namespace invoicing;

Class ImportUi {

	public function __construct() {
		\Asset::css('invoicing', 'invoicing.css');
		\Asset::js('invoicing', 'invoicing.js');
	}

	public function displayMarket(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cSale): string {

		if($cSale->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune vente de marché à importer. Êtes-vous sur le bon exercice comptable ?").'</div>';
		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="invoicing-import-table" data-batch="#batch-market">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="market" onclick="Invoicing.toggleGroupSelection(this)"/>';
							$h .= '</label>';
						$h .= '</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Date").'</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Écritures").'</th>';
						$h .= '<th rowspan="2"></th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("N° Compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th>'.s("Paiement").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cSale as $eSale) {

					$batch = [];

					if($eSale->acceptAccountingImport() === FALSE) {
						$batch[] = 'not-import';
					}

					$rowspan = count($eSale['operations']);
					$operations = $eSale['operations'];
					$operation = array_shift($operations);

					$h .= '<tbody>';
						$h .= '<tr data-document="'.encode($eSale['document']).'">';
							$h .= '<td rowspan="'.$rowspan.'" class="td-checkbox td-vertical-align-top">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSale['id'].'" batch-type="market" oninput="Invoicing.changeSelection(this)" data-batch-amount-excluding="'.($eSale['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eSale['priceIncludingVat'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="text-center td-vertical-align-top">'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-vertical-align-top">';
								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="text-end highlight-stick-right td-vertical-align-top">'.\selling\SaleUi::getTotal($eSale).'</td>';
							$h .= '<td class="text-center invoicing-import-td-operation">'.encode($operation[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]).'</td>';
							$h .= '<td class="text-end highlight-stick-right invoicing-import-td-operation">'.\util\TextUi::money($operation[\farm\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]).'</td>';
							$h .= '<td class="invoicing-import-td-operation">'.encode($operation[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]).'</td>';

							$h .= '<td rowspan="'.$rowspan.'" class="td-vertical-align-top">';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/invoicing/import:doImport" post-id="'.$eSale['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Importer").'</a>';
									$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/invoicing/import:doIgnore" post-id="'.$eSale['id'].'" class="dropdown-item">'.s("Ignorer").'</a>';
								$h .= '</div>';

							$h .= '</td>';

						$h .= '</tr>';

						foreach($operations as $operation) {

							$h .= '<tr data-document="'.encode($eSale['document']).'">';

								$h .= '<td class="text-center invoicing-import-td-operation">'.encode($operation[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]).'</td>';
								$h .= '<td class="text-end highlight-stick-right invoicing-import-td-operation">';
									$h .= \util\TextUi::money($operation[\farm\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]);
								$h .= '</td>';
								$h .= '<td class="invoicing-import-td-operation">'.encode($operation[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]).'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFinancialYear, 'market');
		return $h;

	}


	public function getBatch(\account\FinancialYear $eFinancialYear, string $type): string {

		$url = match($type) {
			'market' => '/selling/product:updateAccount',
			'invoice' => '/selling/product:updateAccount',
			'sales' => '/selling/product:updateAccount',
		};
		$title = match($type) {
			'market' => s("Pour les marchés sélectionnés"),
			'invoice' => s("Pour les factures sélectionnées"),
			'sales' => s("Pour les ventes sélectionnées"),
		};

		$menu = '<a href="javascript: void(0);" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number"></span>';
				$menu .= ' <span class="batch-menu-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';


		$menu .= '<a data-ajax-submit="'.$url.'" class="batch-menu-import batch-menu-item" post-financial-year="'.$eFinancialYear['id'].'">'.\Asset::icon('hand-thumbs-up').'<span>'.s("Importer").'</span></a>';

		$menu .= '<a data-ajax-submit="'.$url.'" class="batch-menu-ignore batch-menu-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';

		return \util\BatchUi::group('batch-'.$type, $menu, title: $title);

	}

	public function getDescription(\selling\Sale $eSale): string {

		return s("Vente du {value}", \util\DateUi::numeric($eSale['deliveredAt']));

	}
}
