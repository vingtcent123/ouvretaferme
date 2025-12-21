<?php
namespace preaccounting;

Class ImportUi {

	public function __construct() {
		\Asset::css('preaccounting', 'invoicing.css');
		\Asset::js('preaccounting', 'invoicing.js');
	}

	public function displaySales(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cSale, \Search  $search): string {

		if($cSale->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune vente à importer. Êtes-vous sur le bon exercice comptable ?").'</div>';
		}

		$h = '<div id="invoice-search" class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH.'?tab=sales';

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->select('type', [
						NULL => s("Type de client"),
						\selling\Customer::PRO => s("Professionnels"),
						\selling\Customer::PRIVATE => s("Particuliers"),
					], $search->get('type', 'int', 0), ['mandatory' => TRUE]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';


		if($eFinancialYear->isCashAccrualAccounting()) {

			$h .= '<div class="util-info">'.s("Lors de l'import des ventes, les écritures client (compte {value}) de contrepartie correspondantes seront automatiquement créées.", \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS).'</div>';

		}

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="invoicing-import-table" data-batch="#batch-sales">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="sales" onclick="Invoicing.toggleGroupSelection(this)"/>';
							$h .= '</label>';
						$h .= '</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Date").'</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Écritures").'</th>';
						$h .= '<th rowspan="2"></th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
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

					$onclick = ' onclick="Invoicing.updateSelection(this);"';

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-checkbox td-vertical-align-top">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSale['id'].'" batch-type="sales" oninput="Invoicing.changeSelection(this)" data-batch-amount-excluding="'.($eSale['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eSale['priceIncludingVat'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-center td-vertical-align-top">'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="td-vertical-align-top">';
								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-end highlight-stick-right td-vertical-align-top">'.\selling\SaleUi::getTotal($eSale).'</td>';
							$h .= '<td '.$onclick.' class="text-center invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
								$h .= $this->emptyData();
							} else {
								$eAccount = new \account\Account(['class' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], 'description' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]]);
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
								$h .= '</div>';
								$h .= new \account\AccountUi()->getDropdownTitle($eAccount);
							}
							$h .= '</td>';
							$h .= '<td '.$onclick.' class="text-end highlight-stick-right invoicing-import-td-operation">'.\util\TextUi::money($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]).'</td>';
							$h .= '<td '.$onclick.' class="invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
								$h .= $this->emptyData();
							} else {
								$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
							}
							$h .= '</td>';

							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="td-vertical-align-top">';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';
									if($eSale->acceptAccountingImport()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportSale" post-id="'.$eSale['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Importer").'</a>';
									}
									if($eSale->acceptAccountingIgnore()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreSale" post-id="'.$eSale['id'].'" class="dropdown-item">'.s("Ignorer").'</a>';
									}
								$h .= '</div>';

							$h .= '</td>';

						$h .= '</tr>';

						$h .= $this->operations($operations, $onclick);

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $eFinancialYear, 'sales');
		return $h;
	}

	public function displayInvoice(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cInvoice, \Search $search): string {


		if($cInvoice->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucune facture à importer. Êtes-vous sur le bon exercice comptable ?").'</div>';
		}

		$h = '<div id="invoice-search" class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH.'?tab=invoice';

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

			$h .= '<div>';
				$h .= $form->select('type', [
					NULL => s("Type de client"),
					\selling\Customer::PRO => s("Professionnels"),
					\selling\Customer::PRIVATE => s("Particuliers"),
				], $search->get('type', 'int', 0), ['mandatory' => TRUE]);
				$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
				$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($eFinancialYear->isCashAccrualAccounting()) {

			$h .= '<div class="util-info">'.s("Lors de l'import des factures, les écritures client (compte {value}) de contrepartie correspondantes seront automatiquement créées.", \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS).'</div>';

		}

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="invoicing-import-table" data-batch="#batch-invoice">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="invoice" onclick="Invoicing.toggleGroupSelection(this)"/>';
							$h .= '</label>';
						$h .= '</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Date").'</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th rowspan="2">'.s("Référence").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Écritures").'</th>';
						$h .= '<th rowspan="2"></th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th>'.s("Paiement").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cInvoice as $eInvoice) {

					$batch = [];

					if($eInvoice->acceptAccountingImport() === FALSE) {
						$batch[] = 'not-import';
					}

					$rowspan = count($eInvoice['operations']);
					$operations = $eInvoice['operations'];
					$operation = array_shift($operations);

					$onclick = 'onclick="Invoicing.updateSelection(this)"';

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-checkbox td-vertical-align-top">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eInvoice['id'].'" batch-type="invoice" oninput="Invoicing.changeSelection(this)" data-batch-amount-excluding="'.($eInvoice['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eInvoice['priceIncludingVat'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-center td-vertical-align-top">'.\util\DateUi::numeric($eInvoice['date']).'</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="td-vertical-align-top">';
								$h .= encode($eInvoice['customer']->getName());
								if($eInvoice['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eInvoice['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class=" td-vertical-align-top"><a href="/ferme/'.$eFarm['id'].'/factures?document='.encode($eInvoice['document']).'&customer='.encode($eInvoice['customer']['name']).'">'.encode($eInvoice['name']).'</a></td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-end highlight-stick-right td-vertical-align-top">'.\selling\SaleUi::getTotal($eInvoice).'</td>';
							$h .= '<td '.$onclick.' class="text-center invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
								$h .= $this->emptyData();
							} else {
								$eAccount = new \account\Account(['class' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], 'description' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]]);
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
								$h .= '</div>';
								$h .= new \account\AccountUi()->getDropdownTitle($eAccount);
							}
							$h .= '</td>';
							$h .= '<td '.$onclick.' class="text-end highlight-stick-right invoicing-import-td-operation">'.\util\TextUi::money($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]).'</td>';
							$h .= '<td '.$onclick.' class="invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
								$h .= $this->emptyData();
							} else {
								$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
							}
							$h .= '</td>';

							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="td-vertical-align-top">';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';
									if($eInvoice->acceptAccountingImport()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoice" post-id="'.$eInvoice['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Importer").'</a>';
									}
									if($eInvoice->acceptAccountingIgnore()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreInvoice" post-id="'.$eInvoice['id'].'" class="dropdown-item">'.s("Ignorer").'</a>';
									}
								$h .= '</div>';

							$h .= '</td>';

						$h .= '</tr>';

						$h .= $this->operations($operations, $onclick);

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $eFinancialYear, 'invoice');
		return $h;

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
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
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

					$onclick = ' onclick="Invoicing.updateSelection(this);"';

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-checkbox td-vertical-align-top">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSale['id'].'" batch-type="market" oninput="Invoicing.changeSelection(this)" data-batch-amount-excluding="'.($eSale['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eSale['priceIncludingVat'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-center td-vertical-align-top">'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-vertical-align-top">';
								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="text-end highlight-stick-right td-vertical-align-top">'.\selling\SaleUi::getTotal($eSale).'</td>';
							$h .= '<td '.$onclick.' class="text-center invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
								$h .= $this->emptyData();
							} else {
								$eAccount = new \account\Account(['class' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], 'description' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]]);
								$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
									$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
								$h .= '</div>';
								$h .= new \account\AccountUi()->getDropdownTitle($eAccount);
							}
							$h .= '</td>';
							$h .= '<td '.$onclick.' class="text-end highlight-stick-right invoicing-import-td-operation">'.\util\TextUi::money($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]).'</td>';
							$h .= '<td '.$onclick.' class="invoicing-import-td-operation">';
							if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
								$h .= $this->emptyData();
							} else {
								$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
							}
							$h .= '</td>';

							$h .= '<td '.$onclick.' rowspan="'.$rowspan.'" class="td-vertical-align-top">';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

								$h .= '<div class="dropdown-list">';
									if($eSale->acceptAccountingImport()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportMarket" post-id="'.$eSale['id'].'" post-financial-year="'.$eFinancialYear['id'].'" class="dropdown-item">'.s("Importer").'</a>';
									}
									if($eSale->acceptAccountingIgnore()) {
										$h .= '<a data-ajax="'.\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreSale" post-id="'.$eSale['id'].'" class="dropdown-item">'.s("Ignorer").'</a>';
									}
								$h .= '</div>';

							$h .= '</td>';

						$h .= '</tr>';

						$h .= $this->operations($operations, $onclick);

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $eFinancialYear, 'market');
		return $h;

	}

	public function operations(array $operations, string $onclick): string {

		$h = '';

		foreach($operations as $operation) {

			$h .= '<tr '.$onclick.'>';

				$h .= '<td class="text-center invoicing-import-td-operation">';
					if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
						$h .= $this->emptyData();
					} else {
						$eAccount = new \account\Account(['class' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], 'description' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]]);
						$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
							$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
						$h .= '</div>';
						$h .= new \account\AccountUi()->getDropdownTitle($eAccount);
					}
				$h .= '</td>';
				$h .= '<td class="text-end highlight-stick-right invoicing-import-td-operation">';
					$h .= \util\TextUi::money($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]);
				$h .= '</td>';
				$h .= '<td class="invoicing-import-td-operation">';
					if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
						$h .= $this->emptyData();
					} else {
						$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
					}
				$h .= '</td>';

			$h .= '</tr>';

		}

		return $h;

	}

	public function emptyData(): string {

		return '<span class="color-danger" title="'.s("Information manquante").'">'.\Asset::icon('three-dots').'</span>';

	}

	public function getBatch(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type): string {

		$url = match($type) {
			'market' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportMarketCollection',
			'invoice' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoiceCollection',
			'sales' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportSalesCollection',
		};
		$urlIgnore = \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreCollection';
		$title = match($type) {
			'market' => s("Pour les marchés sélectionnés"),
			'invoice' => s("Pour les factures sélectionnées"),
			'sales' => s("Pour les ventes sélectionnées"),
		};

		$menu = '<a href="javascript: void(0);" class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number"></span>';
				$menu .= ' <span class="batch-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';


		$menu .= '<a data-ajax-submit="'.$url.'" class="batch-import batch-item" post-financial-year="'.$eFinancialYear['id'].'">'.\Asset::icon('hand-thumbs-up').'<span>'.s("Importer").'</span></a>';

		$menu .= '<a data-ajax-submit="'.$urlIgnore.'" post-type="'.$type.'" class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';

		return \util\BatchUi::group('batch-'.$type, $menu, title: $title);

	}

	public function getSaleDescription(\selling\Sale $eSale): string {

		return s("Vente {number} du {date}", ['number' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['deliveredAt'])]);

	}

	public function getPaymentSaleDescription(\selling\Sale $eSale): string {

		return s("Paiement vente {number} du {date}", ['number' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['deliveredAt'])]);

	}
}
