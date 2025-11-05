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

					//$h .= '<a href="'.PdfUi::urlJournal($eFarm).'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('file-pdf').'&nbsp;'.s("Télécharger en PDF").'</a>';

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

		$h = '<div id="journal-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

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
					$h .= $form->select('cashflowFilter', [
						0 => s("Toutes les écritures"),
						1 => s("Écritures non rattachées à une opération bancaire"),
					], $search->get('cashflowFilter', 'int', 0), ['mandatory' => TRUE]);
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

	public function getJournalTabs(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Search $search, ?string $selectedJournalCode): string {

		$args = $search->toQuery(exclude: ['code']);

		$h = '<div class="tabs-item">';

			$h .= '<a class="tab-item'.($selectedJournalCode === NULL ? ' selected' : '').'" data-tab="journal" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?'.$args.'">'.s("Général").'</a>';

			foreach(Operation::model()->getPropertyEnum('journalCode') as $journalCode) {

					$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'&'.$args.'">';
						$h .= '<div class="text-center">';
							$h .= OperationUi::p('journalCode')->values[$journalCode];
							$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.OperationUi::p('journalCode')->shortValues[$journalCode].')</span></small>';
						$h .= '</div>';
					$h .= '</a>';

			}

			// Journaux de TVA
			if($eFinancialYear['hasVat']) {

				$journalCode = 'vat-buy';
				$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'&'.$args.'">';
					$h .= '<div class="text-center">';
						$h .= s("TVA");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("Achats").')</span></small>';
					$h .= '</div>';
				$h .= '</a>';

				$journalCode = 'vat-sell';
				$h .= '<a class="tab-item'.($selectedJournalCode === $journalCode ? ' selected' : '').'" data-tab="journal-'.$journalCode.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'&'.$args.'">';
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

		$columns = 5;

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';

						if($eFinancialYearSelected->canUpdate()) {
							$h .= '<th>';
							$h .= '</th>';
						}

						$h .= '<th>'.s("Compte").'</th>';

						if($journalCode === NULL) {
							$h .= '<th></th>';
						}

						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit (C)").'</th>';
						$h .= '<th></th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$currentDate = NULL;

					foreach($cOperation as $eOperation) {

						$canUpdate = (
							$eOperation->canUpdate()
						);

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

							if($eFinancialYearSelected->canUpdate()) {
								$h .= '<td class="td-checkbox">';
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
								if($canUpdate) {
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
								$h .= '<div class="dropdown-list bg-primary">';
									$h .= '<span class="dropdown-item">'.encode($eOperation['account']['class']).' '.encode($eOperation['account']['description']).'</span>';
								$h .= '</div>';
							$h .= '</td>';

							if($journalCode === NULL) {
								$h .= '<td>';
									$h .= $eOperation['journalCode'] === NULL ? '' : OperationUi::getShortJournal($eFarm, $eOperation['journalCode'], link: TRUE);
								$h .= '</td>';
							}

							$h .= '<td>';
								$class = ($eOperation['type'] === Operation::CREDIT ? ' ml-3' : '');
								$h .= '<div class="description'.$class.'">';
									if($eOperation['asset']->exists() === TRUE) {
										$attributes = [
											'href' => \company\CompanyUi::urlAsset($eFarm).'/'.$eOperation['asset']['id'].'/',
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
							$h .= '<td>';
								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.new OperationUi()->getTitle($eOperation).'</div>';
									$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'" class="dropdown-item">'.s("Ouvrir le détail").'</a>';
									if($eOperation->canUpdate()) {
										$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'/update" class="dropdown-item">'.s("Modifier").'</a>';
									}
								$h .= '</div>';
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cPaymentMethod): string {

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

		foreach(Operation::model()->getPropertyEnum('journalCode') as $journalCode) {

			$warningText = match($journalCode) {
				Operation::ACH => s("Déplacer ces écritures dans le journal d'achats ?"),
				Operation::VEN => s("Déplacer ces écritures dans le journal de ventes ?"),
				Operation::OD => s("Déplacer ces écritures dans le journal d'opérations diverses ?"),
				Operation::BAN => s("Déplacer ces écritures dans le journal de banque ?"),
				Operation::KS => s("Déplacer ces écritures dans le journal de caisse ?"),
			} ;

			$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateJournalCollection" post-journal-code="'.$journalCode.'"  data-confirm="'.$warningText.'" class="batch-menu-'.$journalCode.' batch-menu-item">';
				$menu .= '<span class="btn btn-sm journal-code-batch journal-code-button">'.OperationUi::p('journalCode')->shortValues[$journalCode].'</span>';
			$menu .= '<span>'.OperationUi::p('journalCode')->values[$journalCode].'</span>';
			$menu .= '</a>';
		}

		$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
			$menu .= \Asset::icon('cash-coin');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Moyen de paiement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';

			$menu .= '<div class="dropdown-title">'.s("Changer de moyen de paiement").'</div>';
			foreach($cPaymentMethod as $ePaymentMethod) {
				if($ePaymentMethod['online'] === FALSE) {
					$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdatePaymentCollection" data-ajax-target="#batch-group-form" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}
			$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdatePaymentCollection" data-ajax-target="#batch-group-form" post-payment-method="" class="dropdown-item"><i>'.s("Pas de moyen de paiement").'</i></a>';
		$menu .= '</div>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createCommentCollection" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('chat-text-fill').'<span>'.s("Commenter").'</span></a>';

		$menu .= '<a data-ajax-submit="'.\company\CompanyUi::urlJournal($eFarm).'/operation:createDocumentCollection" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('paperclip').'<span>'.s("Pièce comptable").'</span></a>';

		$menu .= '<span class="hide" data-batch-title-more-singular>'.s(", dont 1 opération liée").'</span>';
		$menu .= '<span class="hide" data-batch-title-more-plural>'.s(", dont <span data-batch-title-more-value></span> opérations liées").'</span>';

		return \util\BatchUi::group($menu, '', title: s("Pour les opérations sélectionnées"));

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
