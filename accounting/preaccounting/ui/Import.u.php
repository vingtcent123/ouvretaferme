<?php
namespace preaccounting;

Class ImportUi {

	public function __construct() {
		\Asset::css('preaccounting', 'import.css');
		\Asset::css('preaccounting', 'invoicing.css');
		\Asset::js('preaccounting', 'invoicing.js');
	}

	public function list(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cInvoice, int $nInvoice, \Search $search): string {

		if($cInvoice->empty() and $search->empty(['id'])) {
			return '<div class="util-info">'.s("Il n'y a aucune facture à importer. Êtes-vous sur le bon exercice comptable ?").'</div>';
		}

		$h = '<div id="invoice-search" class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH.'?tab=invoice';

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Type de client").'</legend>';
					$h .= $form->select('type', [
						\selling\Customer::PRO => s("Professionnels"),
						\selling\Customer::PRIVATE => s("Particuliers"),
					], $search->get('type'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Rattachement à une opération bancaire").'</legend>';
					$h .= $form->select('reconciliated', [
						1 => s("Écritures rattachées uniquement"),
						0 => s("Écritures non rattachées"),
					], $search->get('reconciliated'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Écart de paiement").'</legend>';
					$h .= $form->select('accountingDifference', [
						NULL => s("Toutes les factures"),
						1 => s("Factures avec un écart de paiement"),
						0 => s("Factures sans écart de paiement"),
					], (int)$search->get('accountingDifference'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Client").'</legend>';
					$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
				$h .= '</fieldset>';

			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
				$h .= '<a href="'.$url.'" class="btn btn-outline-secondary">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($nInvoice === 0) {

			if($search->empty()) {

				$h .= '<div class="util-info">'.s("Vous êtes à jour de vos imports ! ... ou alors vous n'avez pas terminé de <link>préparer les données des factures</link>", ['link' => '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite">']).'</div>';

			} else {

				$h .= '<div class="util-info">'.s("Aucune facture ne correspond à vos critères de recherche.").'</div>';

			}

			return $h;

		}

		$h .= '<div class="util-info">';
			$h .= s("Vous ne pouvez importer que les factures avec un moyen de paiement défini et des numéros de compte renseignés pour chaque article.");
		$h .= '</div>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="invoicing-import-table" data-batch="#batch-invoice">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" class="batch-all batch-all-group" onclick="Invoicing.toggleGroupSelection(this)"/>';
							$h .= '</label>';
						$h .= '</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Date").'</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th rowspan="2">'.s("Référence").'</th>';
						$h .= '<th rowspan="2" class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Écritures").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th class="text-center td-min-content">'.s("D/C").'</th>';
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

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td rowspan="'.($rowspan + 1).'" class="td-checkbox td-vertical-align-top">';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eInvoice['id'].'" batch-type="invoice" oninput="Invoicing.changeSelection(this)" data-batch-amount-excluding="'.($eInvoice['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eInvoice['priceIncludingVat'] ?? 0.0).'" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="text-center td-vertical-align-top">'.\util\DateUi::numeric($eInvoice['date']).'</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="td-vertical-align-top">';
								$h .= encode($eInvoice['customer']->getName());
								if($eInvoice['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eInvoice['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';

							$h .= '<td rowspan="'.$rowspan.'" class=" td-vertical-align-top">';
								$h .= '<a href="/ferme/'.$eFarm['id'].'/factures?document='.encode($eInvoice['document']).'&customer='.encode($eInvoice['customer']['name']).'">';
									$h .= encode($eInvoice['name']);
								$h .= '</a>';
							$h .= '</td>';
							$h .= '<td rowspan="'.$rowspan.'" class="text-end highlight-stick-right td-vertical-align-top invoicing-import-td-amount">';

								$h .= \selling\SaleUi::getTotal($eInvoice);

								if($eInvoice['cashflow']->notEmpty()) {

									$h .= '<br />';

									if($eInvoice['cashflow']['amount'] !== $eInvoice['priceIncludingVat']) {

										$class = 'color-danger';

										if($eInvoice['taxes'] === \selling\Invoice::EXCLUDING) {

											$eInvoice['taxes'] = \selling\Invoice::INCLUDING;
											$h .= \selling\SaleUi::getTotal($eInvoice);
											$h .= '<br />';

										}

									} else {
										$class = '';
									}

									$h .= '<span class="'.$class.'">'.\util\TextUi::money($eInvoice['cashflow']['amount']).'</span>';
									$h .= '<a title="'.s("Rapprochée").'" href="'.\company\CompanyUi::urlFarm($eInvoice['farm']).'/banque/operations?id='.$eInvoice['cashflow']['id'].'&bankAccount='.$eInvoice['cashflow']['account']['id'].'" class="util-badge bg-accounting">'.\Asset::icon('piggy-bank').'</a>';

								}

							$h .= '</td>';

							$h .= $this->operations([$operation], withTr: FALSE);

						$h .= '</tr>';

						$h .= $this->operations($operations, TRUE);

						$h .= '<tr>';
							$h .= '<td colspan="3" class="text-end">';
								if($eInvoice->hasAccountingDifference()) {
									$h .= '<div class="mb-1">';
										$difference = abs($eInvoice['priceIncludingVat'] - $eInvoice['cashflow']['amount']);
										$form = new \util\FormUi();
										$h .= $form->openAjax(\company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:updateInvoiceAccountingDifference', ['id' => 'difference-'.$eInvoice['id'], 'name' => 'difference-'.$eInvoice['id']]);
											$h .= $form->hidden('id', $eInvoice['id']);
											$h .= '<fieldset>';
												$h .= '<legend>';
													$h .= s("Traitement comptable de l'écart de {value}", \util\TextUi::money($difference));
												$h .= '</legend>';
												$h .= $form->select('accountingDifference', \selling\InvoiceUi::p('accountingDifference')->values, $eInvoice['accountingDifference'], attributes: ['onchange' => 'Invoicing.submit(this);', 'mandatory' => TRUE]);
											$h .= '</fieldset>';
										$h .= $form->close();
									$h .= '</div>';
								}
								$h .= '<div class="invoicing-import-td-action">';
									if($eInvoice->acceptAccountingImport()) {
										$attributes = [
											'data-confirm' => s("Confirmez-vous importer cette facture dans votre comptabilité ?"),
											'data-ajax' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoice',
											'post-id' => $eInvoice['id'],
											'post-financial-year' => $eFinancialYear['id'],
										];
										$h .= '<a '.attrs($attributes).' class="btn btn-sm btn-secondary">';
											$h .= \Asset::icon('hand-thumbs-up').' <span class="hide-sm-down">'.s("Importer").'</span>';
										$h .= '</a>';
									}
									if($eInvoice->acceptAccountingIgnore()) {
										$attributes = [
											'data-confirm' => s("Confirmez-vous ignorer cette facture ? Elle ne vous sera plus jamais proposée à l'import."),
											'data-ajax' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreInvoice',
											'post-id' => $eInvoice['id'],
											'post-financial-year' => $eFinancialYear['id'],
										];
										$h .= '<a '.attrs($attributes).' class="btn btn-sm btn-outline-secondary">';
											$h .= \Asset::icon('hand-thumbs-down').' <span class="hide-sm-down">'.s("Ignorer").'</span>';
										$h .= '</a>';
									}
								$h .= '</div>';

							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top"></td>';
							$h .= '<td></td>';
							$h .= '<td class="text-end highlight-stick-right invoicing-import-td-operation font-sm"></td>';
							$h .= '<td></td>';
							$h .= '<td></td>';

						$h .= '</tr>';

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $eFinancialYear);
		return $h;

	}

	public function operations(array $operations, bool $withTr): string {

		$h = '';

		foreach($operations as $operation) {

			if($withTr) {
				$h .= '<tr>';
			}

				$h .= '<td class="text-center invoicing-import-td-operation font-sm">';
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
				$h .= '<td class="text-end highlight-stick-right invoicing-import-td-operation font-sm">';
					$h .= \util\TextUi::money($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]);
				$h .= '</td>';
				$h .= '<td class="text-center invoicing-import-td-operation">';
					if($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEBIT] !== 0.0) {
						$h .= s("D");
					} else {
						$h .= s("C");
					}
				$h .= '</td>';
				$h .= '<td class="invoicing-import-td-operation font-sm">';
					if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
						$h .= $this->emptyData();
					} else {
						$h .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
					}
				$h .= '</td>';

			if($withTr) {
				$h .= '</tr>';
			}

		}

		return $h;

	}

	public function emptyData(): string {

		return '<span class="color-danger" title="'.s("Information manquante").'">'.\Asset::icon('three-dots').'</span>';

	}

	public function getBatch(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$menu = '<a href="javascript: void(0);" class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number"></span>';
				$menu .= ' <span class="batch-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';

		$attributesImport = [
			'data-ajax-submit' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doImportInvoiceCollection',
			'data-confirm' => s("Confirmez-vous importer ces factures dans votre comptabilité ?"),
			'post-financial-year' => $eFinancialYear['id'],
		];
		$menu .= '<a '.attrs($attributesImport).' class="batch-import batch-item" >'.\Asset::icon('hand-thumbs-up').'<span>'.s("Importer").'</span></a>';

		$attributesIgnore = [
			'data-ajax-submit' => \company\CompanyUi::urlFarm($eFarm).'/preaccounting/import:doIgnoreCollection',
			'data-confirm' => s("Confirmez-vous ignorer ces factures ? Elles ne vous seront plus jamais proposées à l'import."),
			'post-financial-year' => $eFinancialYear['id'],
		];
		$menu .= '<a '.attrs($attributesIgnore).' class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';

		return \util\BatchUi::group('batch-invoice', $menu, title: s("Pour les factures sélectionnées"));

	}
}
