<?php
namespace journal;

class JournalUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
		\Asset::css('company', 'company.css');
		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'amount.js');
	}

	public function getJournalTitle(\farm\Farm $eFarm, bool $hasSearch = TRUE): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$h = new \farm\FarmUi()->getAccountingYears($eFarm);
		$h .= '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Le livre journal");
			$h .= '</h1>';

			if($eFinancialYear->notEmpty()) {

				$h .= '<div class="journal-title-buttons">';

					if($hasSearch) {
						$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#journal-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
					}

					if(
						GET('financialYear') !== '0' and // Cas où on regarde tous les exercices
						get_exists('cashflow') === FALSE and
						$eFinancialYear['status'] === \account\FinancialYearElement::OPEN and
						$eFarm->canManage()
					) {
						$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create?journalCode='.GET('journalCode').'" class="btn btn-primary">';
							$h .= \Asset::icon('plus-circle').' '.s("Enregistrer une écriture");
						$h .= '</a>';
					}

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getBaseUrl(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {
		return \company\CompanyUi::urlJournal($eFarm, $eFinancialYear).'/livre-journal';
	}
	public function getSearch(\farm\Farm $eFarm, \Search $search, \account\FinancialYear $eFinancialYearSelected, \bank\Cashflow $eCashflow, ?\account\ThirdParty $eThirdParty, \Collection $cPaymentMethod, int $nUnbalanced): string {

		$hideSearch = ($search->empty(['ids']) and $search->get('hasDocument') === NULL and $search->get('needsAsset') === NULL and $search->get('cashflowFilter') === NULL);

		$h = '<div id="journal-search" class="util-block-search '.($hideSearch ? 'hide' : '').'">';

			$form = new \util\FormUi();

			if(get_exists('financialYear')) {
				$eFinancialYearSelected = new \account\FinancialYear(['id' => GET('financialYear')]);
				$minDate = NULL;
				$maxDate = NULL;
			} else {
				$minDate = $eFinancialYearSelected['startDate'];
				$maxDate = $eFinancialYearSelected['endDate'];
			}

			$url = $this->getBaseUrl($eFarm, $eFinancialYearSelected);

			$statuses = OperationUi::p('type')->values;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search util-search-3']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Période").'</legend>';
					$h .= $form->inputGroup(
						$form->date('periodStart', $search->get('periodStart'), ['min' => $minDate, 'max' => $maxDate, 'placeholder' => s("Début")]).
						$form->addon(s("à")).
						$form->date('periodEnd', $search->get('periodEnd'), ['min' => $minDate, 'max' => $maxDate, 'placeholder' => s("Fin")])
					);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Type").'</legend>';
					$h .= $form->select('type', $statuses, $search->get('type'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Numéro de compte").'</legend>';
					$h .= $form->text('accountLabel', $search->get('accountLabel'), ['placeholder' => s("Numéro de compte")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Description").'</legend>';
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Description")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Moyen de paiement").'</legend>';
					$h .= $form->select('paymentMethod', $cPaymentMethod, $search->get('paymentMethod'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Tiers").'</legend>';
					$h .= $form->dynamicField(new Operation(['thirdParty' => $eThirdParty]), 'thirdParty', function($d) use($form) {
						$d->autocompleteDispatch = '[data-third-party="search"]';
						$d->attributes['data-index'] = 0;
						$d->attributes['data-third-party'] = 'search';
					});
				$h .= '</fieldset>';
				$h .= '<fieldset class="journal-search-document">';
					$h .= '<legend>'.s("Pièce comptable").'</legend>';
					$h .= $form->inputGroup(
						$form->select('hasDocument', [
							0 => s("Sans"),
							1 => s("Avec"),
						], $search->get('hasDocument'), ['placeholder' => s("Avec ou sans")]).
						$form->text('document', $search->get('document'), ['placeholder' => s("Nom de la pièce comptable")])
					);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Opération bancaire").'</legend>';
					$h .= $form->select('cashflowFilter', [
						1 => s("Avec"),
						0 => s("Sans"),
					], $search->get('cashflowFilter') === NULL ? '' : (int)$search->get('cashflowFilter'), ['placeholder' => s("Avec ou sans")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Immobilisation").'</legend>';
					$h .= $form->select('needsAsset', [
						NULL => s("Toutes les écritures"),
						0 => s("Immobilisation avec fiche"),
						1 => s("Immobilisation sans fiche"),
					],  $search->get('needsAsset'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';
				if($nUnbalanced > 0) {
					$h .= '<div class="util-search-fill text-end">';
						$h .= \Asset::icon('search').' '.'<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?unbalanced=1">'.p("Retrouver {value} groupe d'écritures déséquilibrés", "Retrouver les {value} groupes d'écritures déséquilibrés", $nUnbalanced, ['value' => '<span class="util-badge bg-primary">'.$nUnbalanced.'</span>']).'</a>';
					$h .= '</div>';
				}
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= ' <a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($eCashflow->exists() === TRUE) {
			$h .= '<div class="util-info">';
				$h .= s(
					"Vous visualisez actuellement les écritures correspondant à l'opération bancaire du {date}, \"{memo}\" d'un {type} de {amount} (<link>annuler le filtre</link>).",
					[
						'date' => \util\DateUi::numeric($eCashflow['date']),
						'memo' => encode($eCashflow->getMemo()),
						'type' => mb_strtolower(\bank\CashflowUi::p('type')->values[$eCashflow['type']]),
						'amount' => \util\TextUi::money(abs($eCashflow['amount'])),
						'link' => '<a href="'.$url.'">',
					]
				);
			$h .= '</div>';
		}

		return $h;

	}

	public function getJournalTabs(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Search $search, ?string $selectedJournalCode, \Collection $cJournalCode): string {

		$args = $search->toQuery(exclude: ['code']);

		$h = '<div class="tabs-item">';

			$h .= '<style>';
				foreach($cJournalCode as $eJournalCode) {
					if($eJournalCode['color']) {

						$h .= '.journal-code-'.$eJournalCode['id'].'.selected { color: white; background-color: '.$eJournalCode['color'].';}';
						$h .= '.journal-code-'.$eJournalCode['id'].':not(.selected):hover { color: white; background-color: '.$eJournalCode['color'].';}';

					}	 else {

						$h .= '.journal-code-'.$eJournalCode['id'].'.selected { color: white; background-color: var(--secondary);}';
						$h .= '.journal-code-'.$eJournalCode['id'].':not(.selected):hover { color: white; background-color: var(--secondary);}';

					}
				}
			$h .= '</style>';

			$h .= '<a class="tab-item '.($selectedJournalCode === NULL ? ' selected' : '').'" data-tab="journal" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?'.$args.'">';
				$h .= '<div class="text-center">';
					$h .= s("Général");
				$h .= '</div>';
			$h .= '</a>';

			$h .= '<a class="tab-item'.($selectedJournalCode === \journal\JournalSetting::JOURNAL_CODE_BANK ? ' selected' : '').'" data-tab="journal-'.\journal\JournalSetting::JOURNAL_CODE_BANK.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.\journal\JournalSetting::JOURNAL_CODE_BANK.'&'.$args.'">';
				$h .= '<div class="text-center">';
					$h .= s("Banque");
					$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("BAN").')</span></small>';
				$h .= '</div>';
			$h .= '</a>';

			foreach($cJournalCode as $eJournalCode) {

				if($eJournalCode['isDisplayed'] === FALSE and (int)$selectedJournalCode !== $eJournalCode['id']) {
					continue;
				}

				$attributes = [
					'class' => 'tab-item journal-code-'.$eJournalCode['id'],
					'data-tab' => 'journal-'.$eJournalCode['id'],
					'href' => \company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$eJournalCode['id'].'&'.$args,
				];
				if((int)$selectedJournalCode === $eJournalCode['id']) {
					$attributes['style'] = 'color: white; background-color: ';
					if($eJournalCode['color']) {
						$attributes['style'] .= $eJournalCode['color'];
					} else {
						$attributes['style'] .= 'var(--secondary);';
					}
				}

					$h .= '<a '.attrs($attributes).'>';
						$h .= '<div class="text-center">';
							$h .= encode($eJournalCode['name']);
							$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.encode($eJournalCode['code']).')</span></small>';
						$h .= '</div>';
					$h .= '</a>';

			}

			// Journaux de TVA
			if($eFinancialYear['hasVat']) {

				$journalCode = 'vat-buy';
				$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$journalCode.'&'.$args.'">';
					$h .= '<div class="text-center">';
						$h .= s("TVA");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("Achats").')</span></small>';
					$h .= '</div>';
				$h .= '</a>';

				$journalCode = 'vat-sell';
				$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$journalCode.'&'.$args.'">';
					$h .= '<div class="text-center">';
						$h .= s("TVA");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("Ventes").')</span></small>';
					$h .= '</div>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;
	}

	protected function getFilterUrl(Operation $eOperation, \Search $search, string $field, \farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$_search = clone $search;

		switch($field) {

			case 'thirdParty':
			case 'account':
				$_search->set($field, $eOperation[$field]['id']);
				break;

			case 'accountLabel':
				$_search->set('accountLabel', trim($eOperation['accountLabel'], '0'));
				break;

		}

		$baseUrl = $this->getBaseUrl($eFarm, $eFinancialYear);

		$_search = $_search->getFiltered(['hash']);
		return $baseUrl.'&'.http_build_query($_search);

	}

	public function list(
		\farm\Farm $eFarm,
		?string $selectedJournalCode,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYearSelected,
		\Search $search = new \Search(),
		bool $readonly = FALSE,
		bool $displayTotal = FALSE,
	): string {

		if($readonly === FALSE and $cOperation->empty() === TRUE) {

			$hideSearch = ($search->empty(['ids']) and $search->get('hasDocument') === NULL and $search->get('needsAsset') === NULL) === TRUE;

			if($hideSearch === TRUE) {
				$h = '<div class="util-empty">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
				$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create" class="btn btn-primary">'.s("Enregistrer ma première écriture comptable").'</a>';
				return $h;
			}
			return '<div class="util-empty">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		\Asset::css('util', 'batch.css');
		\Asset::js('util', 'batch.js');

		\Asset::js('journal', 'journal.js');

		$canUpdateFinancialYear = ($readonly === FALSE and $eFinancialYearSelected->canUpdate() and $selectedJournalCode !== JournalSetting::JOURNAL_CODE_BANK);

		$columns = 6;
		if($selectedJournalCode === NULL) {
			$columns++;
		}

		$totalDebit = 0;
		$totalCredit = 0;

		$h = '';

		$h .= '<div class="stick-sm util-overflow-md">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';

						if($canUpdateFinancialYear) {
							$h .= '<th>';
							$h .= '</th>';
						}

						$h .= '<th>'.s("Numéro de compte").'</th>';

						if($selectedJournalCode === NULL and $readonly === FALSE) {
							$h .= '<th class="td-min-content"></th>';
						}

						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';

						$h .= '<th class="text-end highlight-stick-right hide-md-up">'.s("Montant").'</th>';

						$h .= '<th class="text-end highlight-stick-right hide-sm-down">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left hide-sm-down">'.s("Crédit (C)").'</th>';

						if($readonly === FALSE) {
							$h .= '<th></th>';
						}

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$currentDate = NULL;

					foreach($cOperation as $eOperation) {

						$batch = [];

						if($eOperation->acceptNewAsset() or ($eOperation['operation']->notEmpty() and $eOperation['operation']->acceptNewAsset())) {
							$batch[] = 'accept-asset';
						}

						if($eOperation['cOperationCashflow']->empty()) {
							$batch[] = 'accept-attach';
						}

						$referenceDate = $eFinancialYearSelected->isCashAccounting() ? $eOperation['paymentDate'] : $eOperation['date'];

						if($currentDate === NULL or $currentDate !== $referenceDate) {
							$h .= '<tr class="tr-title">';

								if($readonly === FALSE) {
									$h .= '<td></td>';
								}
								$h .= '<td colspan="'.$columns.'">';
									$h .= \util\DateUi::numeric($referenceDate);
								$h .= '</td>';
							$h .= '</tr>';
						}

						$currentDate = $referenceDate;

						$h .= '<tr name="operation-'.$eOperation['id'].'" name-linked="operation-linked-'.($eOperation['operation']['id'] ?? '').'">';

							if($canUpdateFinancialYear) {
								$h .= '<td class="td-checkbox">';
									if($eOperation->acceptUpdate() and $eOperation->canUpdate()) {
										$attributesCheckbox = [
											'data-batch-type' => $eOperation['type'],
											'data-batch-amount' => $eOperation['amount'],
											'data-journal-code' => (string)$selectedJournalCode,
											'data-batch' => implode(' ', $batch),
										];
										if($eOperation['operation']->notEmpty()) {
											$attributesCheckbox['class'] = 'hide';
											$attributesCheckbox['data-operation-parent'] = $eOperation['operation']['id'];
										} else {
											$attributesCheckbox['oninput'] = 'Journal.changeSelection("'.$selectedJournalCode.'")';
										}
										$h .= '<label>';
											$h .= '<input '.attrs($attributesCheckbox).' type="checkbox" name="batch[]" value="'.$eOperation['id'].'" data-operation="'.$eOperation['id'].'"/>';
										$h .= '</label>';
									}
								$h .= '</td>';
							}
							$h .= '<td class="td-vertical-align-top">';
								$h .= '<div class="journal-operation-description" data-dropdown="bottom" data-dropdown-hover="true">';
									if($eOperation['accountLabel'] !== NULL) {
										$text = encode($eOperation['accountLabel']);
										$url = $this->getFilterUrl($eOperation, $search, 'accountLabel', $eFarm, $eFinancialYearSelected);
									} else {
										$text = encode(str_pad($eOperation['account']['class'], 8, 0));
										$url = $this->getFilterUrl($eOperation, $search, 'account', $eFarm, $eFinancialYearSelected);
									}
									if($readonly) {
										$h .= $text;
									} else {
										$h .= '<a href="'.$url.'" title="'.s("Filtrer sur ce compte").'">'.$text.'</a>';
									}
								$h .= '</div>';
								$h .= new \account\AccountUi()->getDropdownTitle($eOperation['account']);
							$h .= '</td>';

							if($selectedJournalCode === NULL and $readonly === FALSE) {

								$h .= '<td class="td-vertical-align-top">';
									$h .= $eOperation['journalCode']->empty()
										? ''
										: new JournalCodeUi()->getColoredButton($eOperation['journalCode'], link: \company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$eOperation['journalCode']['id'], title: s("Filtrer sur le journal : {value}", encode($eOperation['journalCode']['name'])));
								$h .= '</td>';

							}

							$h .= '<td>';
								$class = ($eOperation['type'] === Operation::CREDIT ? ' journal-margin-credit' : '');
								$h .= '<div class="description'.$class.'">';
									if($eOperation['asset']->exists() === TRUE) {
										$attributes = [
											'href' => \company\CompanyUi::urlFarm($eFarm).'/immobilisation/'.$eOperation['asset']['id'].'/',
											'data-dropdown' => 'bottom-end',
											'data-dropdown-hover' => TRUE,
											'data-dropdown-offset-x' => 0,
										];
										$h .= '<a '.attrs($attributes).'>'.\Asset::icon('house-door').'</a>';
										$h .= '&nbsp;';
										$h .= '<div class="operation-asset-dropdown dropdown-list dropdown-list-unstyled">';
											$h .= s("Voir l'immobilisation liée");
										$h .= '</div>';
									}

								$h .= encode($eOperation['description']);
								if($eOperation['cashflow']->notEmpty() and \account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS)) {
									$h .= ' <a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/operations?id='.$eOperation['cashflow']['id'].'&bankAccount='.$eOperation['cashflow']['account']['id'].'" class="btn btn-xs btn-secondary" title="'.("Voir l'opération bancaire liée").'">';
										$h .= \Asset::icon('piggy-bank');
									$h .= '</a>';
								}

							$h .= '</td>';

							$h .= '<td class="td-vertical-align-top">';
								if($eOperation['thirdParty']->exists() === TRUE) {
									$url = $this->getFilterUrl($eOperation, $search, 'thirdParty', $eFarm, $eFinancialYearSelected);
									if($readonly) {
										$h .= encode($eOperation['thirdParty']['name']);
									} else {
										$h .= '<a href="'.$url.'" title="'.s("Filtrer sur ce tiers").'">'.encode($eOperation['thirdParty']['name']).'</a>';
									}
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top hide-md-up">';
								if($eOperation['type'] === Operation::DEBIT) {
									$h .= '<span class="journal-margin-debit">'.\util\TextUi::money($eOperation['amount']).'</span>';
								} else {
									$h .= \util\TextUi::money(-1 * $eOperation['amount']);
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top hide-sm-down">';
								if($eOperation['type'] === Operation::DEBIT) {
									$h .= \util\TextUi::money($eOperation['amount']);
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top hide-sm-down">';
								if($eOperation['type'] === Operation::CREDIT) {
									$h .= \util\TextUi::money($eOperation['amount']);
								}
							$h .= '</td>';

							$args = $_GET;
							foreach(['id', 'operation', 'origin', 'farm', 'app'] as $filteredField) {
								unset($args[$filteredField]);
							}
							if($readonly === FALSE) {
								$h .= '<td class="td-min-content">';
									$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-title">'.new OperationUi()->getTitle($eOperation).'</div>';
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'?'.http_build_query($args).'" class="dropdown-item" data-view-operation="'.$eOperation['id'].'">'.s("Voir l'écriture").'</a>';
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?hash='.$eOperation['hash'].'" class="dropdown-item" data-view-operation="'.$eOperation['id'].'">'.s("Filtrer sur le groupe d'écritures").'</a>';

										if($eOperation->canUpdate() and $eOperation->acceptUpdate()) {

											$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'/update" class="dropdown-item">'.s("Modifier l'écriture").'</a>';

											if(
												\asset\AssetLib::isAsset($eOperation['accountLabel']) and
												$eOperation['asset']->empty()
											) {

												$h .= '<div class="dropdown-divider"></div>';
												$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/asset/:create?ids[]='.$eOperation['id'].'" class="dropdown-item">'.s("Créer l'immobilisation").'</a>';

											}

											// Rattacher à une opération bancaire
											if($eOperation['cOperationCashflow']->empty()) {

												$h .= '<div class="dropdown-divider"></div>';
												$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations:attach?ids[]='.$eOperation['id'].'" class="dropdown-item">'.s("Rattacher à une opération bancaire").'</a>';

											}

											// SUPPRIMER
											$isLinkedToAsset = $eOperation['cOperationHash']->getColumnCollection('asset')->find(fn($e) => $e->notEmpty())->notEmpty();

											// Si immo : d'abord supprimer l'immo
											if($isLinkedToAsset) {

												$deleteText = s("Supprimer <div>(Supprimez d'abord l'<b>immobilisation</b> liée)</div>", ['div' => '<div class="operations-delete-more">']);

												$h .= '<div class="dropdown-divider"></div>';
												$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';

											// Si cashflow : passer par le cashlow
											} else if($eOperation['cOperationCashflow']->notEmpty()) {

												$deleteText = s("Supprimer <div>(Passez par l'opération bancaire pour supprimer cette écriture)</div>", ['div' => '<div class="operations-delete-more">']);

												$h .= '<div class="dropdown-divider"></div>';
												$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';

											// Sinon: ok
											} else {

												$h .= '<div class="dropdown-divider"></div>';
												$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:delete?id='.$eOperation['id'].'" class="dropdown-item">';
													$h .= p(
														"Supprimer l'écriture",
														"Supprimer les {value} écritures",
														$eOperation['cOperationHash']->count()
													);
												$h .= '</a>';
											}

										}

									$h .= '</div>';
								$h .= '</td>';

							}

							if($eOperation['type'] === Operation::DEBIT) {
								$totalDebit += $eOperation['amount'];
							}
							if($eOperation['type'] === Operation::CREDIT) {
								$totalCredit += $eOperation['amount'];
							}

						$h .= '</tr>';

					}

					if($displayTotal) {

						$colspan = ($selectedJournalCode === NULL ? 5 : 4);
						if($readonly) {
							$colspan--;
						}

						$h .= '<tr class="row-highlight tr-bold">';

							if($readonly === FALSE) {
								$h .= '<td class="td-checkbox"></td>';
							}
							$h .= '<td colspan="'.$colspan.'">'.s("Totaux").'</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top">';
								$h .= \util\TextUi::money($totalDebit);
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top">';
								$h .= \util\TextUi::money($totalCredit);
							$h .= '</td>';

							$h .= '<td></td>';

						$h .= '</tr>';

						if($totalCredit >= $totalDebit) {
							$text = s("Solde créditeur");
						} else {
							$text = s("Solde débiteur");
						}

						$h .= '<tr class="row-highlight tr-bold">';

							if($readonly === FALSE) {
								$h .= '<td class="td-checkbox"></td>';
							}
							$h .= '<td colspan="'.$colspan.'">'.$text.'</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top">';
								if($totalDebit > $totalCredit) {
									$h .= \util\TextUi::money($totalDebit - $totalCredit);
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top">';
								if($totalDebit <= $totalCredit) {
									$h .= \util\TextUi::money($totalCredit - $totalDebit);
								}
							$h .= '</td>';

							$h .= '<td></td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cPaymentMethod, \Collection $cJournalCode): string {

		$menu = '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number-debit"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Débit").'</span>';
		$menu .= '</a>';
		$menu .= '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number-credit"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Crédit").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-dropdown="top-start" class="batch-journal-code batch-item">';
			$menu .= \Asset::icon('journal-bookmark');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Journal").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';

			$menu .= '<div class="dropdown-title">'.s("Changer de journal").'</div>';
			foreach($cJournalCode as $eJournalCode) {
				$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateJournalCollection" data-ajax-target="#batch-journal-form" post-journal-code="'.$eJournalCode['id'].'" class="dropdown-item">'.encode($eJournalCode['name']).'</a>';
			}
			$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateJournalCollection" data-ajax-target="#batch-journal-form" post-journal-code="" class="dropdown-item"><i>'.s("Pas de journal").'</i></a>';
		$menu .= '</div>';

		$menu .= '<a data-dropdown="top-start" class="batch-payment-method batch-item">';
			$menu .= \Asset::icon('cash-coin');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Moyen de paiement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';

			$menu .= '<div class="dropdown-title">'.s("Changer de moyen de paiement").'</div>';
			foreach($cPaymentMethod as $ePaymentMethod) {
				if($ePaymentMethod['online'] === FALSE) {
					$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdatePaymentCollection" data-ajax-target="#batch-journal-form" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}
			$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdatePaymentCollection" data-ajax-target="#batch-journal-form" post-payment-method="" class="dropdown-item"><i>'.s("Pas de moyen de paiement").'</i></a>';
		$menu .= '</div>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createCommentCollection" data-ajax-method="get" class="batch-item">'.\Asset::icon('chat-text-fill').'<span>'.s("Commenter").'</span></a>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createDocumentCollection" data-ajax-method="get" class="batch-item">'.\Asset::icon('paperclip').'<span>'.s("Pièce comptable").'</span></a>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlAsset($eFarm).'/:create" data-ajax-method="get" class="batch-item batch-asset" data-batch-not-only="hide" data-batch-test="accept-asset">'.\Asset::icon('house-door').'<span>'.s("Créer la fiche d'immo").'</span></a>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operations:attach" data-ajax-method="get" class="batch-item" data-batch-test="accept-attach" data-batch-not-only="hide">'.\Asset::icon('piggy-bank').'<span>'.s("Rattacher").'</span></a>';

		$menu .= '<span class="hide" data-batch-title-more-singular>'.s(", dont 1 opération liée").'</span>';
		$menu .= '<span class="hide" data-batch-title-more-plural>'.s(", dont <span data-batch-title-more-value></span> opérations liées").'</span>';

		return \util\BatchUi::group('batch-journal', $menu, '', title: s("Pour les opérations sélectionnées"));

	}

	protected function action(Operation $eOperation, string $title, string $property): string {

		if($eOperation['operation']->notEmpty()) {
			$attributes = [
				'class' => 'dropdown-item inactive',
				'onclick' => 'void(0);',
			];

			return '<a '.attrs($attributes).'>'.$title.'<sup>*</sup></a>';
		} else {
			return $eOperation->quick($property, $title, 'dropdown-item');
		}
	}

	protected function displayActions(\farm\Farm $eFarm, Operation $eOperation): string {

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';

		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.new OperationUi()->getTitle($eOperation).'</div>';
			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'" class="dropdown-item">'.s("Voir le détail").'</a>';
		$h .= '</div>';

		return $h;

	}

}

?>
