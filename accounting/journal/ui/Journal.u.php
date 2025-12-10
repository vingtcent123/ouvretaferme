<?php
namespace journal;

class JournalUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
		\Asset::css('company', 'company.css');
	}

	public function getJournalTitle(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Le livre journal");
			$h .= '</h1>';

			if($eFinancialYear->notEmpty()) {

				$h .= '<div class="journal-title-buttons">';

					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#journal-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';

					if(
						get_exists('cashflow') === FALSE
						and $eFinancialYear['status'] === \account\FinancialYearElement::OPEN
						and $eFarm->canManage()
					) {
						if($eFinancialYear->isCashAccounting()) {
							$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create?financialYear='.$eFinancialYear['id'].'&journalCode='.GET('journalCode').'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter une écriture").'</a> ';
						}
						if($eFinancialYear->isAccrualAccounting() or $eFinancialYear->isCashAccrualAccounting()) {

							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter...").'</a>';
							$h .= '<div class="dropdown-list">';

								$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create?financialYear='.$eFinancialYear['id'].'" class="dropdown-item">';
								$h .= s("une écriture");
								$h .= '</a>';

								$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createPayment?financialYear='.$eFinancialYear['id'].'" class="dropdown-item">';
								$h .= s("un paiement");
								$h .= '</a>';

							$h .= '</div>';
						}
					}

					//$h .= '<a href="'.PdfUi::urlJournal($eFarm).'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('file-pdf').'&nbsp;'.s("Télécharger en PDF").'</a>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getBaseUrl(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear = new \account\FinancialYear()): string {

		return \company\CompanyUi::urlJournal($eFarm).'/livre-journal'.'?financialYear='.($eFinancialYear['id'] ?? '');

	}
	public function getSearch(\farm\Farm $eFarm, \Search $search, \account\FinancialYear $eFinancialYearSelected, \bank\Cashflow $eCashflow, ?\account\ThirdParty $eThirdParty, \Collection $cPaymentMethod, \Collection $cJournalCode): string {

		\Asset::js('journal', 'operation.js');

		$h = '<div id="journal-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = $this->getBaseUrl($eFarm, $eFinancialYearSelected);

			$statuses = OperationUi::p('type')->values;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->inputGroup($form->addon(s("entre")).
						$form->month('periodStart', $search->get('periodStart'), ['min' => $eFinancialYearSelected['startDate'], 'max' => $eFinancialYearSelected['endDate'], 'placeholder' => s("Début")]).
						$form->addon("et").
						$form->month('periodEnd', $search->get('periodEnd'), ['min' => $eFinancialYearSelected['startDate'], 'max' => $eFinancialYearSelected['endDate'], 'placeholder' => s("Fin")]),
						['class' => 'company-period-input-group']
					);
					$h .= $form->text('accountLabel', $search->get('accountLabel'), ['placeholder' => s("Classe de compte")]);
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Description")]);
					$h .= $form->select('type', $statuses, $search->get('type'), ['placeholder' => s("Type")]);
					$h .= $form->select('paymentMethod', $cPaymentMethod, $search->get('paymentMethod'), ['placeholder' => s("Moyen de paiement")]);
					$h .= $form->dynamicField(new Operation(['thirdParty' => $eThirdParty]), 'thirdParty', function($d) use($form) {
						$d->autocompleteDispatch = '[data-third-party="form-search"]';
						$d->attributes['data-index'] = 0;
						$d->attributes['data-third-party'] = 'form-search';
					});
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Pièce comptable")]);
					$h .= $form->select('cashflowFilter', [
						0 => s("Toutes les écritures"),
						1 => s("Écritures non rattachées à une opération bancaire"),
					], $search->get('cashflowFilter', 'int', 0), ['mandatory' => TRUE]);
					$journalCode = [];
					foreach($cJournalCode as $eJournalCode) {
						$journalCode[$eJournalCode['id']] = $eJournalCode['name'];
					}
					$journalCode[-1] = s("Sans journal");
					$h .= $form->select('journalCode', $journalCode, $search->get('journalCode'), ['placeholder' => s("Journal")]);
					$h .= $form->select('hasDocument', [
						0 => s("Avec ou sans pièce comptable"),
						1 => s("Sans pièce comptable"),
					], $search->get('hasDocument', 'int', 0), ['mandatory' => TRUE]);

			$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($eCashflow->exists() === TRUE) {
			$h .= '<div class="util-info">';
				$h .= s(
					"Vous visualisez actuellement les écritures correspondant à l'opération bancaire du {date}, \"{memo}\" d'un {type} de {amount} (<link>annuler le filtre</link>).",
					[
						'date' => \util\DateUi::numeric($eCashflow['date']),
						'memo' => encode($eCashflow['memo']),
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

			$h .= '<a class="tab-item '.($selectedJournalCode === NULL ? ' selected' : '').'" data-tab="journal" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?'.$args.'">'.s("Général").'</a>';

			$h .= '<a class="tab-item'.((int)$selectedJournalCode === \journal\JournalSetting::JOURNAL_CODE_BANK ? ' selected' : '').'" data-tab="journal-'.\journal\JournalSetting::JOURNAL_CODE_BANK.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.\journal\JournalSetting::JOURNAL_CODE_BANK.'&'.$args.'">';
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
					'class' => 'tab-item journal-code-'.$eJournalCode['id'], 'data-tab' => 'journal-'.$eJournalCode['id'],
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

		return $baseUrl.'&'.$_search->toQuery();

	}

	public function getTableContainer(
		\farm\Farm $eFarm,
		?string $journalCode,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYearSelected,
		\Search $search = new \Search(),
		array $hide = [],
	): string {

		if($cOperation->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-info">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		\Asset::css('util', 'batch.css');
		\Asset::js('util', 'batch.js');

		\Asset::js('journal', 'journal.js');

		$canUpdateFinancialYear = ($eFinancialYearSelected->canUpdate() and $journalCode !== JournalSetting::JOURNAL_CODE_BANK);

		// On affiche les données de lettrage si on a filtré sur un tiers + sa classe
		$showLettering = (($eFinancialYearSelected->isAccrualAccounting() or $eFinancialYearSelected->isCashAccrualAccounting()) and $search->get('thirdParty') and $search->get('accountLabel'));

		$columns = 6;
		if($journalCode === NULL) {
			$columns++;
		}
		if($showLettering) {
			$columns++;
		}

		$totalDebit = 0;
		$totalCredit = 0;

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';

						if($canUpdateFinancialYear) {
							$h .= '<th>';
							$h .= '</th>';
						}

						$h .= '<th>'.s("Compte").'</th>';

						if($journalCode === NULL) {
							$h .= '<th></th>';
						}

						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';

						if($showLettering) {
							$h .= '<th>'.s("Lettrage").'</th>';
						}

						$h .= '<th class="text-end highlight-stick-right">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit (C)").'</th>';
						$h .= '<th></th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$currentDate = NULL;

					foreach($cOperation as $eOperation) {

						$referenceDate = $eFinancialYearSelected->isCashAccounting() ? $eOperation['paymentDate'] : $eOperation['date'];

						if($currentDate === NULL or $currentDate !== $referenceDate) {
							$h .= '<tr class="tr-title">';
								$h .= '<td></td>';
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
											'data-journal-code' => (string)$journalCode,
										];
										if($eOperation['operation']->notEmpty()) {
											$attributesCheckbox['class'] = 'hide';
											$attributesCheckbox['data-operation-parent'] = $eOperation['operation']['id'];
										} else {
											$attributesCheckbox['oninput'] = 'Journal.changeSelection("'.$journalCode.'")';
										}
										$h .= '<label>';
											$h .= '<input '.attrs($attributesCheckbox).' type="checkbox" name="batch[]" value="'.$eOperation['id'].'" data-operation="'.$eOperation['id'].'" data-operation-linked="'.$eOperation['cOperationLinked']->count().'"/>';
										$h .= '</label>';
									}
								$h .= '</td>';
							}
							$h .= '<td>';
								$h .= '<div class="journal-operation-description" data-dropdown="bottom" data-dropdown-hover="true">';
									if($eOperation['accountLabel'] !== NULL) {
										$text = encode($eOperation['accountLabel']);
										$url = $this->getFilterUrl($eOperation, $search, 'accountLabel', $eFarm, $eFinancialYearSelected);
									} else {
										$text = encode(str_pad($eOperation['account']['class'], 8, 0));
										$url = $this->getFilterUrl($eOperation, $search, 'account', $eFarm, $eFinancialYearSelected);
									}
									$h .= '<a href="'.$url.'" title="'.s("Filtrer sur ce compte").'">'.$text.'</a>';
								$h .= '</div>';
								$h .= new \account\AccountUi()->getDropdownTitle($eOperation['account']);
							$h .= '</td>';

							if($journalCode === NULL) {

								$h .= '<td>';
									$h .= $eOperation['journalCode']->empty()
										? ''
										: new JournalCodeUi()->getColoredButton($eOperation['journalCode'], link: \company\CompanyUi::urlJournal($eFarm).'/livre-journal?journalCode='.$eOperation['journalCode']['id'], title: s("Filtrer sur le journal : {value}", encode($eOperation['journalCode']['name'])));
								$h .= '</td>';

							}

							$h .= '<td>';
								$class = ($eOperation['type'] === Operation::CREDIT ? ' ml-3' : '');
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

							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['thirdParty']->exists() === TRUE) {
									$url = $this->getFilterUrl($eOperation, $search, 'thirdParty', $eFarm, $eFinancialYearSelected);
									$h .= '<a href="'.$url.'" title="'.s("Filtrer sur ce tiers").'">'.encode($eOperation['thirdParty']['name']).'</a>';
								}
							$h .= '</td>';

							if($showLettering) {

								$c = new \Collection();
								$c->mergeCollection($eOperation['cLetteringDebit']);
								$c->mergeCollection($eOperation['cLetteringCredit']);

								[$icon, $class, $title] = match($eOperation['letteringStatus']) {
									NULL => [\Asset::icon('x-lg'), 'color-danger', s("Non lettrée")],
									Operation::PARTIAL => [\Asset::icon('exclamation-triangle'), 'color-warning', s("Lettrage partiel")],
									Operation::TOTAL => [\Asset::icon('check'), 'color-success', s("Lettrée")],
								};

								$h .= '<td title="'.$title.'">';
									$h .= '<div class="journal-operation-letters">';
										$h .= '<div class="'.$class.'">'.$icon.'</div>';
										$h .= '<div>'.join('<br />', $c->getColumn('code')).'</div>';
									$h .= '</div>';
								$h .= '</td>';
							}

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top">';
								if($eOperation['type'] === Operation::DEBIT) {
									$h .= \util\TextUi::money($eOperation['amount']);
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top">';
								if($eOperation['type'] === Operation::CREDIT) {
									$h .= \util\TextUi::money($eOperation['amount']);
								}
							$h .= '</td>';

							$args = $_GET;
							foreach(['id', 'operation', 'origin', 'farm', 'app'] as $filteredField) {
								unset($args[$filteredField]);
							}
							$h .= '<td class="td-min-content">';
								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.new OperationUi()->getTitle($eOperation).'</div>';
									$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'?'.http_build_query($args).'" class="dropdown-item" data-view-operation="'.$eOperation['id'].'">'.s("Voir l'écriture").'</a>';

									if($eOperation->canUpdate() and $eOperation->acceptUpdate()) {

										$more = null;

										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'/update" class="dropdown-item">'.s("Modifier l'écriture").'</a>';

										// ACTION "SUPPRIMER"
										if($eOperation['cOperationCashflow']->empty()) { // Cette opération est liée à une autre : on ne peut pas la supprimer.

											if($eOperation['operation']->notEmpty()) {

												$attributes = [
													'class' => 'dropdown-item inactive',
													'onclick' => 'void(0);',
													'data-highlight' => 'operation-'.$eOperation['operation']['id'],
												];

												$more = s("Cette écriture est liée à une autre écriture. Supprimer l'autre écriture supprimera celle-ci.");

												$deleteText = s("Supprimer <div>({more})</div>", ['div' => '<div class="operations-delete-more">', 'more' => $more]);

												$buttonDelete = '<a '.attrs($attributes).'>'.$deleteText.'</a>';

											} else if($eOperation['hash'] !== NULL and $eOperation->isFromImport()) {

												$confirmText = match($eOperation->importType()) {
													JournalSetting::HASH_LETTER_IMPORT_INVOICE => s("Cette opération est liée à d'autres opérations et à une facture. Confirmez-vous la suppression de ces écritures et du lien avec la facture ?"),
													JournalSetting::HASH_LETTER_IMPORT_SALE => s("Cette opération est liée à d'autres opérations et à une vente. Confirmez-vous la suppression de ces écritures et du lien avec la vente ?"),
													JournalSetting::HASH_LETTER_IMPORT_MARKET => s("Cette opération est liée à d'autres opérations et à un marché. Confirmez-vous la suppression de ces écritures et du lien avec le marché ?"),
												};
												$attributes = [
													'data-ajax' => \company\CompanyUi::urlJournal($eFarm).'/operation:doDelete',
													'post-id' => $eOperation['id'],
													'data-confirm' => $confirmText,
													'class' => 'dropdown-item',
												];

												$more = match($eOperation->importType()) {
													JournalSetting::HASH_LETTER_IMPORT_INVOICE => s("Cette opération est liée à d'autres opérations et à une facture"),
													JournalSetting::HASH_LETTER_IMPORT_SALE => s("Cette opération est liée à d'autres opérations et à une vente"),
													JournalSetting::HASH_LETTER_IMPORT_MARKET => s("Cette opération est liée à d'autres opérations et à un marché"),
												};
												$deleteText = s("Supprimer <div>({more})</div>", ['div' => '<div class="operations-delete-more">', 'more' => $more]);

												$buttonDelete = '<a '.attrs($attributes).'>'.$deleteText.'</a>';

											} else {

												$attributes = [
													'data-ajax' => \company\CompanyUi::urlJournal($eFarm).'/operation:doDelete',
													'post-id' => $eOperation['id'],
													'data-confirm' => s("Confirmez-vous la suppression de cette écriture ?"),
													'class' => 'dropdown-item',
												];

												if($eOperation['vatAccount']->notEmpty()) {

													$attributes += [
														'data-highlight' => $eOperation['vatAccount']->exists()
															? 'operation-linked-'.$eOperation['id']
															: 'operation-'.$eOperation['operation']['id'],
														'data-confirm' => s("Confirmez-vous la suppression de cette écriture ?"),
													];

												}

												if($eOperation['asset']->exists() === TRUE) {

													if($eOperation['vatAccount']->exists() === TRUE) {

														$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture, de l'entrée de TVA liée, ainsi que de l'entrée dans les immobilisations ?");

														$more = s("En supprimant cette écriture, l'écriture de TVA associée et l'entrée dans les immobilisations seront également supprimées.");

													} else {

														$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture ainsi que de l'entrée dans les immobilisations ?");

														$more = s("En supprimant cette écriture, l'entrée dans les immobilisations sera également supprimée.");

													}

												} elseif($eOperation['vatAccount']->exists() === TRUE) {

													$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture ainsi que de l'écriture de TVA associée ?");

													$more = s("En supprimant cette écriture, l'écriture de TVA associée sera également supprimée.");

												}

												if(isset($more)) {

													$deleteText = s("Supprimer <div>({more})</div>", ['div' => '<div class="operations-delete-more">', 'more' => $more]);

												} else {

													$deleteText = s("Supprimer");

												}

												$buttonDelete = '<a '.attrs($attributes).'>'.$deleteText.'</a>';
											}

											$h .= '<div class="dropdown-divider"></div>';
											$h .= $buttonDelete;

										} else {

											$deleteText = s("Supprimer <div>(Passez par l'opération bancaire pour supprimer cette écriture)</div>", ['div' => '<div class="operations-delete-more">']);

											$h .= '<div class="dropdown-divider"></div>';
											$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';

										}

									}

								$h .= '</div>';
							$h .= '</td>';

							if($eOperation['type'] === Operation::DEBIT) {
								$totalDebit += $eOperation['amount'];
							}
							if($eOperation['type'] === Operation::CREDIT) {
								$totalCredit += $eOperation['amount'];
							}

						$h .= '</tr>';

					}

					if($showLettering) {

						$colspan = ($journalCode === NULL ? 5 : 4);

						$h .= '<tr class="row-highlight row-bold">';

							$h .= '<td class="td-checkbox"></td>';
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

						$h .= '<tr class="row-highlight row-bold">';

							$h .= '<td class="td-checkbox"></td>';
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

		$menu = '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number-debit"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Débit").'</span>';
		$menu .= '</a>';
		$menu .= '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number-credit"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Crédit").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-dropdown="top-start" class="batch-menu-journal-code batch-menu-item">';
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

		$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
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

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createCommentCollection" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('chat-text-fill').'<span>'.s("Commenter").'</span></a>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createDocumentCollection" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('paperclip').'<span>'.s("Pièce comptable").'</span></a>';

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
