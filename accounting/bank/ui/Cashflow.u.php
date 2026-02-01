<?php
namespace bank;

class CashflowUi {

	public function __construct() {
		\Asset::js('bank', 'cashflow.js');
		\Asset::css('bank', 'cashflow.css');
		\Asset::css('company', 'company.css');
		\Asset::css('journal', 'journal.css');
	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="cashflow-search" class="util-block-search '.(($search->empty(['ids', 'bankAccount']) and $search->get('isReconciliated') === NULL) ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search util-search-3']);

			$h .= '<fieldset>';
				$h .= '<legend>'.s("Période").'</legend>';
				$h .= $form->inputGroup(
					$form->date('periodStart', $search->get('periodStart'), ['placeholder' => s("Début")]).
					$form->addon(s("à")).
					$form->date('periodEnd', $search->get('periodEnd'), ['placeholder' => s("Fin")])
				);
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Écritures comptables").'</legend>';
				$statuses = CashflowUi::p('status')->values;
				if($eFarm->usesAccounting() === FALSE) {
					unset($statuses[Cashflow::ALLOCATED]);
					$statuses[Cashflow::WAITING] = s("Valide");
				}
				$h .= $form->select('status', $statuses, $search->get('status'));
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Libellé").'</legend>';
				$h .= $form->text('memo', $search->get('memo'), ['placeholder' => s("Libellé")]);
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Mouvement").'</legend>';
				$h .= $form->select('type', [Cashflow::DEBIT => s("Débit"), Cashflow::CREDIT => s("Crédit")], $search->get('type'));
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Montant").'</legend>';
				$h .= $form->inputGroup(
					$form->addon(s('Montant')).
					$form->number('amount', $search->get('amount'), ['style' => 'width: 100px', 'step' => 0.01]).
					$form->addon(s('+/-')).
					$form->number('margin', $search->get('margin', 1)).
					$form->addon(s('€'))
				);
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Rapprochement").'</legend>';
				$h .= $form->select('isReconciliated', [1 => s("Opérations rapprochées"), 0 => s("Opérations non rapprochées")], $search->get('isReconciliated') === NULL ? '' : (int)$search->get('isReconciliated'));
			$h .= '</fieldset>';

			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Chercher"));
				$h .= '<a href="'.$url.'" class="btn btn-outline-primary">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getSummarize(\farm\Farm $eFarm, int $nSuggestion, int $nCashflow): string {

		$h = '<div class="mb-1 flex-justify-space-between flex-align-center">';

			$h .= '<div>';
				if($nCashflow > 0) {
					$h .= new \preaccounting\PreaccountingUi()->getLinkToReconciliate($eFarm, $nSuggestion);
				}
			$h .= '</div>';
			$h .= '<a href="/doc/accounting:bank" target="_blank" class="btn btn-xs btn-outline-primary">'.\asset::Icon('person-raised-hand').' '.s("Aide").'</a>';
		$h .= '</div>';

		return $h;

	}

	public function getTabs(\farm\Farm $eFarm, \Collection $cBankAccount, BankAccount $eBankAccountSelected): string {

		$getArgs = $_GET;
		unset($getArgs['origin']);
		unset($getArgs['farm']);
		$h = '<div class="tabs-item">';

			foreach($cBankAccount as $eBankAccount) {

				$isSelected = ($eBankAccount['id'] === $eBankAccountSelected['id']);

				$getArgs['bankAccount'] = $eBankAccount['id'];
				$h .= '<a class="tab-item'.($isSelected ? ' selected' : '').'" data-tab="'.$eBankAccount['id'].'" href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/operations?'.http_build_query($getArgs).'">';
					$label = '<div class="text-center">';
						if($eBankAccount['account']->notEmpty()) {
							$label .= s("Compte {value}", $eBankAccount['account']['class']);
						} else {
							$label .= s("Compte");
						}
						if($eBankAccount['description']) {
							$label .= '<br/><small><span style="font-weight: lighter" class="opacity-75">'.encode($eBankAccount['description']).'</span></small>';
						}
					$label .= '</div>';
					$h .= $label;
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;
	}


	public function list(
		\farm\Farm $eFarm,
		\Collection $cCashflow,
		\account\FinancialYear $eFinancialYear,
		Import $eImport,
		\Search $search,
		\Collection $cFinancialYear
	): string {

		\Asset::css('bank', 'cashflow.css');

		if($cCashflow->empty() === TRUE) {

			if($search->empty(['ids']) === FALSE) {

				return '<div class="util-empty">'.
					s("Aucune opération bancaire ne correspond à vos critères de recherche.").
					'</div>';

			}

			return '<div class="util-empty">'.
				s("Il n'y aucune opération bancaire à afficher.").
				'</div>';
		}

		$showMonthHighlight = str_starts_with($search->getSort(), 'date');
		$showReconciliate = $cCashflow->find(fn($e) => $e['isReconciliated'])->count() > 0;

		$h = '';

		if($eImport->exists() === TRUE) {

			$h .= '<div class="util-info">';
				$h .= s(
					"Vous visualisez actuellement les opérations bancaires correspondant à l'import #{id} du {date}.",
					[
						'id' => GET('import'),
						'date' => \util\DateUi::numeric($eImport['createdAt'], \util\DateUi::DATE),
					]
				);
			$h .= '</div>';
		}

		$h .= '<div id="cashflow-list" class="stick-md util-overflow-md">';

			$h .= '<table class="tr-even tr-hover td-padding-sm">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';

						$h .= '<th class="td-vertical-align-middle">';
							$label = s("Date");
							$h .= ($search ? $search->linkSort('date', $label) : $label);
						$h .= '</th>';
						$h .= '<th class="td-vertical-align-middle">';
							$label = s("Libellé");
							$h .= ($search ? $search->linkSort('memo', $label) : $label);
						$h .= '</th>';
						if($showReconciliate) {
							$h .= '<th class="text-center td-min-content">'.s("Rapproché ?").'</th>';
						}
						$h .= '<th class="text-end highlight-stick-right td-vertical-align-top hide-md-up td-min-content">'.s("Montant").'</th>';
						$h .= '<th class="text-end highlight-stick-right td-vertical-align-top hide-sm-down td-min-content">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left td-vertical-align-top hide-sm-down td-min-content">'.s("Crédit (C)").'</th>';
						if($eFarm->usesAccounting()) {
							$h .= '<th>'.s("Écritures comptables").'</th>';
						}
						$h .= '<th class="text-center td-vertical-align-middle"></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				$lastMonth = NULL;
				foreach($cCashflow as $eCashflow) {

					if(
						$showMonthHighlight and
						($lastMonth === NULL or $lastMonth !== substr($eCashflow['date'], 0, 7))
					) {

						$lastMonth = substr($eCashflow['date'], 0, 7);

							$h .= '<tr class="tr-title">';

								$h .= '<td class="td-min-content" colspan="7">';
									$h .= mb_ucfirst(\util\DateUi::textual($eCashflow['date'], \util\DateUi::MONTH_YEAR));
								$h .= '</td>';

							$h .= '</tr>';
					}

					$class = [];
					if($eCashflow['status'] === CashflowElement::DELETED) {
						$class[] = 'cashflow-strikethrough';
					}
					$h .= '<tr name="cashflow-'.$eCashflow['id'].'" class="'.join(' ', $class).'">';

						$h .= '<td class="td-vertical-align-top td-min-content">';
							$h .= \util\DateUi::numeric($eCashflow['date']);
						$h .= '</td>';

						$h .= '<td class="td-description td-vertical-align-top color-primary">';
							$h .= encode($eCashflow->getMemo());
						$h .= '</td>';

						if($showReconciliate) {
							$h .= '<td class="text-center td-vertical-align-top td-min-content">';
								if($eCashflow['isReconciliated']) {
									if($eCashflow['sale']->notEmpty()) {
										$h .= '<a href="/vente/'.$eCashflow['sale']['id'].'">'.encode($eCashflow['sale']['document']).'</a>';
									} else if($eCashflow['invoice']->notEmpty()) {
										$h .= '<a href="/ferme/'.$eFarm['id'].'/factures?invoice='.$eCashflow['invoice']['id'].'&customer='.encode($eCashflow['invoice']['customer']['name']).'">'.encode($eCashflow['invoice']['number']).'</a>';
									}
								}
							$h .= '</td>';
						}

						$h .= '<td class="text-end highlight-stick-right td-vertical-align-top hide-md-up">';
							$h .= \util\TextUi::money($eCashflow['amount']);
						$h .= '</td>';
						$h .= '<td class="text-end highlight-stick-right td-vertical-align-top hide-sm-down">';
							$h .= match($eCashflow['type']) {
								CashflowElement::DEBIT => \util\TextUi::money(abs($eCashflow['amount'])),
								default => '',
							};
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-left td-vertical-align-top hide-sm-down">';
							$h .= match($eCashflow['type']) {
								CashflowElement::CREDIT => \util\TextUi::money(abs($eCashflow['amount'])),
								default => '',
							};
						$h .= '</td>';

						if($eFarm->usesAccounting()) {

							$h .= '<td>';

								if($eCashflow['status'] === Cashflow::WAITING) {

									$canBeAdded = FALSE;
									foreach($cFinancialYear as $eFinancialYearCurrent) {
										if($eFinancialYearCurrent->acceptUpdate() and \account\FinancialYearLib::isDateInFinancialYear($eCashflow['date'], $eFinancialYearCurrent)) {
											$canBeAdded = TRUE;
											break;
										}
									}

									if($canBeAdded) {

										$h .= '<a data-dropdown="bottom-end" class="btn btn-outline-primary dropdown-toggle">'.s("Ajouter").'</a> ';

										$h .= '<div class="dropdown-list">';
											$h .= '<div class="dropdown-title">'.s("Ajouter des écritures comptables").'</div>';

												$financialYearOptions = [];
												foreach($cFinancialYear as $eFinancialYearCurrent) {

													if(
														$eFinancialYearCurrent->acceptUpdate() === FALSE or
														\account\FinancialYearLib::isDateInFinancialYear($eCashflow['date'], $eFinancialYearCurrent) === FALSE
													) {
														continue;
													}

													$financialYearOption = '<div class="dropdown-subtitle">'.s("Exercice {value}", $eFinancialYearCurrent->getLabel()).'</div>';
													$financialYearOption .= '<a href="'.\company\CompanyUi::urlBank($eFarm, $eFinancialYearCurrent).'/cashflow:allocate?id='.$eCashflow['id'].'" class="dropdown-item">';
														$financialYearOption .= s("Créer de nouvelles écritures");
													$financialYearOption .= '</a>';

													$financialYearOption .= '<a href="'.\company\CompanyUi::urlBank($eFarm, $eFinancialYearCurrent).'/cashflow:attach?id='.$eCashflow['id'].'" class="dropdown-item">';
														$financialYearOption .= s("Rattacher des écritures existantes");
													$financialYearOption .= '</a>';

													$financialYearOptions[] = $financialYearOption;
												}

											$h .= join('<div class="dropdown-divider"></div>', $financialYearOptions);
										$h .= '</div>';
									}

								} else if($eCashflow['status'] !== Cashflow::DELETED) {

									$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm, $eCashflow['cOperationHash']->first()['financialYear']).'/livre-journal?hash='.$eCashflow['cOperationHash']->first()['hash'].'">'.p("{value} écriture", "{value} écritures", $eCashflow['cOperationHash']->count()).'</a>';

								}
							$h .= '</td>';

						}

						$h .= '<td class="td-min-content text-center">';

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
								$h .= $this->getAction($eFarm, $eCashflow);

						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	protected function getAction(\farm\Farm $eFarm, Cashflow $eCashflow): string {

		if($eFarm->canManage() === FALSE) {
			return '';
		}

		$h = '<div class="dropdown-list">';

			if($eCashflow['status'] === CashflowElement::ALLOCATED) {

				$nOperation = $eCashflow['cOperationHash']->count();
				$isLinkedToAsset = $eCashflow['cOperationHash']->getColumnCollection('asset')->find(fn($e) => $e->notEmpty())->notEmpty();

				$h .= '<div class="dropdown-title">';
					$h .= p("Actions sur l'écriture comptable liée", "Actions sur les {value} écritures comptables liées", $nOperation);
				$h .= '</div>';

				if($isLinkedToAsset) {

					$h .= '<a class="dropdown-item inactive">';
						$h .= p(
							"Supprimer l'écriture",
							"Supprimer les {value} écritures",
							$nOperation
						);
						$h .= '<div class="operations-action-more">'.s("(Supprimez d'abord l'<b>immobilisation</b> liée)").'</div>';
					$h .= '</a>';

				} else {

					$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate?id='.$eCashflow['id'].'&action=delete" class="dropdown-item">';
					$h .= p(
						"Supprimer l'écriture",
						"Supprimer les {value} écritures",
						$nOperation
					);
					$h .= '</a>';

				}

				$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate?id='.$eCashflow['id'].'&action=dissociate" class="dropdown-item" >';
					$h .= p("Dissocier sans supprimer l'écriture", "Dissocier sans supprimer les écritures<br/>de l'opération bancaire", $nOperation - 1);
				$h .= '</a>';

				$h .= '<div class="dropdown-divider"></div>';

			}

			if($eCashflow->acceptCancelReconciliation()) {

				$nOperation = $eCashflow['cOperationHash']->count();
				if($nOperation === 0) {
					$confirm = s("Le lien entre la facture et l'opération bancaire sera supprimé. Confirmez-vous cette action ?");
				} else {
					$confirm = s("Cette action supprimera le rapprochement entre la facture et l'opération bancaire mais ne supprimera pas les écritures comptables créées. Vous pourrez les supprimer manuellement. Confirmez-vous cette action ?");
				}
				$reconciliate = '<a data-ajax="'.\farm\FarmUi::urlConnected($eFarm).'/preaccounting/reconciliate:cancel"  post-cashflow="'.$eCashflow['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
					$reconciliate .= s("Annuler le rapprochement");
				$reconciliate .= '</a>';

			} else {

				$reconciliate = '';

			}


			if($eCashflow['status'] === Cashflow::DELETED) {

				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:undoDelete"  post-id="'.$eCashflow['id'].'" class="dropdown-item">';
					$h .= s("Annuler la suppression de l'opération bancaire");
				$h .= '</a>';

			} else if($eCashflow['status'] === Cashflow::ALLOCATED) {

				$h .= '<div class="dropdown-title">'.s("Actions sur l'opération bancaire").'</div>';

				$h .= $reconciliate;

				$deleteText = s("Supprimer l'opération bancaire<div>(Supprimez d'abord les <b>écritures comptables</b> liées)</div>", ['div' => '<div class="operations-action-more">']);
				$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';

			} else {

				$h .= '<div class="dropdown-title">'.s("Actions sur l'opération bancaire").'</div>';

				$h .= $reconciliate;

				if($eCashflow->acceptDelete()) {

					$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:doDelete"  post-id="'.$eCashflow['id'].'" class="dropdown-item">';
						$h .= s("Supprimer l'opération bancaire");
					$h .= '</a>';

				}

			}

		$h .= '</div>';

		return $h;

	}

	public static function getCashflowHeader(Cashflow $eCashflow): string {

		$type = match($eCashflow['type']) {
			CashflowElement::CREDIT => s("Crédit"),
			CashflowElement::DEBIT => s("Débit"),
			default => '',
		};

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Numéro").'</dt>';
				$h .= '<dd>'.$eCashflow['id'].'</dd>';
				$h .= '<dt>'.s("Date").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eCashflow['date'], \util\DateUi::DATE).'</dd>';
				$h .= '<dt>'.s("Libellé").'</dt>';
				$h .= '<dd>'.encode($eCashflow->getMemo()).'</dd>';
				$h .= '<dt>'.s("Type").'</dt>';
				$h .= '<dd>'.$type.'</dd>';
				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>';
					$h .= '<span name="cashflow-amount" class="hide">'.$eCashflow['amount'].'</span>';
					$h .= '<span>'.\util\TextUi::money($eCashflow['amount']).'</span>';
				$h .= '</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;
	}

	public static function extractPaymentTypeFromCashflowDescription(string $description, \Collection $cPaymentMethod): ?\payment\Method {

		if(mb_strpos(mb_strtolower($description), 'carte') !== FALSE) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'card')->first();
		}

		if(
			mb_strpos(mb_strtolower($description), 'prélèvement') !== FALSE
			or mb_strpos(mb_strtolower($description), 'prelevement') !== FALSE
			or mb_strpos(mb_strtolower($description), 'prlv') !== FALSE
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'direct-debit')->first();
		}

		// Après les prélèvements car on capte SEPA (où y'a parfois écrit SEPA)
		if(
			mb_strpos(mb_strtolower($description), 'virement') !== FALSE
			or mb_strpos(mb_strtolower($description), 'sepa') !== FALSE
			or mb_strpos(mb_strtolower($description), 'vir') === 0
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'transfer')->first();
		}

		if(
			mb_strpos(mb_strtolower($description), 'chèque') !== FALSE
			or mb_strpos(mb_strtolower($description), 'cheque') !== FALSE
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'check')->first();
		}

		if(
			mb_strpos(mb_strtolower($description), 'espèce') !== FALSE
			or mb_strpos(mb_strtolower($description), 'espece') !== FALSE
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'cash')->first();
		}

		return new \payment\Method();

	}

	public function getAllocateTitle(Cashflow $eCashflow): string {

		$amount = '<span class="util-badge bg-primary" style="padding-left: .75rem; padding-right: .75rem">'.\util\TextUi::money(abs($eCashflow['amount'])).'</span>';

		$title = '<div class="panel-title-container">';
				$title .= '<h2 class="panel-title">'.s(
				"Opération bancaire de {amount} du {date}</b>",
				[
					'amount' => $amount,
					'date' => \util\DateUi::numeric($eCashflow['date'], \util\DateUi::DATE),
				]
			).'</h2>';
			$title .= '<a class="panel-close-desktop" onclick="Lime.Panel.closeLast()">'.\Asset::icon('x').'</a>';
			$title .= '<a class="panel-close-mobile" onclick="Lime.Panel.closeLast()">'.\Asset::icon('arrow-left-short').'</a>';
		$title .= '</div>';

		$subtitle = '<h2 class="panel-subtitle">'.encode($eCashflow->getMemo()).'</h2>';

		return $title.$subtitle;

	}

	public function getDeallocate(\farm\Farm $eFarm, Cashflow $eCashflow, \Collection $cOperation, \Collection $cInvoice, string $action): \Panel {

		\Asset::css('bank', 'cashflow.css');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlBank($eFarm).'/cashflow:doDeallocate',
			[
				'id' => 'bank-cashflow-deallocate', 'class' => 'panel-dialog',]
		);

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('action', $action);

		$cOperationBank = $cOperation->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS));

		if($action === 'delete') {

			$h .= '<h3 class="mt-2">';
				$h .= p("Écriture comptable liée à cette opération bancaire", "Écritures comptables liées à cette opération bancaire", $cOperation->count());
			$h .= '</h3>';

			$h .= '<div class="bg-background">';
				$h .= new \journal\JournalUi()->list($eFarm, NULL, $cOperation, $eFarm['eFinancialYear'], readonly: TRUE);
			$h .= '</div>';

		} else {

			$h .= '<h3 class="mt-2">';
				$h .= p(
					"Écriture comptable du compte banque {bankAccount} liée à cette opération bancaire qui sera supprimée",
					"Écritures comptables du compte banque {bankAccount} liées à cette opération bancaire qui seront supprimées",
					$cOperationBank->count(), ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);
			$h .= '</h3>';

			$h .= '<div class="bg-background">';
				$h .= new \journal\JournalUi()->list($eFarm, NULL, $cOperationBank, $eFarm['eFinancialYear'], readonly: TRUE);
			$h .= '</div>';


			$cOperationFiltered = $cOperation->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS) === FALSE);

			$h .= '<h3 class="mt-2">';
				$h .= p(
					"Autre écriture comptable qui sera isolée",
					"Autres écritures comptables liées à cette opération bancaire qui seront groupées ensemble",
					$cOperationFiltered->count(), ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);
			$h .= '</h3>';

			$h .= '<div class="bg-background">';
				$h .= new \journal\JournalUi()->list($eFarm, NULL, $cOperationFiltered, $eFarm['eFinancialYear'], readonly: TRUE);
			$h .= '</div>';

		}

		if($cInvoice->notEmpty()) {

			$eInvoice = $cInvoice->first();

				$h .= '<div class="util-info">';
					$h .= s("Cette opération bancaire est rapprochée avec la facture {name}.", ['name' => '<a href="'.\farm\FarmUi::urlSellingInvoices($eFarm).'?invoice='.$eInvoice['id'].'">'.encode($eInvoice['number']).'</a>']);
					$h .= ' '.s("Ce rapprochement entre la facture et l'opération bancaire sera conservé.");
				$h .= '</div>';

		}

		if($action === 'delete') {
			$submit = s("Confirmer la suppression");
			$waiter = s("Suppression en cours");
			$title = p("Supprimer l'écriture comptable liée à une opération bancaire", "Supprimer les écritures comptables liées à une opération bancaire", $cOperation->count());
		} else {
			$submit = s("Confirmer la dissociation");
			$waiter = s("Dissociation en cours...");
			$title = p("Dissocier l'écriture comptable liée à une opération bancaire", "Dissocier les écritures comptables liées à une opération bancaire", $cOperation->count());
		}

		$h .= $form->submit(
			$submit,
			[
				'id' => 'submit-deallocate',
				'class' => 'btn btn-primary',
				'data-waiter' => $waiter,
			],
		);

		return new \Panel(
			id         : 'panel-bank-cashflow-allocate',
			title      : $title,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			header     : $this->getAllocateTitle($eCashflow),
		);

	}

	public function getAllocateGeneralPayment(\account\FinancialYear $eFinancialYear, \Collection $cPaymentMethod, array $defaultValues, \util\FormUi $form, string $for): string {

		$hasPaymentMethod = ($for === 'update' and
			$defaultValues['paymentMethod'] !== NULL and
			$defaultValues['paymentMethod']->notEmpty()
		);
		$h = '<div class="cashflow-create-operation-general">';
			$h .= '<div class="cashflow-operation-create-title">'.\journal\OperationUi::p('paymentDate').'</div>';
			$h .= $form->date(
				'paymentDate',
					$defaultValues['paymentDate'] ?? '',
				['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']] + ($for == 'update' ? ['disabled' => 'disabled'] : []),
			);
			$h .= '<div class="cashflow-operation-create-title">'.\journal\OperationUi::p('paymentMethod').'</div>';
			$h .= $form->select(
				'paymentMethod',
				$cPaymentMethod,
					$defaultValues['paymentMethod'] ?? '',
				['mandatory' => TRUE] + ($hasPaymentMethod ? ['disabled' => 'disabled'] : []),
			);
		$h .= '</div>';

		return $h;
	}

	public function getAllocate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, \Collection $cPaymentMethod, \Collection $cJournalCode): \Panel {

		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'amount.js');
		\Asset::js('bank', 'cashflow.js');
		\Asset::js('account', 'thirdParty.js');

		\Asset::css('journal', 'operation.css');
		\Asset::css('bank', 'cashflow.css');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlBank($eFarm).'/cashflow:doAllocate',
			[
				'id' => 'bank-cashflow-allocate',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog',
				'data-has-vat' => (int)$eFinancialYear['hasVat'],
			]
		);

		$eOperation = new \journal\Operation([
			'account' => new \account\Account(),
			'cOperationCashflow' => new \Collection(['cashflow' => $eCashflow]),
			'cJournalCode' => $cJournalCode,
		]);
		$index = 0;
		$defaultValues = [
			'date' => $eCashflow['date'],
			'amount' => abs($eCashflow['amount']),
			'type' => $eCashflow['type'],
			'description' => $eCashflow->getMemo(),
			'paymentDate' => $eCashflow['date'],
			'paymentMethod' => self::extractPaymentTypeFromCashflowDescription($eCashflow->getMemo(), $cPaymentMethod->filter(fn($e) => $e['use']->value(\payment\Method::ACCOUNTING))),
			'amountIncludingVAT' => abs($eCashflow['amount']),
		];

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('type', $eCashflow['type']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);
		$h .= '<span name="cashflow-amount" class="hide">'.$eCashflow['amount'].'</span>';

		$h .= $this->getAllocateGeneralPayment($eFinancialYear,  $cPaymentMethod, $defaultValues, $form, 'copy');

		// Proposer de partir d'une opération similaire
		$eCashflowCopySelected = new Cashflow();
		if(count($eCashflow['similar']) > 0) {

			$eCashflowCopySelected = $eCashflow['similar']->find(fn($e) => $e['id'] === GET('copy', 'int'))->first() ?? new Cashflow();

			if($eCashflowCopySelected->notEmpty()) {

				$h .= ' <a class="btn btn-md btn-outline-primary mt-1 mb-1" href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:allocate?id='.$eCashflow['id'].'">';
					$h .= s("Ne plus repartir d'une opération similaire");
				$h .= '</a>';

			} else {

				$values = [];

				foreach($eCashflow['similar'] as $eCashflowSimilar) {

					$eOperationBank = $eCashflowSimilar['cOperation']->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS))->first() ?? new \journal\Operation();

					if($eOperationBank->notEmpty()) {
						$label = s("{thirdParty} à {amount}", ['thirdParty' => encode($eOperationBank['thirdParty']['name']), 'amount' => \util\TextUi::money(abs($eCashflowSimilar['amount']))]);
						$values[] = ['id' => $eCashflowSimilar['id'], 'label' => $label];
					}

				}

				$h .= '<a class="dropdown-toggle btn btn-md btn-outline-primary mt-1 mb-1" data-dropdown="bottom-start">'.s("Repartir d'une opération similaire").'</a>';

				$h .= '<div class="dropdown-list">';

					foreach($values as $value) {

						$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:allocate?id='.$eCashflow['id'].'&copy='.$value['id'].'" class="dropdown-item">';
							if($eCashflowCopySelected->notEmpty()) {
								$h .= \Asset::icon('check-lg').' ';
							}
							$h .= $value['label'];
						$h .= '</a>';

					}

				$h .= '</div>';

			}

		}

		if($eCashflowCopySelected->notEmpty()) {

			$eOperationBase = $eCashflowCopySelected['cOperation']->find(fn($e) => $e['operation']->empty())->first();
			$eOperationBase['cJournalCode'] = $cJournalCode;

			$cOperationFormatted = new \journal\OperationUi()->formatOperationForUpdate($eCashflow, $eCashflowCopySelected['cOperation'], $eOperationBase, 'copy');

			$h .= \journal\OperationUi::getUpdateGrid(
				eFinancialYear: $eFinancialYear,
				form: $form,
				cPaymentMethod: $cPaymentMethod,
				cOperation: $cOperationFormatted,
				eCashflow: $eCashflow,
				eOperationRequested: new \journal\Operation(),
				for: 'copy',
			);

			$thirdParty = $eOperationBase['thirdParty']['id'] ?? '';

		} else {

			$h .= \journal\OperationUi::getCreateGrid($eFarm, $eOperation, $eCashflow, $eFinancialYear, $index, $form, $defaultValues, $cPaymentMethod);
			$thirdParty = '';

		}

		$amountWarning = '<div id="cashflow-allocate-difference-warning" class="util-danger hide">';
			$amountWarning .= s("Attention, les montants saisis doivent correspondre au montant total de la transaction. Il y a une différence de {difference}.", ['difference' => '<span id="cashflow-allocate-difference-value">0</span>']);
		$amountWarning .= '</div>';

		$addButton = '<a id="add-operation" data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:addAllocate" post-index="'.($index + 1).'" post-id="'.$eCashflow['id'].'" post-third-party="'.$thirdParty.'" post-amount="" class="btn btn-outline-secondary">';
		$addButton .= \Asset::icon('plus-circle').'&nbsp;'.s("Ajouter une autre écriture");
		$addButton .= '</a>';

		$saveButton = $form->submit(
			s("Enregistrer l'écriture"),
			[
				'id' => 'submit-save-operation',
				'class' => 'btn btn-primary',
				'data-text-singular' => s("Enregistrer l'écriture"),
				'data-text-plural' => s("Enregistrer les écritures"),
				'data-confirm-text' => s("Il y a une incohérence entre les écritures saisies et le montant de l'opération bancaire. Voulez-vous vraiment les enregistrer tel quel ?"),
			],
		);

		return new \Panel(
			id         : 'panel-bank-cashflow-allocate',
			title      : s("Créer des écritures pour une opération bancaire"),
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			header     : $this->getAllocateTitle($eCashflow),
			footer     : $amountWarning.'<div class="operation-create-buttons">'.$saveButton.$addButton.'</div>',
		);

	}

	public function getCopy(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, array $similar): \Panel {

		$form = new \util\FormUi();

		$h = '<div class="util-info">';
			$h .= p(
				"Un schéma d'écriture correspondant à cette opération bancaire a été trouvé. S'il correspond à l'opération que vous traitez actuellement, choisissez-le et les écritures seront copiées à l'identique.",
				"{value} schémas d'écritures correspondant à cette opération bancaire ont été trouvés. Si l'un d'eux correspond à l'opération que vous traitez actuellement, choisissez-le et les écritures seront copiées à l'identique.",
				count($similar),
			);
			$h .= '<p>';
				$h .= s("Si nécessaire, vous pourrez modifier les écritures nouvellement créées comme habituellement : pièce comptable, tiers, libellé etc.");
			$h .= '</p>';
		$h .= '</div>';

		$h .= '<h3>'.p("Schéma trouvé", "Schémas trouvés", count($similar)).'</h3>';

		$h .= '<table id="bank-cashflow-copy-table">';

			foreach($similar as $key => $cOperation) {

				$h .= '<tr>';

					$h .= '<td>';
						$h .= new \journal\JournalUi()->list($eFarm, NULL, $cOperation, $eFarm['eFinancialYear'], readonly: TRUE);
					$h .= '</td>';

					$h .= '<td>';
						$attributes = [
							'data-ajax' => \company\CompanyUi::urlBank($eFarm).'/cashflow:doCopy',
							'post-id' => $eCashflow['id'],
							'post-key' => $key,
							'post-copy' => $cOperation->first()['cashflow']['id'],
							'class' => 'btn btn-primary',
						];
						$h .= '<a '.attrs($attributes).'>';
							$h .= s("Choisir ce schéma");
						$h .= '</a>';
					$h .= '</td>';

				$h .= '</tr>';
			}

		$h .= '</table>';

		$h .= $form->close();

		return new \Panel(
			id         : 'panel-bank-cashflow-copy',
			title      : s("Copier une structure d'écritures comptables"),
			body       : $h,
			header     : $this->getAllocateTitle($eCashflow),
		);

	}

	public static function addAllocate(\farm\Farm $eFarm, \journal\Operation $eOperation, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, int $index, \Collection $cPaymentMethod): string {

		$form = new \util\FormUi();
		$form->open('bank-cashflow-allocate');
		$defaultValues = [
			'date' => $eCashflow['date'],
			'type' => $eCashflow['type'],
			'description' => $eCashflow->getMemo(),
		];

		return \journal\OperationUi::getFieldsCreateGrid($form, $eOperation, $eCashflow, $eFinancialYear, '['.$index.']', $defaultValues, [], $cPaymentMethod);

	}

	public static function import(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = '';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Seul l'import bancaire au format <b>OFX</b> est actuellement supporté et ce format est disponible sur la plupart des sites bancaires.").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Si certains flux bancaires ont déjà été précédemment importés, ils seront ignorés.").'</li>';
				$h .= '<li>'.s("Si le compte bancaire est inconnu, il sera automatiquement créé et vous pourrez paramétrer son libellé dans le <link>paramétrage des comptes bancaires</link>.", ['link' => '<a href="'.\company\CompanyUi::urlBank($eFarm).'/account">']).'</li>';
			$h .= '</ul>';
		$h .= '</div>';

		$h .= $form->openUrl(\farm\FarmUi::urlConnected($eFarm).'/banque/imports:doImport', ['id' => 'cashflow-import', 'binary' => TRUE, 'method' => 'post']);

		$h .= $form->hidden('farm', $eFarm['id']);

			$h .= '<label class="btn btn-primary">';
				$h .= $form->file('ofx', ['onchange' => 'this.form.submit()', 'accept' => '.ofx']);
				$h .= s("Choisir un fichier OFX sur mon ordinateur");
			$h .= '</label>';
		$h .= $form->close();

		return new \Panel(
			id: 'panel-cashflow-import',
			title: s("Importer un relevé bancaire"),
			body: $h
		);
	}

	public function getAttach(\farm\Farm $eFarm, Cashflow $eCashflow, \account\ThirdParty $eThidParty, ?string $tip): \Panel {

		\Asset::js('bank', 'cashflow.js');
		\Asset::css('bank', 'cashflow.css');

		$h = '';

		if($tip) {
			$h .= new \farm\TipUi()->get($eFarm, $tip, 'inline');
		}

		$h .= CashflowUi::getCashflowHeader($eCashflow);

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(\company\CompanyUi::urlBank($eFarm).'/cashflow:doAttach', ['method' => 'post', 'id' => 'cashflow-doAttach', 'class' => 'panel-dialog']);

		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('cashflow-amount', $eCashflow['amount']);

		$h .= '<div class="mt-1 mb-3">';

			$h .= $form->group(
				s("Tiers").\util\FormUi::asterisk(),
				$form->dynamicField(new \journal\Operation(['thirdParty' => $eThidParty]), 'thirdParty', function($d) use($form, $eThidParty) {
					$d->autocompleteDispatch = '[data-third-party="'.$form->getId().'"]';
					$d->attributes['data-third-party'] = $form->getId();
					$d->default = fn($e, $property) => GET('thirdParty');
					$d->label = '';
				}),
				['wrapper' => 'third-party']
			);
		$h .= '</div>';

		$h .= $form->group(
			s("Écritures").\util\FormUi::asterisk(),
			$form->dynamicField(new \journal\OperationCashflow(), 'operation', function($d) use($form, $eCashflow) {
				$d->autocompleteDispatch = '[data-operation="'.$form->getId().'"]';
				$d->attributes['data-operation'] = $form->getId();
				$d->default = fn($e, $property) => get('operation');
				$d->label = '';
				$d->wrapper = 'operation';
				$d->autocompleteBody = function() {
					return [
					];
				};
				new \journal\OperationUi()->query($d, GET('farm', 'farm\Farm'), $eCashflow);
			}),
			['wrapper' => 'operation']
		);

		$h .= '<div class="stick-sm util-overflow-sm" data-operations>';
			$h .= $this->getSelectedOperationsTableForAttachement($eCashflow);
		$h .= '</div>';

		$h .= '<div id="cashflow-attach-information"></div>';
		$h .= '<div class="cashflow-attach-panel-validate" onrender="CashflowAttach.reloadFooter();">';
				$h .= $form->submit(s("Rattacher"), ['class' => 'btn btn-secondary']);
			$h .= '<div>'.s("Total sélectionné : {value}", '<span data-field="totalAmount" class="mr-2">'.\util\TextUi::money(0).'</span>').'</div>';
			$h .= '</div>';

		return new \Panel(
			id: 'panel-cashflow-attach',
			title: s("Rattacher l'opération bancaire"),
			body: $h,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
		);

	}

	public function getSelectedOperationsTableForAttachement(Cashflow $eCashflow, \Collection $cOperation = new \Collection()): string {

		$h = '<table id="cashflow-operations" class="tr-even mt-2 tr-hover '.($cOperation->empty() ? 'hide' : '').'">';

			$h .= '<thead>';
				$h .= '<tr class="tr-header">';
					$h .= '<th>';
						$h .= s("Date");
					$h .= '</th>';
					$h .= '<th class="text-center">';
						$h .= s("Numéro de compte");
					$h .= '</th>';
					$h .= '<th>';
						$h .= s("Description");
					$h .= '</th>';
					$h .= '<th>'.s("Tiers").'</th>';
					$h .= '<th>'.s("Moyen de paiement").'</th>';
					$h .= '<th class="text-end">'.s("Débit (D)").'</th>';
					$h .= '<th class="text-end">'.s("Crédit (C)").'</th>';
					$h .= '<th class="text-center"></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cOperation as $eOperation) {
					$h .= CashflowUi::getOperationLineForAttachment($eCashflow, $eOperation);
				}

			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

	public static function getOperationLineForAttachment(Cashflow $eCashflow, \journal\Operation $eOperation): string {

		$form = new \util\FormUi();

		$amount = 0;
		foreach($eOperation['cOperationHash'] as $eOperationHash) {
			if(\account\AccountLabelLib::isFromClass($eOperationHash['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS)) {
				continue;
			}
			$amount += $eOperationHash['type'] === \journal\Operation::DEBIT ?  -1 * $eOperationHash['amount'] : $eOperationHash['amount'];
		}

		$h = '<tr data-operation="'.$eOperation['id'].'">';
			$h .= '<td '.($eCashflow['date'] < $eOperation['date'] ? 'class="color-warning" title="'.s("Attention, l'opération bancaire est antérieure à l'écriture comptable").'"' : '').'>';
					$h .= \util\DateUi::numeric($eOperation['date']);
					if($eCashflow['date'] < $eOperation['date']) {
						$h .= ' '.\Asset::icon('exclamation-triangle');
					}
			$h .= '</td>';
			$h .= '<td class="text-center">';
				$h .= encode($eOperation['accountLabel']);
			$h .= '</td>';
			$h .= '<td>';
				$h .= encode($eOperation['description']);
			$h .= '</td>';

			$h .= '<td>';
			if($eOperation['thirdParty']->notEmpty()) {
				$h .= encode($eOperation['thirdParty']['name']);
			}
			$h .= '</td>';

			$h .= '<td>';
				$h .= \payment\MethodUi::getName($eOperation['paymentMethod']);
			$h .= '</td>';

			$h .= '<td class="text-end">';
				$h .= $amount > 0 ? \util\TextUi::money($amount) : '';
			$h .= '</td>';

			$h .= '<td class="text-end">';
				$h .= $amount < 0 ? \util\TextUi::money(abs($amount)) : '';
			$h .= '</td>';

			$h .= '<td class="text-center">';
				$h .= $form->hidden('operations[]', $eOperation['id']);
				$h .= $form->hidden('amounts[]', $amount);
				$h .= '<span class="btn btn-outline-danger btn-xs" onclick="CashflowAttach.removeOperation('.$eOperation['id'].')">'.\Asset::icon('trash').'</span>';
			$h .= '</td>';
		$h .= '</tr>';

		return $h;

	}
	public function getReconciliateInfo(\farm\Farm $eFarm, Import $eImport): string {

		if($eImport->empty() or $eImport['reconciliation'] === Import::DONE or $eImport['nCashflowWaiting'] === 0) {
			return '';
		}

		$h = '<div class="util-block-info">';
			$h .= \Asset::icon('fire', ['class' => 'util-block-icon']);
			$h .= '<h3>'.s("Rapprochement bancaire").'</h3>';
			$h .= '<p>';
				if($eImport['reconciliation'] === Import::PROCESSING) {
					$h .= s("Le rapprochement bancaire de votre dernier import est en cours de calcul.");
					$h .= '<br/>'.p("Il reste {value} opération à vérifier.", "Il reste {value} opérations à vérifier.", $eImport['nCashflowWaiting']);
				} else {
					$h .= s("Le rapprochement bancaire de votre dernier import est en attente de calcul.");
					$h .= '<br/>'.p("Il y a {value} opération à vérifier.", "Il y a {value} opérations à vérifier.", $eImport['nCashflowWaiting']);
				}
			$h .= '</p>';
			$h .= '<p>';
				if($eImport['reconciliation'] === Import::WAITING) {
					$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/banque/operations" class="btn btn-transparent">'.s("Actualiser").'</a>';
				}
			$h .= '</p>';
		$h .= '</div>';

		return $h;

	}

	public static function getName(Cashflow $eCashflow): string {

		return s("Transaction #{id}", ['id' => $eCashflow['id']]);

	}

	public static function getAutocomplete(\bank\Cashflow $eCashflow): array {

		\Asset::css('media', 'media.css');

		$itemHtml = '<div style="display: flex; gap: 0.5rem;">';
			$itemHtml .= '<div class="text-end">';
				$itemHtml .= \util\DateUi::numeric($eCashflow['date']);
			$itemHtml .= '</div>';
			$itemHtml .= '<div>';
				$itemHtml .= s("{direction} de {amount}", [
					'direction' => $eCashflow['amount'] > 0 ? s("Crédit") : s("Débit"),
					'amount' => \util\TextUi::money(abs($eCashflow['amount'])),
				]);
				$itemHtml .= '<br />';
				$itemHtml .= encode($eCashflow->getMemo());
			$itemHtml .= '</div>';
		$itemHtml .= '</div>';

		return [
			'value' => $eCashflow['id'],
			'itemText' => encode($eCashflow->getMemo()).s(" ({amount} au {type})", ['amount' => \util\TextUi::money(abs($eCashflow['amount'])), 'type' => $eCashflow['amount'] > 0 ? s("crédit") : s("débit")]),
			'itemHtml' => $itemHtml,
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('piggy-bank');
		$item .= '<div>'.s("Aucune opération bancaire trouvée (cliquer pour voir toutes les opérations bancaires non rattachées)").'</div>';

		return [
			'type' => 'link',
			'link' => \farm\FarmUi::urlConnected($eFarm).'/banque/operations?isReconciliated=0',
			'itemHtml' => $item,
		];

	}
	public function query(\PropertyDescriber $d, \farm\Farm $eFarm, \Collection $cOperation, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('journal-bookmark');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Opération bancaire...");
		$d->multiple = $multiple;

		$operations = join('&operations[]=', $cOperation->getIds());
		$d->autocompleteUrl = \company\CompanyUi::urlBank($eFarm).'/cashflow:query?operations[]='.$operations;

		$d->autocompleteResults = function(Cashflow $eCashflow) {
			return self::getAutocomplete($eCashflow);
		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Cashflow::model()->describer($property, [
			'date' => s("Date"),
			'type' => s("Type"),
			'amount' => s("Montant"),
			'fitid' => s("Id transaction"),
			'name' => s("Nom"),
			'memo' => s("Libellé"),
			'account' => s("Compte bancaire"),
			'cashflow' => s("Flux"),
		]);

		switch($property) {

			case 'date' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'type':
				$d->values = [
					CashflowElement::CREDIT => s("Crédit"),
					CashflowElement::DEBIT => s("Débit"),
				];
				break;

			case 'status' :
				$d->attributes['placeholder'] = s("Peu importe");
				$d->values = [
					CashflowElement::WAITING => s("Sans écriture comptable"),
					CashflowElement::ALLOCATED => s("Avec écriture comptable"),
					CashflowElement::DELETED => s("Supprimée"),
				];
				break;
		}

		return $d;

	}
}

?>
