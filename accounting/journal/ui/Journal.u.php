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
				if($eFarm['company']->isCashAccounting()) {
					$h .= s("Le journal comptable");
				} else {
					$h .= s("Les journaux");
				}

			$h .= '</h1>';

			if($eFinancialYear->notEmpty()) {

				$h .= '<div class="journal-title-buttons">';

					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#journal-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';

					if(
						get_exists('cashflow') === FALSE
						and $eFinancialYear['status'] === \account\FinancialYearElement::OPEN
						and $eFarm->canManage()
					) {
						if($eFarm['company']->isCashAccounting()) {
							$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter une écriture").'</a> ';
						}
						if($eFarm['company']->isAccrualAccounting()) {

							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter...").'</a>';
							$h .= '<div class="dropdown-list">';

								$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create" class="dropdown-item">';
								$h .= s("une écriture");
								$h .= '</a>';

								$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createPayment" class="dropdown-item">';
								$h .= s("un paiement");
								$h .= '</a>';

							$h .= '</div>';
						}
					}

					$h .= '<a href="'.PdfUi::urlJournal($eFarm).'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('download').'&nbsp;'.s("Télécharger en PDF").'</a>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getBaseUrl(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear = new \account\FinancialYear()): string {

		return \company\CompanyUi::urlJournal($eFarm).'/operations'.'?financialYear='.($eFinancialYear['id'] ?? '').'&code='.GET('code');

	}
	public function getSearch(\farm\Farm $eFarm, \Search $search, \account\FinancialYear $eFinancialYearSelected, \bank\Cashflow $eCashflow, ?\account\ThirdParty $eThirdParty): string {

		\Asset::js('journal', 'operation.js');

		$h = '<div id="journal-search" class="util-block-search stick-xs '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = $this->getBaseUrl($eFarm, $eFinancialYearSelected);

			$statuses = OperationUi::p('type')->values;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->month('date', $search->get('date'), ['placeholder' => s("Mois")]);
					$h .= $form->text('accountLabel', $search->get('accountLabel'), ['placeholder' => s("Classe de compte")]);
					$h .= $form->text('description', $search->get('description'), ['placeholder' => s("Description")]);
					$h .= $form->select('type', $statuses, $search->get('type'), ['placeholder' => s("Type")]);
					$h .= $form->dynamicField(new Operation(['thirdParty' => $eThirdParty]), 'thirdParty', function($d) use($form) {
						$d->autocompleteDispatch = '[data-third-party="form-search"]';
						$d->attributes['data-index'] = 0;
						$d->attributes['data-third-party'] = 'form-search';
					});
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Pièce comptable")]);
					$h .= $form->checkbox('cashflowFilter', 1, ['checked' => $search->get('cashflowFilter'), 'callbackLabel' => fn($input) => $input.' '.s("Filtrer les écritures non rattachées")]);
				$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		if($eCashflow->exists() === TRUE) {
			$h .= '<div class="util-block-search stick-xs">';
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

	public function getJournal(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYearSelected,
		\Search $search = new \Search()
	): string {

		$selectedJournalCode = GET('code');
		if(in_array($selectedJournalCode, Operation::model()->getPropertyEnum('journalCode')) === FALSE) {
			$selectedJournalCode = NULL;
		}

		$h = '<div class="tabs-h" id="journals">';

			$h .= '<div class="tabs-item">';

				$h .= '<a class="tab-item'.($selectedJournalCode === NULL ? ' selected' : '').'" data-tab="journal" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations">'.s("Général").'</a>';

				foreach(Operation::model()->getPropertyEnum('journalCode') as $journalCode) {

					$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'">'.OperationUi::p('journalCode')->values[$journalCode].'</a>';

				}

			$h .= '</div>';

			$h .= '<div class="tab-panel'.($selectedJournalCode === NULL ? ' selected' : '').'" data-tab="journal">';
				$h .= $this->getTableContainer($eFarm, $cOperation, $eFinancialYearSelected, $search, $selectedJournalCode);
			$h .= '</div>';

			foreach(Operation::model()->getPropertyEnum('journalCode') as $journalCode) {
				$h .= '<div class="tab-panel'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'">';
					$h .= $this->getTableContainer($eFarm, $cOperation, $eFinancialYearSelected, $search, $selectedJournalCode);
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getTableContainer(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYearSelected,
		\Search $search = new \Search(),
		?string $selectedJournalCode = NULL,
		array $hide = [],
		array $show = [],
	): string {

		if($cOperation->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-info">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		$thRowspan = 2;
		$descriptionColspan = 4;
		$baseUrl = $this->getBaseUrl($eFarm, $eFinancialYearSelected);

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="td-vertical-top no-background tbody-hover table-operations">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th>';

							if($eFarm['company']->isCashAccounting()) {
								$label = s("Date de l'écriture");
								$h .= ($search ? $search->linkSort('paymentDate', $label) : $label);
							} else {
								$label = s("Date de transaction");
								$h .= ($search ? $search->linkSort('date', $label) : $label);
							}
						$h .= '</th>';

						if(in_array('cashflow', $hide) === FALSE) {
							$h .= '<th><span title="'.s("Numéro d'opération bancaire").'">'.s("Op.").'</span></th>';
						}

						if($selectedJournalCode === NULL) {
							$h .= '<th class="td-min-content"><span title="'.s("Journal").'">'.s("J.").'</span></th>';
						}

						$h .= '<th colspan="2">'.s("Compte (Libellé et classe)").'</th>';
						$h .= '<th class="rowspaned-center" rowspan="'.$thRowspan.'"><span title="'.s("Mode de paiement").'">'.s("Mode").'</span></th>';
						$h .= '<th class="rowspaned-center" rowspan="'.$thRowspan.'">'.s("Tiers").'</th>';
						$h .= '<th class="text-end highlight-stick-right rowspaned-center" rowspan="'.$thRowspan.'">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left rowspaned-center" rowspan="'.$thRowspan.'">'.s("Crédit (C)").'</th>';

						if(in_array('actions', $hide) === FALSE) {
							$h .= '<th class="td-min-content" rowspan="'.$thRowspan.'" class="rowspaned-center"></th>';
						}

						if(in_array('vatAdjustement', $show)) {
							$h .= '<th class="text-end" rowspan="'.$thRowspan.'" class="rowspaned-center">'.s("Régularisation").'</th>';
						}

					$h .= '</tr>';

					$h .= '<tr>';

						if(in_array('document', $hide) === FALSE) {

							$h .= '<th colspan="'.($selectedJournalCode === NULL ? 3 : 2).'">';
								$label = s("Pièce comptable");
								$h .= ($search ? $search->linkSort('document', $label) : $label);
							$h .= '</th>';

						} else {

							$h .= '<th></th>';
							$h .= '<th></th>';
							if($selectedJournalCode === NULL) {
								$h .= '<th></th>';
							}

						}
						$h .= '<th colspan="'.($descriptionColspan - 2).'">';
							$h .= s("Description");
						$h .= '</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				foreach($cOperation as $eOperation) {

					$canUpdate = (
						$eFinancialYearSelected['status'] === \account\FinancialYear::OPEN
						and $eOperation['date'] <= $eFinancialYearSelected['endDate']
						and $eOperation['date'] >= $eFinancialYearSelected['startDate']
						and $eFarm->canManage()
						and $eOperation->canUpdate()
						and in_array('actions', $hide) === FALSE
					);

					$eOperation->setQuickAttribute('farm', $eFarm['id']);
					$eOperation->setQuickAttribute('app', 'accounting');

					$h .= '<tbody>';
						$h .= '<tr name="operation-'.$eOperation['id'].'" name-linked="operation-linked-'.($eOperation['operation']['id'] ?? '').'" class="tr-border-top">';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eOperation['date']);
							$h .= '</td>';

							if(in_array('cashflow', $hide) === FALSE) {
								$h .= '<td>';
									if($eOperation['cashflow']->exists() === TRUE) {
										$searchCashflow = clone $search;
										$searchCashflow->set('cashflow', $eOperation['cashflow']['id']);
										$cashflowLink = $baseUrl.'&'.$searchCashflow->toQuery();
										$h .= '<a href="'.$cashflowLink.'" title="'.s("Filtrer sur cette opération bancaire").'">'.$eOperation['cashflow']['id'].'</a>';
									} else {
										$h .= '';
									}
								$h .= '</td>';

							}

							if($selectedJournalCode === NULL) {
								$h .= '<td class="td-min-content">';

									if($eOperation['journalCode']) {
										$journalLink = $baseUrl.'&'.$search->toQuery().'&code='.encode($eOperation['journalCode']);
										$h .= '<a href="'.$journalLink.'">'.mb_strtoupper($eOperation['journalCode']).'</a>';
									} else {
										$h .= '-';
									}

								$h .= '</td>';
							}

							$h .= '<td>';
								if($eOperation['accountLabel'] !== NULL) {
									$h .= encode($eOperation['accountLabel']);
								} else {
									$h .= encode(str_pad($eOperation['account']['class'], 8, 0));
								}
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['asset']->exists() === TRUE) {
									$attributes = [
										'href' => \company\CompanyUi::urlAsset($eFarm).'/depreciation?id='.$eOperation['asset']['id'],
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
								$h .= encode($eOperation['account']['description']);
							$h .= '</td>';

							$h .= '<td>';
								if($canUpdate === TRUE) {
									$h .= $eOperation->quick('paymentMethod', \payment\MethodUi::getShort($eOperation['paymentMethod']) ?? '<i>'.s("?").'</i>');
								} else {
									$h .= \payment\MethodUi::getShort($eOperation['paymentMethod']);
								}
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['thirdParty']->exists() === TRUE) {
									$searchThirdParty = clone $search;
									$searchThirdParty->set('thirdParty', $eOperation['thirdParty']['id']);
									$thirdPartyLink = $baseUrl.'&'.$searchThirdParty->toQuery();
									$h .= '<a href="'.$thirdPartyLink.'" title="'.s("Filtrer sur ce tiers").'">'.encode($eOperation['thirdParty']['name']).'</a>';
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top" rowspan="2">';
								$debitDisplay = match($eOperation['type']) {
									Operation::DEBIT => \util\TextUi::money($eOperation['amount']),
									default => '',
								};
								if($canUpdate === TRUE) {
									$h .= $eOperation->quick('amount', $debitDisplay);
								} else {
									$h .= $debitDisplay;
								}
							$h .= '</td>';

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top" rowspan="2">';
								$creditDisplay = match($eOperation['type']) {
									Operation::CREDIT => \util\TextUi::money($eOperation['amount']),
									default => '',
								};
								if($canUpdate === TRUE) {
									$h .= $eOperation->quick('amount', $creditDisplay);
								} else {
									$h .= $creditDisplay;
								}
							$h .= '</td>';

							if(in_array('actions', $hide) === FALSE) {

								$h .= '<td rowspan="2" class="td-vertical-align-top">';
									$h .= '<div class="util-unit td-min-content text-end">';
										$h .= $this->displayActions($eFarm, $eOperation, $canUpdate);
									$h .= '</div>';
								$h .= '</td>';

							}

							if(in_array('vatAdjustement', $show)) {

								$h .= '<td>';
									$h .= '<div class="text-center">';
										$h .= $eOperation->isVatAdjustement($show['period']) ? s("oui") : '';
									$h .= '</div>';
								$h .= '</td>';

							}

						$h .= '</tr>';

						$h .= '<tr class="td-padding-xs">';

							if(in_array('document', $hide) === FALSE) {
								$h .= '<td colspan="'.($selectedJournalCode === NULL ? 3 : 2).'">';
									$h .= '<div class="operation-info">';
										if($canUpdate === TRUE) {
											$h .= $eOperation->quick('document', $eOperation['document'] ? encode($eOperation['document']) : '<i>'.s("Non défini").'</i>');
										} else {
											$h .= encode($eOperation['document']);
										}
									$h .= '</div>';
								$h .= '</td>';
							}

							$h .= '<td colspan="'.$descriptionColspan.'" class="td-description">';

								$h .= '<div class="description">';
									if($canUpdate === TRUE) {
										$h .= $eOperation->quick('description', encode($eOperation['description']));
									} else {
										$h .= encode($eOperation['description']);
									}

								$h .= '</div>';

								if($eOperation['comment'] !== NULL) {
									$h .= '<div><i>'.s("Commentaire : {comment}", ['comment' => $eOperation->quick('comment', encode($eOperation['comment']))]).'</i></div>';
								}

							$h .= '</td>';
							if(in_array('vatAdjustement', $show)) {
								$h .= '<td></td>';
							}

						$h .= '</tr>';

					$h .= '</tbody>';
				}

			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	protected function displayActions(\farm\Farm $eFarm, Operation $eOperation, bool $canUpdate): string {

		if($canUpdate === FALSE or $eFarm->canManage() === FALSE) {

			if($eOperation['comment'] === NULL) {
				return '';
			}

		}

		$canAddShipping = ($eOperation->isClassAccount(\Setting::get('account\chargeAccountClass')) === TRUE
			// On ne rajoute pas des frais de port sur des frais de port
			and \account\ClassLib::isFromClass($eOperation['accountLabel'], \Setting::get('account\shippingChargeAccountClass')) === FALSE);

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.s("Modifier une écriture comptable").'</div>';

			// COMMENTAIRE
			if($eOperation['comment'] === NULL) {
				$title = s("Ajouter un commentaire");
				$h .= $eOperation->quick('comment', $title, 'dropdown-item');
			}

			// FRAIS DE LIVRAISON
			if($canAddShipping) {

				$args = [
					'accountPrefix' => \Setting::get('account\shippingChargeAccountClass'),
					'accountLabel' => encode(OperationUi::getAccountLabelFromAccountPrefix(\Setting::get('account\shippingChargeAccountClass'))),
					'document' => $eOperation['document'],
					'type' => $eOperation['type'],
					'date' => $eOperation['date'],
					'thirdParty' => $eOperation['thirdParty']['id'] ?? NULL,
					'description' => $eOperation['description'],
					'cashflow' => $eOperation['cashflow']['id'] ?? NULL,
				];

				$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create?'.http_build_query($args).'" class="dropdown-item">';
					$h .= s("Ajouter des frais de livraison");
				$h .= '</a>';

			}

			if($eOperation['comment'] === NULL or $canAddShipping) {
				$h .= '<div class="dropdown-divider"></div>';
			}

			// SUPPRIMER
			if($eOperation['cashflow']->exists() === FALSE) {

				// Cette opération est liée à une autre : on ne peut pas la supprimer.
				if($eOperation['operation']->exists() === TRUE) {

					$attributes = [
						'class' => 'dropdown-item inactive',
						'onclick' => 'void(0);',
					];
					$buttonDelete = '<a '.attrs($attributes).'>'.s("Supprimer").'</a>';


				} else {

					$attributes = [
						'data-ajax' => \company\CompanyUi::urlJournal($eFarm).'/operation:doDelete',
						'post-id' => $eOperation['id'],
						'data-confirm' => s("Confirmez-vous la suppression de cette écriture ?"),
						'class' => 'dropdown-item',
					];

					if($eOperation['vatAccount']->exists() === TRUE) {
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

					} else if($eOperation['vatAccount']->exists() === TRUE) {

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

				$h .= $buttonDelete;

			} else {

				$deleteText = s("Supprimer <div>(Passez par l'opération bancaire pour supprimer cette écriture)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';
			}

		$h .= '</div>';

		return $h;

	}

}

?>
