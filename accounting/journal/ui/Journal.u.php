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
				if($eFinancialYear->isCashAccounting()) {
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
						if($eFinancialYear->isCashAccounting()) {
							$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter une écriture").'</a> ';
						}
						if($eFinancialYear->isAccrualAccounting()) {

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
	public function getSearch(\farm\Farm $eFarm, \Search $search, \account\FinancialYear $eFinancialYearSelected, \bank\Cashflow $eCashflow, ?\account\ThirdParty $eThirdParty, \Collection $cPaymentMethod): string {

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
					$h .= $form->select('paymentMethod', $cPaymentMethod, $search->get('paymentMethod'), ['placeholder' => s("Moyen de paiement")]);
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

					$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'">';
						$h .= '<div class="text-center">';
							$h .= OperationUi::p('journalCode')->values[$journalCode];
							$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.OperationUi::p('journalCode')->shortValues[$journalCode].')</span></small>';
						$h .= '</div>';
					$h .= '</a>';

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

		$columns = 5;
		$baseUrl = $this->getBaseUrl($eFarm, $eFinancialYearSelected);

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th>'.s("Compte").'</th>';
						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit (C)").'</th>';

						if(in_array('actions', $hide) === FALSE) {
							$columns++;
							$h .= '<th class="td-min-content"></th>';
						}

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';
					$currentDate = NULL;
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

						$referenceDate = $eFinancialYearSelected->isCashAccounting() ? $eOperation['paymentDate'] : $eOperation['date'];

						if($currentDate === NULL or $currentDate !== $referenceDate) {
							$h .= '<tr class="tr-title">';
								$h .= '<td colspan="'.$columns.'">';
									$h .= \util\DateUi::numeric($referenceDate);
								$h .= '</td>';
							$h .= '</tr>';
						}

						$currentDate = $referenceDate;

						$h .= '<tr name="operation-'.$eOperation['id'].'" name-linked="operation-linked-'.($eOperation['operation']['id'] ?? '').'">';

							$h .= '<td>';
								$h .= '<div class="journal-operation-description">';
									$h .= '<span title="'.encode($eOperation['account']['description']).'">';
										if($eOperation['accountLabel'] !== NULL) {
											$h .= encode($eOperation['accountLabel']);
										} else {
											$h .= encode(str_pad($eOperation['account']['class'], 8, 0));
										}
									$h .= '</span>';
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= '<div class="description">';
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

								if($canUpdate === TRUE) {
									$h .= $eOperation->quick('description', encode($eOperation['description']));
								} else {
									$h .= encode($eOperation['description']);
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

							$h .= '<td class="text-end highlight-stick-right td-vertical-align-top">';
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

							$h .= '<td class="text-end highlight-stick-left td-vertical-align-top">';
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

								$h .= '<td class="td-vertical-align-top">';
									$h .= '<div class="journal-operation-actions">';
										$h .= '<div class="journal-operation-action-icon">';
											if($eOperation['cOperationCashflow']->notEmpty()) {
												$h .= '<span title="'.s("Écriture rattachée à une opération bancaire").'">'.\Asset::icon('piggy-bank').'</span>';
											} else {
												$h .= '<span class="color-muted opacity-50" title="'.s("Pas d'opération bancaire liée").'">'.\Asset::icon('piggy-bank').'</a>';
											}
										$h .= '</div>';
										$h .= '<div class="journal-operation-action-icon">';
											if($eOperation['document'] !== NULL) {
												$h .= '<span title="'.s("Pièce comptable : {document}", ['document' => encode($eOperation['document'])]).'">'.$eOperation->quick('document', \Asset::icon('paperclip')).'</a>';
											} else {
												$h .= '<span data-is-updatable class="color-muted opacity-50" title="'.s("Indiquer la pièce comptable").'">'.$eOperation->quick('document', \Asset::icon('paperclip')).'</a>';
											}
										$h .= '</div>';
										$h .= '<div class="journal-operation-action-icon">';
											if($eOperation['comment'] !== NULL) {
												$h .= '<span title="'.encode($eOperation['comment']).'">'.$eOperation->quick('comment', \Asset::icon('chat-text-fill')).'</a>';
											} else {
												$h .= '<span data-is-updatable class="color-muted opacity-50" title="'.s("Ajouter un commentaire").'">'.$eOperation->quick('comment', \Asset::icon('chat-text-fill')).'</a>';
											}
										$h .= '</div>';
										$h .= $this->displayActions($eFarm, $eOperation, $canUpdate);
									$h .= '</div>';
								$h .= '</td>';

							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';

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

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.s("Modifier une écriture comptable").'</div>';

			// JOURNAL
			if($eOperation['journalCode'] === NULL) {
				$title = s("Indiquer le journal");
	 		} else {
				$title = s("Modifier le journal ({value})", OperationUi::p('journalCode')->shortValues[$eOperation['journalCode']]);
			}
			$h .= $eOperation->quick('journalCode', $title, 'dropdown-item');

			// MOYEN DE PAIEMENT
			if($eOperation['paymentMethod'] === NULL) {
				$title = s("Indiquer le moyen de paiement");
	 		} else {
				$title = s("Modifier le moyen de paiement ({value})", \payment\MethodUi::getName($eOperation['paymentMethod']));
			}
			$h .= $eOperation->quick('paymentMethod', $title, 'dropdown-item');

			// COMMENTAIRE
			if($eOperation['comment'] === NULL) {
				$title = s("Ajouter un commentaire");
	 		} else {
				$title = s("Modifier le commentaire");
			}
			$h .= $eOperation->quick('comment', $title, 'dropdown-item');

			// PIÈCE COMPTABLE
			if($eOperation['document'] === NULL) {
				$title = s("Indiquer le n° de pièce comptable");
	 		} else {
				$title = s("Modifier la pièce comptable ({value})", encode($eOperation['document']));
			}
			$h .= $eOperation->quick('document', $title, 'dropdown-item');

			$h .= '<div class="dropdown-divider"></div>';

			// ACTION "SUPPRIMER"
			if($eOperation['cOperationCashflow']->notEmpty()) {

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
