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

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYearSelected, \bank\Cashflow $eCashflow, ?\account\ThirdParty $eThirdParty): string {

		\Asset::js('journal', 'operation.js');

		$h = '<div id="journal-search" class="util-block-search stick-xs '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH.'?financialYear='.$eFinancialYearSelected['id'].'&code='.GET('code');

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
					"Vous visualisez actuellement les écritures correspondant à l'opération bancaire du {date}, \"{memo}\" d'un {type} de {amount}.",
					[
						'date' => \util\DateUi::numeric($eCashflow['date']),
						'memo' => encode($eCashflow['memo']),
						'type' => mb_strtolower(\bank\CashflowUi::p('type')->values[$eCashflow['type']]),
						'amount' => \util\TextUi::money(abs($eCashflow['amount'])),
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

		if($eFarm['company']->isCashAccounting()) {
			return $this->getTableContainer($eFarm, $cOperation, $eFinancialYearSelected, $search, NULL);
		}

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
		?string $selectedJournalCode = NULL
	): string {

		if($cOperation->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-info">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-info">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		\Asset::js('util', 'form.js');
		\Asset::css('util', 'form.css');

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th>';

							if($eFarm['company']->isCashAccounting()) {
								$label = s("Date de transaction");
								$h .= ($search ? $search->linkSort('paymentDate', $label) : $label);
							} else {
								$label = s("Date de l'écriture");
								$h .= ($search ? $search->linkSort('date', $label) : $label);
							}
						$h .= '</th>';
						$h .= '<th>'.s("# Opération bancaire").'</th>';

						if($eFarm['company']->isAccrualAccounting() and $selectedJournalCode === NULL) {
							$h .= '<th>'.s("Journal").'</th>';
						}

						$h .= '<th>';
							$label = s("Pièce comptable");
							$h .= ($search ? $search->linkSort('document', $label) : $label);
						$h .= '</th>';
						$h .= '<th colspan="2">'.s("Compte (Libellé et classe)").'</th>';
						$h .= '<th>';
							$label = s("Description");
							$h .= ($search ? $search->linkSort('description', $label) : $label);
						$h .= '</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th class="text-end">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end">'.s("Crédit (C)").'</th>';
						$h .= '<th class="text-end"></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cOperation as $eOperation) {

						$canUpdate = ($eFinancialYearSelected['status'] === \account\FinancialYear::OPEN
							and $eOperation['date'] <= $eFinancialYearSelected['endDate']
							and $eOperation['date'] >= $eFinancialYearSelected['startDate']
							and $eFarm->canManage());

						$eOperation->setQuickAttribute('farm', $eFarm['id']);
						if($eOperation['cashflow']->exists() === TRUE) {
							$cashflowLink = \company\CompanyUi::urlBank($eFarm).'/cashflow?id='.$eOperation['cashflow']['id'];
						} else {
							$cashflowLink = NULL;
						}

						$h .= '<tr name="operation-'.$eOperation['id'].'" name-linked="operation-linked-'.($eOperation['operation']['id'] ?? '').'">';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eOperation['date']);
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['cashflow']->exists() === TRUE) {
									$h .= '<a href="'.$cashflowLink.'" class="color-text">'.$eOperation['cashflow']['id'].'</a>';
								} else {
									$h .= '';
								}
							$h .= '</td>';

							if($eFarm['company']->isAccrualAccounting() and $selectedJournalCode === NULL) {
								$h .= '<td>';

									if($eOperation['journalCode']) {
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$eOperation['journalCode'].'">'.mb_strtoupper($eOperation['journalCode']).'</a>';
									} else {
										$h .= '-';
									}

								$h .= '</td>';
							}
							$h .= '<td>';
								$h .= '<div class="operation-info">';
									if($canUpdate === TRUE) {
										$h .= $eOperation->quick('document', $eOperation['document'] ? encode($eOperation['document']) : '<i>'.s("Non définie").'</i>');
									} else {
										$h .= encode($eOperation['document']);
									}
								$h .= '</div>';
							$h .= '</td>';

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
									$h .= $eOperation->quick('description', encode($eOperation['description']));
								} else {
									$h .= encode($eOperation['description']);
								}
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['thirdParty']->exists() === TRUE) {
									$h .= encode($eOperation['thirdParty']['name']);
								}
							$h .= '</td>';

							$h .= '<td class="text-end">';
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

							$h .= '<td class="text-end">';
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

							$h .= '<td>';
								$h .= '<div class="util-unit text-end">';
									$h .= $this->displayActions($eFarm, $eOperation, $canUpdate, $cashflowLink);
								$h .= '</div>';
							$h .= '</td>';

						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	private function getCommentButton(string $icon, string $content): string {

		return '<span class="btn btn-outline-secondary" title="'.encode($content).'" >'.\Asset::icon($icon).'</span>';

	}

	protected function displayActions(\farm\Farm $eFarm, Operation $eOperation, bool $canUpdate, ?string $cashflowLink): string {

		if($canUpdate === FALSE or $eFarm->canManage() === FALSE) {

			if($eOperation['comment'] === NULL) {
				return '';
			}

			$icon = 'send-fill';
			$content = encode($eOperation['comment']);

			return $this->getCommentButton($icon, $content);
		}

		$h = '';

		if(
			$eOperation['comment'] !== NULL
		) {

			$icon = 'chat-left-text-fill';
			$content = encode($eOperation['comment']);

		} else {

			$icon = 'chat-left-text';
			$content = s("Ajouter un commentaire");

		}

		$eOperation->setQuickAttribute('farm', $eFarm['id']);
		$eOperation->setQuickAttribute('app', 'accounting');
		$h .= $eOperation->quick('comment', $this->getCommentButton($icon, $content));
		$h .= '&nbsp;';

		if(
			$eOperation->isClassAccount(\Setting::get('account\chargeAccountClass')) === TRUE
			// On ne rajoute pas des frais de port sur des frais de port
			and mb_substr($eOperation['accountLabel'], 0, strlen((string)\Setting::get('account\shippingChargeAccountClass'))) !== (string)\Setting::get('account\shippingChargeAccountClass')
		) {

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

			$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation:create?'.http_build_query($args).'" class="btn btn-outline-secondary" class="btn btn-outline-secondary" title="'.s("Ajouter des frais de livraison").'">'.\Asset::icon('truck').'</a>';

		} else {

			$h .= '<span class="not-visible btn btn-outline-secondary">'.\Asset::icon('truck').'</span>';

		}

		$h .= '&nbsp;';

		if($eOperation['cashflow']->exists() === FALSE) {

			// Cette opération est liée à une autre : on ne peut pas la supprimer.
			if($eOperation['operation']->exists() === TRUE) {

				$attributes = [
					'class' => 'btn btn-outline-secondary inactive',
					'data-dropdown' => 'bottom-end',
					'data-dropdown-hover' => TRUE,
					'data-dropdown-offset-x' => 0,
					'onclick' => 'void(0);',
				];
				$buttonDelete = '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>';
				$buttonDelete .= '<div class="operation-comment-dropdown dropdown-list dropdown-list-unstyled">';
					$buttonDelete .= s("Cette opération est liée à une autre opération. Supprimez plutôt l'opération initiale.");
				$buttonDelete .= '</div>';


			} else {

				$attributes = [
					'data-ajax' => \company\CompanyUi::urlJournal($eFarm).'/operation:doDelete',
					'post-id' => $eOperation['id'],
					'data-confirm' => s("Confirmez-vous la suppression de cette écriture ?"),
					'class' => 'btn btn-outline-secondary btn-outline-danger',
				];

				if($eOperation['vatAccount']->exists() === TRUE) {
					$attributes += [
						'data-dropdown' => 'bottom-end',
						'data-dropdown-hover' => 'TRUE',
						'data-dropdown-offset-x' => '0',
						'data-highlight' => $eOperation['vatAccount']->exists()
							? 'operation-linked-'.$eOperation['id']
							: 'operation-'.$eOperation['operation']['id'],
						'data-confirm' => s("Confirmez-vous la suppression de cette écriture ?"),
					];
				}

				$buttonDeleteDropdown = '';

				if($eOperation['asset']->exists() === TRUE) {

					if($eOperation['vatAccount']->exists() === TRUE) {

						$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture, de l'entrée de TVA liée, ainsi que de l'entrée dans les immobilisations ?");

						$buttonDeleteDropdown .= '<div class="operation-comment-dropdown dropdown-list dropdown-list-unstyled">';
							$buttonDeleteDropdown .= s("En supprimant cette écriture, l'écriture de TVA associée et l'entrée dans les immobilisations seront également supprimées.");
						$buttonDeleteDropdown .= '</div>';

					} else {

						$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture ainsi que de l'entrée dans les immobilisations ?");
						$buttonDeleteDropdown .= '<div class="operation-comment-dropdown dropdown-list dropdown-list-unstyled">';
							$buttonDeleteDropdown .= s("En supprimant cette écriture, l'entrée dans les immobilisations sera également supprimée.");
						$buttonDeleteDropdown .= '</div>';

					}

				} else if($eOperation['vatAccount']->exists() === TRUE) {

					$attributes['data-confirm'] = s("Confirmez-vous la suppression de cette écriture ainsi que de l'écriture de TVA associée ?");
					$buttonDeleteDropdown .= '<div class="operation-comment-dropdown dropdown-list dropdown-list-unstyled">';
						$buttonDeleteDropdown .= s("En supprimant cette écriture, l'écriture de TVA associée sera également supprimée.");
					$buttonDeleteDropdown .= '</div>';

				}

				$buttonDelete = '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>'.$buttonDeleteDropdown;
			}

			$h .= $buttonDelete;

		}

		return $h;
	}

}

?>
