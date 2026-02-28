<?php
namespace preaccounting;

Class ImportUi {

	public function __construct() {
		\Asset::css('preaccounting', 'import.css');
		\Asset::css('preaccounting', 'invoicing.css');
		\Asset::js('preaccounting', 'import.js');
	}

	public function list(\farm\Farm $eFarm, \Collection $cOperation, ?string $lastValidationDate, \Search $search): string {

		if($cOperation->empty() and $search->empty(['from', 'to', 'cRegister', 'cMethod'])) {
			return '<div class="util-empty">'.s("Il n'y a aucune opération à importer. Avez-vous terminé de <link>préparer vos données</link> ?", ['link' => '<a href="'.\farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite/verifier:import?from='.$search->get('from').'">']).'</div>';
		}

		$nPayment = $cOperation->find(fn($e) => $e instanceof \selling\Payment)->count();
		$nCash = $cOperation->find(fn($e) => $e instanceof \cash\Cash)->count();
		$dateOk = date('Y-m-t', strtotime($search->get('from'))) < date('Y-m-d');
		$financialYearOk = ($eFarm['eFinancialYear']['status'] === \account\FinancialYear::OPEN);
		$hasBeforeLastValidationDate = FALSE;
		$canImport = ($dateOk and $financialYearOk);

		$h = '<div id="payment-search" class="util-block-search">';

			$form = new \util\FormUi();

			$h .= $form->openAjax(LIME_REQUEST_PATH.'?type=export&from='.GET('from'), ['method' => 'get', 'class' => 'util-search']);
				$values = [
					'invoice' => s("Factures avec rapprochement bancaire"),
				];
				$values += $search->get('cRegister')->makeArray(function($e, &$key) {
					$key = $e['id'];
					return strip_tags(\cash\RegisterUi::getName($e));
				});
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Origine des opérations").'</legend>';
					$h .= $form->select('importType', $values, $search->get('importType'), ['placeholder' => s("Toutes les origines")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Type de client").'</legend>';
					$h .= $form->select('customerType', [
						\selling\Customer::PRO => s("Professionnels"),
						\selling\Customer::PRIVATE => s("Particuliers"),
					], $search->get('customerType'), ['placeholder' => s("Tous types de clients")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Écart de paiement").'</legend>';
					$h .= $form->select('accountingDifference', [
						1 => s("Factures avec un écart de paiement"),
						0 => s("Factures sans écart de paiement"),
					], $search->get('accountingDifference'), ['placeholder' => s("Toutes les factures")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Client").'</legend>';
					$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
				$h .= '</fieldset>';

			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Chercher"));
				if($search->notEmpty(['from', 'to'])) {
					$h .= '<a href="'.LIME_REQUEST_PATH.'?from='.$search->get('from').'&type=export" class="btn btn-outline-primary">'.\Asset::icon('x-lg').'</a>';
				}
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($nPayment === 0 and $nCash === 0) {

			if($search->empty(['from', 'to', 'cRegister', 'cMethod'])) {

				$h .= '<div class="util-empty">'.s("Vous êtes à jour de vos imports ! ... ou alors vous n'avez pas terminé de <link>préparer vos données</link> ?", ['link' => '<a href="'.\farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite/verifier:import?from='.$search->get('from').'">']).'</div>';

			} else {

				$h .= '<div class="util-empty">'.s("Aucune opération ne correspond à vos critères de recherche.").'</div>';

			}

			return $h;

		}

		$showIgnoreColumn = $cOperation->find(fn($e) => $e instanceof \selling\Payment)->count() > 0;
		$columns = 8;
		if($showIgnoreColumn) {
			$columns++;
		}

		$h .= '<div class="stick-sm util-overflow-lg">';

			$h .= '<table class="preaccounting-import-table" data-batch="#batch-payment">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr class="tr-bold">';
						$h .= '<th colspan="'.$columns.'" class="text-center">';
							if(empty($search->get('type'))) {
								if($nPayment > 0 and $nCash > 0) {
									$h .= s("{payment} paiements et {cash} opérations de caisse", ['payment' => $nPayment, 'cash' => $nCash]);
								} else if($nPayment > 0) {
									$h .= p("{value} paiement, aucune opération de caisse", "{value} paiements, aucune opération de caisse", $nPayment);
								} else if($nCash > 0) {
									$h.= p("{value} opération de caisse", "{value} opérations de caisse", $nCash);
								}
							} else if($search->get('type') === 'invoice') {
								$h .= p("{value} paiement, aucun paiement de facture", "{value} paiements, aucun paiement de facture", $nPayment);
							} else {
								$h.= p("{value} opération de caisse", "{value} opérations de caisse", $nCash);
							}
						$h .= '</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="text-center">'.s("Date").'</th>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th rowspan="2">'.s("Référence").'</th>';
						$h .= '<th rowspan="2" class="text-end t-highlight">'.s("Montant").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Écritures").'</th>';
						if($showIgnoreColumn) {
							$h .= '<th rowspan="2" class="text-center"></th>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Montant").'</th>';
						$h .= '<th class="text-center td-min-content">'.s("D/C").'</th>';
						$h .= '<th>'.s("Paiement").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cOperation as $eOperation) {

					$operations = $eOperation['operations'];

					if($eOperation instanceof \selling\Payment) {

						$eElement = $eOperation['invoice'];

						$date = $eOperation['paidAt'];
						$customer = encode($eElement['customer']->getName());

						if($eElement['customer']->notEmpty()) {
							$customer .= '<div class="util-annotation">';
								$customer .= \selling\CustomerUi::getCategory($eElement['customer']);
							$customer .= '</div>';
						}

						if(
							$eElement['totalPaid'] !== $eElement['priceIncludingVat'] or
							$eOperation['cashflow']['amount'] !== $eOperation['amountIncludingVat']
						) {
							$customer .= '<div class="mb-1">';

								if($eOperation['cashflow']['amount'] !== $eOperation['amountIncludingVat']) {
									$difference = abs($eOperation['cashflow']['amount'] - $eOperation['amountIncludingVat']);
								} else if($eElement['totalPaid'] !== $eElement['priceIncludingVat']) {
									$difference = abs($eElement['priceIncludingVat'] - $eElement['totalPaid']);
								}

								$form = new \util\FormUi();
								$customer .= $form->openAjax(\farm\FarmUi::urlConnected($eFarm).'/preaccounting/import:updateInvoiceAccountingDifference', ['id' => 'difference-'.$eOperation['id'], 'name' => 'difference-'.$eOperation['id']]);
									$customer .= $form->hidden('id', $eOperation['id']);
									$customer .= '<fieldset>';
										$customer .= '<legend>';
											if($eOperation['accountingDifference'] === NULL) {
												$customer .= $this->emptyData(NULL).' ';
											}
											$customer .= s("Traitement comptable de l'écart de {value}", \util\TextUi::money(round($difference, 2)));
										$customer .= '</legend>';
										$customer .= $form->select('accountingDifference', \selling\PaymentUi::p('accountingDifference')->values, $eOperation['accountingDifference'], attributes: ['onchange' => 'Import.submit(this);'] + ($eOperation['accountingDifference'] !== NULL ? ['mandatory' => TRUE] : []));
									$customer .= '</fieldset>';
								$customer .= $form->close();
							$customer .= '</div>';

							if($eOperation['accountingDifference'] === NULL) {
								$canImport = FALSE;
							}

						}

						if($eOperation['source'] === \selling\Payment::INVOICE) {
							$reference = '<a class="btn btn-outline-primary btn-xs" href="/ferme/'.$eFarm['id'].'/factures?name='.encode($eOperation['invoice']['number']).'">';
								$reference .= encode($eElement['number']);
							$reference .= '</a>';
						} else {
							$reference = '<a class="btn btn-outline-primary btn-xs" href="'.\selling\SaleUi::url($eOperation['sale']).'">';
								$reference .= s("Vente n°", $eElement['document']);
							$reference .= '</a>';
						}

						$amount = '<div>';
							$amount .= \selling\SaleUi::getIncludingTaxesTotal($eElement);
						$amount .= '</div>';

						$amount .= '<div>';
							if($eOperation['amountIncludingVat'] !== $eElement['priceIncludingVat']) {

								$class = 'color-danger';
								if($eOperation['amountIncludingVat'] < $eElement['priceIncludingVat']) {
									$amount .= '<span class="util-badge bg-warning">'.s("Partiel").'</span> ';
								}

								if($eOperation['source'] === \selling\Payment::INVOICE and $eOperation['invoice']['taxes'] === \selling\Invoice::EXCLUDING) {

									$eOperation['invoice']['taxes'] = \selling\Invoice::INCLUDING;
									$amount .= \selling\SaleUi::getTotal($eOperation['invoice']);
									$amount .= '<br />';

								}
							} else {
								$class = '';
							}

							$amount .= '<span class="'.$class.'">'.\util\TextUi::money($eOperation['cashflow']['amount']).'</span>';
							$amount .= '<a title="'.s("Rapprochée").'" href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/operations?id='.$eOperation['cashflow']['id'].'&bankAccount='.$eOperation['cashflow']['account']['id'].'" class="util-badge bg-accounting">'.\Asset::icon('bank').'</a>';

						$amount .= '</div>';

					} else {

						$date = $eOperation['date'];

						$reference = '<a class="btn btn-outline-primary btn-xs" href="'.\farm\FarmUi::urlCash($eOperation['register']).'&position='.$eOperation['position'].'">';
							$reference .= \cash\CashUi::getName($eOperation);
						$reference .= '</a>';

						if(in_array($eOperation['source'], [\cash\Cash::SELL_INVOICE, \cash\Cash::SELL_SALE])) {

							$customer = encode($eOperation['customer']->getName());

							if($eOperation['customer']->notEmpty()) {
								$customer .= '<div class="util-annotation">';
									$customer .= \selling\CustomerUi::getCategory($eOperation['customer']);
								$customer .= '</div>';
							}

						} else {

							$customer = '-';
						}

						$amount = '<div>';
							$amount .= \util\TextUi::money($eOperation['amountIncludingVat']);
						$amount .= '</div>';

					}

					if(empty($lastValidationDate) === FALSE and $lastValidationDate > $date) {
						$canImport = FALSE;
						$hasBeforeLastValidationDate = TRUE;
					}

					$h .= '<tbody>';
						$h .= '<tr>';
							$h .= '<td class="text-center td-vertical-align-top">'.\util\DateUi::numeric($date).'</td>';
							$h .= '<td class="td-vertical-align-top">';
								$h .= $customer;
							$h .= '</td>';

							$h .= '<td class=" td-vertical-align-top">';
								$h .= $reference;
							$h .= '</td>';

							$h .= '<td class="text-end t-highlight td-vertical-align-top preaccounting-import-td-amount">';
								$h .= $amount;
							$h .= '</td>';

							$h .= '<td class="text-center preaccounting-import-td-operation font-sm td-vertical-align-top">';
								$accountLabels = [];
								foreach($operations as $operation) {
									if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
										if($eOperation['invoice']->notEmpty() or $eOperation['sale']->notEmpty()) {
											$link = LIME_REQUEST_PATH.'?from='.$search->get('from');
										} else if($eOperation instanceof \cash\Cash) {
											$link = \farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/journal-de-caisse?id='.$eOperation['register']['id'].'&position='.$eOperation['position'];
										} else {
											$link = NULL;
										}
										$accountLabel = $this->emptyData($link);
										$canImport = FALSE;
									} else {
										$eAccount = new \account\Account(['class' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], 'description' => $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]]);
										$accountLabel = '<div data-dropdown="bottom" data-dropdown-hover="true">';
											$accountLabel .= encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
										$accountLabel .= '</div>';
										if($operation[AccountingLib::FEC_COLUMN_OPERATION_NATURE] !== NULL) {
											$operationLinked = array_find($operations, fn($op) => $op[AccountingLib::FEC_COLUMN_NUMBER] === (int)$operation[AccountingLib::FEC_COLUMN_OPERATION_NATURE]);
											$more = s(" - rattaché au compte {value}", $operationLinked[AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
										} else {
											$more = '';
										}
										$accountLabel .= new \account\AccountUi()->getDropdownTitle($eAccount, $more);
									}
									$accountLabels[] = $accountLabel;
								}
								$h .= join ('', $accountLabels);
							$h .= '</td>';
							$h .= '<td class="text-end t-highlight preaccounting-import-td-operation font-sm td-vertical-align-top">';
								$amounts = [];
								foreach($operations as $operation) {
									$amounts[] = \util\TextUi::money(abs($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]));
								}
								$h .= join ('<br />', $amounts);
							$h .= '</td>';
							$h .= '<td class="text-center preaccounting-import-td-operation font-sm td-vertical-align-top">';
								$directions = [];
								foreach($operations as $operation) {
									if($operation[\preaccounting\AccountingLib::FEC_COLUMN_DEBIT] !== 0.0) {
										$directions[] = s("D");
									} else {
										$directions[] = s("C");
									}
								}
								$h .= join ('<br />', $directions);
							$h .= '</td>';
							$h .= '<td class="preaccounting-import-td-operation font-sm td-vertical-align-top">';
								$paymentMethods = [];
								foreach($operations as $operation) {
									if(empty($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])) {
										if($eOperation['invoice']->notEmpty() or $eOperation['sale']->notEmpty()) {
											$link = LIME_REQUEST_PATH.'?from='.$search->get('from');
										} else {
											$link = NULL;
										}
										$paymentMethods[] = $this->emptyData($link);
										$canImport = FALSE;
									} else {
										$paymentMethods[] = encode($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
									}
								}
								$h .= join ('<br />', $paymentMethods);
							$h .= '</td>';

							if($showIgnoreColumn) {

								$h .= '<td class="preaccounting-import-td-operation">';

									if($eOperation instanceof \selling\Payment and $eOperation->acceptAccountingIgnore()) {
										$attributes = [
											'data-confirm' => s("Confirmez-vous ignorer ce paiement ? Il ne vous sera plus jamais proposé à l'import."),
											'data-ajax' => \farm\FarmUi::urlConnected($eFarm).'/preaccounting/import:doIgnorePayment',
											'post-id' => $eOperation['id'],
										];
										$h .= '<a '.attrs($attributes).' class="btn btn-sm btn-outline-secondary">';
											$h .= \Asset::icon('hand-thumbs-down').' '.s("Ignorer");
										$h .= '</a>';
									}
								$h .= '</td>';
							}

						$h .= '</tr>';

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		$displayButton = ($dateOk === TRUE and $financialYearOk === TRUE);

		if($displayButton === FALSE) {
			return $h;
		}

		if($canImport === FALSE) {
			$h .= '<div class="util-info">';
				if($hasBeforeLastValidationDate) {
					$h .= s("Seules les opérations <b>datées du {value} ou après</b> seront importées.", \util\DateUi::numeric($lastValidationDate));
				} else {
					$h .= s("Réalisez <b>certaines corrections nécessaires</b> signalées avec le symbole {value} pour pouvoir effectuer l'import.", $this->emptyData(NULL));
				}
			$h .= '</div>';
		}

		$attributes = ['class' => 'btn-xl btn btn-primary'];
		if($search->empty(['type', 'from', 'to', 'cRegister', 'cMethod']) === FALSE) {
			$canImport = FALSE;
		}
		if($canImport === FALSE) {
			$attributes['class'] .= ' disabled';
		} else {
			$attributes['data-ajax'] = \farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/preaccounting/import:doImport';
			$attributes['post-from'] = $search->get('from');
			$attributes['data-waiter'] = s("Import en cours...");
			$attributes['data-confirm'] = s("L'import est définitif ! Confirmez-vous cette action ?");
		}

		$h .= '<a '.attrs($attributes).'>'.s("Générer les écritures").'</a>';
		if($search->empty(['type', 'from', 'to', 'cRegister', 'cMethod']) === FALSE) {
			$h .= '<span class="util-annotation ml-1">';
				$h .= s("<link>Réinitialisez la recherche</link> pour pouvoir tout importer", ['link' => '<a href="'.LIME_REQUEST_PATH.'?from='.$search->get('from').'">']);
			$h .= '</span>';

		}

		return $h;

	}

	public function emptyData(?string $link): string {

		$icon = \Asset::icon('three-dots');
		if($link !== NULL) {
			$icon = '<a href="'.$link.'">'.$icon.'</a>';
		}

		$h = '<span class="color-danger" title="'.s("Information manquante").'">';
			$h .= $icon;
		$h .= '</span>';

		return $h;

	}

}
