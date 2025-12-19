<?php
namespace bank;

class CashflowUi {

	public function __construct() {
		\Asset::js('bank', 'cashflow.js');
		\Asset::css('company', 'company.css');
		\Asset::css('journal', 'journal.css');
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, string $minDate, string $maxDate, \Collection $cBankAccount): string {

		$h = '<div id="cashflow-search" class="util-block-search '.($search->empty(['ids']) ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;
		$statuses = CashflowUi::p('status')->values;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

		$h .= '<div>';
			if($cFinancialYear->count() > 1) {
				$values = [];
				foreach($cFinancialYear as $eFinancialYearCurrent) {
					$values[] = ['value' => $eFinancialYearCurrent['id'], 'label' => s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearCurrent))];
				}
				$h .= $form->select('year', $values, $search->get('financialYear')['id'] ?? NULL, ['placeholder' => s("Exercice comptable")]);
			}
			$h .= $form->inputGroup($form->addon(s("entre")).
				$form->month('periodStart', $search->get('periodStart'), ['min' => $minDate, 'max' => $maxDate, 'placeholder' => s("Début")]).
				$form->addon("et").
				$form->month('periodEnd', $search->get('periodEnd'), ['min' => $minDate, 'max' => $maxDate, 'placeholder' => s("Fin")]),
				['class' => 'company-period-input-group']
			);
			$h .= $form->text('memo', $search->get('memo'), ['placeholder' => s("Libellé")]);
			$h .= $form->select('status', $statuses, $search->get('status'), ['placeholder' => s("Statut")]);
			$h .= $form->select('isReconciliated', [TRUE => s("oui"), FALSE => s("non")], $search->get('isReconciliated'), ['placeholder' => s("Rapprochée")]);
			$h .= $form->inputGroup($form->addon(s('Montant'))
					.$form->number('amount', $search->get('amount'), ['style' => 'width: 100px', 'step' => 0.01])
					.$form->addon(s('+/-'))
					.$form->number('margin', $search->get('margin', 1))
					.$form->addon(s('€'))
			);
			if($cBankAccount->count() > 1) {

				$values = [];
				foreach($cBankAccount as $eBankAccount) {
					$values[$eBankAccount['id']] = $eBankAccount['label'];
				}
				$h .= $form->select('bankAccount', $values, $search->get('bankAccount'), ['placeholder' => s("N° de compte")]);

			}
		$h .= '</div>';
		$h .= '<div>';
			$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
			$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getSummarize(
		\farm\Farm $eFarm,
		\Collection $nCashflow,
		\Search $search
	): string {

		if(($nCashflow[Cashflow::WAITING]['count'] ?? 0) === 0) {
			return '';
		}

		$h = '<div class="flex-justify-space-between td-vertical-align-top mb-1">';
			$h .= '<div class="">';
				$form = new \util\FormUi();

				$h .= $form->openUrl(\company\CompanyUi::urlFarm($eFarm).'/banque/operations', ['method' => 'get']);
				$h .= $form->checkbox('status', Cashflow::WAITING, [
					'checked' => $search->get('status') === Cashflow::WAITING,
					'callbackLabel' => fn($input) => $input.' '.s("N'afficher que les opérations non traitées {value}", '<span class="util-counter">'.$nCashflow[Cashflow::WAITING]['count'].'</span>'),
					'onchange' => 'this.form.submit()',
				]);
				$h .= $form->close();

			$h .= '</div>';

			$h .= '<div>';
			$h .= '<a href="/doc/accounting:bank" target="_blank" class="btn btn-xs btn-outline-primary">'.\asset::Icon('person-raised-hand').' '.s("Aide").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getCashflow(
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
				return '<div class="util-info">'.
					s("Aucune opération bancaire ne correspond à vos critères de recherche.").
					'</div>';

			}
			return '<div class="util-info">'.
				s("Aucun import bancaire n'a été réalisé (<link>importer</link>)", [
					'link' => '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/imports">',
				]).
			'</div>';
		}

		$highlightedCashflowId = GET('id', 'int');
		$showMonthHighlight = $search->getSort() === 'date';
		$showReconciliate = $cCashflow->find(fn($e) => $e['isReconciliated'])->count() > 0;
		$showAccount = count(array_unique($cCashflow->getColumnCollection('import')->getColumnCollection('account')->getColumn('label'))) > 1;

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

		$h .= '<div id="cashflow-list" class="stick-sm util-overflow-sm" '.($highlightedCashflowId !== NULL ? ' onrender="CashflowList.scrollTo('.$highlightedCashflowId.');"' : '').' data-render-timeout="1">';

			$h .= '<table class="tr-even tr-hover td-padding-sm">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';
						$h .= '<th class="td-vertical-align-middle">';
							$label = s("#");
							$h .= ($search ? $search->linkSort('id', $label) : $label);
						$h .= '</th>';

						if($showAccount) {
							$h .= '<th class="td-vertical-align-middle td-min-content">'.s("N° Compte").'</th>';
						}

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

				$lastMonth = '';
				foreach($cCashflow as $eCashflow) {

					if($showMonthHighlight and ($lastMonth === '' or $lastMonth !== substr($eCashflow['date'], 0, 7))) {
						$lastMonth = substr($eCashflow['date'], 0, 7);

							$h .= '<tr class="row-emphasis row-bold">';

								$h .= '<td class="td-min-content" colspan="8">';
									$h .= mb_ucfirst(\util\DateUi::textual($eCashflow['date'], \util\DateUi::MONTH_YEAR));
								$h .= '</td>';

							$h .= '</tr>';
					}

					$class = [];
					if($highlightedCashflowId === $eCashflow['id']) {
						$class[] = 'row-highlight';
					}
					if($eCashflow['status'] === CashflowElement::DELETED) {
						$class[] = 'cashflow-strikethrough';
					}
					$h .= '<tr name="cashflow-'.$eCashflow['id'].'" class="'.join(' ', $class).'">';

						$h .= '<td class="text-left td-vertical-align-top">';
							$h .= encode($eCashflow['id']);
						$h .= '</td>';

						if($showAccount) {
							$h .= '<td class="text-left td-vertical-align-top">';
								$h .= encode($eCashflow['import']['account']['label']);
							$h .= '</td>';
						}

						$h .= '<td class="td-vertical-align-top">';
							$h .= \util\DateUi::numeric($eCashflow['date']);
						$h .= '</td>';

						$h .= '<td class="td-description td-vertical-align-top color-primary">';
							$h .= encode($eCashflow['memo']);
						$h .= '</td>';

						if($showReconciliate) {
							$h .= '<td class="text-center td-vertical-align-top">';
								if($eCashflow['isReconciliated']) {
									if($eCashflow['sale']->notEmpty()) {
										$h .= '<a href="/vente/'.$eCashflow['sale']['id'].'">'.encode($eCashflow['sale']['document']).'</a>';
									} else if($eCashflow['invoice']->notEmpty()) {
										$h .= '<a href="/ferme/'.$eFarm['id'].'/factures?document='.encode($eCashflow['invoice']['document']).'&customer='.encode($eCashflow['invoice']['customer']['name']).'">'.encode($eCashflow['invoice']['name']).'</a>';
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

							$h .= '<td class="td-vertical-align-top">';

								if($eCashflow['status'] === Cashflow::WAITING) {

									$h .= '<a data-dropdown="bottom-end" class="btn btn-outline-primary dropdown-toggle">'.s("Ajouter").'</a> ';

									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-title">'.s("Ajouter des écritures comptables").'</div>';

											$financialYearOptions = [];
											foreach($cFinancialYear as $eFinancialYearCurrent) {

												if($eFinancialYearCurrent->acceptUpdate() === FALSE) {
													continue;
												}

												$financialYearOption = '<div class="dropdown-subtitle">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearCurrent)).'</div>';
												$financialYearOption .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:allocate?id='.$eCashflow['id'].'&financialYear='.$eFinancialYearCurrent['id'].'" class="dropdown-item">';
													$financialYearOption .= s("Créer de nouvelles écritures");
												$financialYearOption .= '</a>';

												$financialYearOption .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:attach?id='.$eCashflow['id'].'" class="dropdown-item">';
													$financialYearOption .= s("Rattacher des écritures existantes");
												$financialYearOption .= '</a>';

												$financialYearOptions[] = $financialYearOption;
											}

										$h .= join('<div class="dropdown-divider"></div>', $financialYearOptions);
									$h .= '</div>';

								} else if($eCashflow['status'] !== Cashflow::DELETED) {

									$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?cashflow='.$eCashflow['id'].'">'.p("{value} écriture", "{value} écritures", $eCashflow['cOperationCashflow']->count()).'</a>';

								}
							$h .= '</td>';

						}

						$h .= '<td class="td-min-content text-center">';

							if($eFarm->usesAccounting() === FALSE) {

								if($eCashflow['status'] === Cashflow::DELETED) {

									$h .= '<a class="btn btn-outline-secondary" data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:undoDelete" post-id="'.$eCashflow['id'].'">';
										$h .= s("Récupérer");
									$h .= '</a>';

								} else {

									$h .= '<a class="btn btn-outline-danger" data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:doDelete" post-id="'.$eCashflow['id'].'">';
										$h .= \Asset::icon('trash');
									$h .= '</a>';

								}

							} else if($eFinancialYear->acceptUpdate()) {

								$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
								$h .= $this->getAction($eFarm, $eFinancialYear, $eCashflow);

							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	protected function getAction(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow): string {

		if($eFarm->canManage() === FALSE) {
			return '';
		}


		$h = '<div class="dropdown-list">';

			$h .= '<div class="dropdown-title">'.s("Opération bancaire #{value}", $eCashflow['id']).'</div>';

			$actions = FALSE;
			if($eCashflow['status'] === CashflowElement::ALLOCATED) {

				$actions = TRUE;

				$h .= '<div class="dropdown-title">'.s("Actions sur les écritures comptables liées").'</div>';

				$confirm = s("Cette action dissociera les écritures de l'opération bancaire sans les supprimer. Confirmez-vous ?");
				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate" post-action="dissociate" post-id="'.$eCashflow['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
					$h .= s("Dissocier <div>(Ne supprimera <b><u>pas</u></b> les écritures)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '</a>';

				$confirm = s("Cette action supprimera toutes les écritures liées à l'opération bancaire. Confirmez-vous ?");
				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate" post-action="delete" post-id="'.$eCashflow['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
					$h .= s("Supprimer <div>(<b><u>Supprimera</u></b> les écritures)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '</a>';

				$h .= '<div class="dropdown-divider"></div>';

			}

			if($eCashflow['status'] === Cashflow::DELETED) {

				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:undoDelete"  post-id="'.$eCashflow['id'].'" class="dropdown-item">';
					$h .= s("Annuler la suppression de l'opération bancaire");
				$h .= '</a>';

			} else if($eCashflow['status'] === Cashflow::ALLOCATED) {

				$h .= '<div class="dropdown-title">'.s("Actions sur l'opération bancaire").'</div>';

				$deleteText = s("Supprimer l'opération bancaire<div>(Supprimez d'abord les écritures liées)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '<a class="dropdown-item inactive">'.$deleteText.'</a>';

			} else {

				if($actions) {

					$h .= '<div class="dropdown-divider"></div>';
					$h .= '<div class="dropdown-title">'.s("Actions sur l'opération bancaire").'</div>';

				}

				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:doDelete"  post-id="'.$eCashflow['id'].'" class="dropdown-item">';
					$h .= s("Supprimer l'opération bancaire");
				$h .= '</a>';

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
				$h .= '<dd>'.encode($eCashflow['memo']).'</dd>';
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

	public function getAllocateTitle(Cashflow $eCashflow, \account\FinancialYear $eFinancialYear, array $defaultValues, \Collection $cPaymentMethod, \util\FormUi $form): string {

		$title = '<div class="panel-title-container">';
			$title .= '<h2 class="panel-title">'.encode($eCashflow['memo']).'</h2>';
			$title .= '<a class="panel-close-desktop" onclick="Lime.Panel.closeLast()">'.\Asset::icon('x').'</a>';
			$title .= '<a class="panel-close-mobile" onclick="Lime.Panel.closeLast()">'.\Asset::icon('arrow-left-short').'</a>';
		$title .= '</div>';

		$subtitle = '<h2 class="panel-subtitle">';
			$subtitle .= s(
				"Opération bancaire #<b>{number}</b> du <b>{date}</b> | <b>{type}</b> d'un montant de <b class='color-primary'>{amount}</b>",
				[
					'amount' => \util\TextUi::money(abs($eCashflow['amount'])),
					'number' => $eCashflow['id'],
					'type' => self::p('type')->values[$eCashflow['type']],
					'date' => \util\DateUi::numeric($eCashflow['date'], \util\DateUi::DATE),
				]
			);
		$subtitle .= '</h2>';
		$subtitle .= '<div class="cashflow-create-operation-general">';
			$subtitle .= '<div class="cashflow-operation-create-title">'.\journal\OperationUi::p('paymentDate').'</div>';
			$subtitle .= $form->date(
				'paymentDate',
					$defaultValues['paymentDate'] ?? '',
				['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']] + (array_key_exists('id', $defaultValues) ? ['disabled' => 'disabled'] : []),
			);
			$subtitle .= '<div class="cashflow-operation-create-title">'.\journal\OperationUi::p('paymentMethod').'</div>';
			$subtitle .= $form->select(
				'paymentMethod',
				$cPaymentMethod,
					$defaultValues['paymentMethod'] ?? '',
				['mandatory' => TRUE] + (array_key_exists('id', $defaultValues) ? ['disabled' => 'disabled'] : []),
			);
		$subtitle .= '</div>';

		return $title.$subtitle;

	}

	public function getAllocate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, \Collection $cPaymentMethod, \Collection $cJournalCode): \Panel {

		\Asset::js('journal', 'operation.js');
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
			'description' => $eCashflow['memo'],
			'paymentDate' => $eCashflow['date'],
			'paymentMethod' => self::extractPaymentTypeFromCashflowDescription($eCashflow['memo'], $cPaymentMethod->filter(fn($e) => $e['use']->value(\payment\Method::ACCOUNTING))),
			'amountIncludingVAT' => abs($eCashflow['amount']),
		];

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('type', $eCashflow['type']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);
		$h .= '<span name="cashflow-amount" class="hide">'.$eCashflow['amount'].'</span>';

		$h .= \journal\OperationUi::getCreateGrid($eFarm, $eOperation, $eFinancialYear, $index, $form, $defaultValues, $cPaymentMethod);

		$amountWarning = '<div id="cashflow-allocate-difference-warning" class="util-danger hide">';
			$amountWarning .= s("Attention, les montants saisis doivent correspondre au montant total de la transaction. Il y a une différence de {difference}.", ['difference' => '<span id="cashflow-allocate-difference-value">0</span>']);
		$amountWarning .= '</div>';

		$addButton = '<a id="add-operation" onclick="Cashflow.recalculateAmounts(); return TRUE;" data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:addAllocate" post-index="'.($index + 1).'" post-id="'.$eCashflow['id'].'" post-third-party="" post-amount="" class="btn btn-outline-secondary">';
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
			header     : $this->getAllocateTitle($eCashflow, $eFinancialYear, $defaultValues, $cPaymentMethod, $form),
			footer     : $amountWarning.'<div class="operation-create-buttons">'.$addButton.$saveButton.'</div>',
		);

	}

	public static function addAllocate(\farm\Farm $eFarm, \journal\Operation $eOperation, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, int $index, \Collection $cPaymentMethod): string {

		$form = new \util\FormUi();
		$form->open('bank-cashflow-allocate');
		$defaultValues = [
			'date' => $eCashflow['date'],
			'type' => $eCashflow['type'],
			'description' => $eCashflow['memo'],
		];

		return \journal\OperationUi::getFieldsCreateGrid($eFarm, $form, $eOperation, $eFinancialYear, '['.$index.']', $defaultValues, [], $cPaymentMethod);

	}

	public static function import(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = '';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Seul l'export au format <b>.ofx</b> est actuellement supporté. Ce format est disponible sur la plupart des sites bancaires.").'</p>';
			$h .= '<p>'.s("Si certains flux bancaires ont déjà été précédemment importés, ils seront ignorés.").'</p>';
		$h .= '</div>';

		$h .= '<div class="util-info">'.s("Si le compte bancaire est inconnu, il sera automatiquement créé et vous pourrez paramétrer son libellé dans le <link>paramétrage des comptes bancaires</link>.", ['link' => '<a href="'.\company\CompanyUi::urlBank($eFarm).'/account">']).'</div>';


		$h .= $form->openUrl(\company\CompanyUi::urlFarm($eFarm).'/banque/imports:doImport', ['id' => 'cashflow-import', 'binary' => TRUE, 'method' => 'post']);
			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= '<label class="btn btn-primary">';
				$h .= $form->file('ofx', ['onchange' => 'this.form.submit()', 'accept' => '.ofx']);
				$h .= s("Importer un fichier OFX depuis mon ordinateur");
			$h .= '</label>';
		$h .= $form->close();

		return new \Panel(
			id: 'panel-cashflow-import',
			title: s("Importer un relevé bancaire"),
			body: $h
		);
	}

	public function getAttach(\farm\Farm $eFarm, Cashflow $eCashflow, \account\ThirdParty $eThidParty, \Collection $cOperation): \Panel {

		\Asset::js('bank', 'cashflow.js');
		\Asset::css('bank', 'cashflow.css');
		$h = CashflowUi::getCashflowHeader($eCashflow);

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(\company\CompanyUi::urlBank($eFarm).'/cashflow:doAttach', ['method' => 'post', 'id' => 'cashflow-doAttach', 'class' => 'panel-dialog']);

		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('cashflow-amount', $eCashflow['amount']);

		$h .= '<div class="util-info">';
			$h .= s("Vous pouvez rattacher directement des écritures comptables déjà saisies avec l'opération bancaire. La contrepartie en compte de banque {bankAccount} sera automatiquement saisie.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);
		$h .= '</div>';

		$h .= '<div class="mb-3">';
			$h .= '<h3>'.\Asset::icon('1-circle').' '.s("Indiquez le tiers lié à cette opération bancaire").'</h3>';
			$h .= $form->dynamicField(new \journal\Operation(['thirdParty' => $eThidParty]), 'thirdParty', function($d) use($form, $eThidParty) {
				$d->autocompleteDispatch = '[data-third-party="'.$form->getId().'"]';
				$d->attributes['data-third-party'] = $form->getId();
				$d->default = fn($e, $property) => GET('thirdParty');
				$d->label = '';
				$d->wrapper = 'third-party';
			});
		$h .= '</div>';

		$h .= '<h3>'.\Asset::icon('2-circle').' '.s("Sélectionnez les écritures liées à cette opération bancaire").'</h3>';
			$h .= $form->dynamicField(new \journal\OperationCashflow(), 'operation', function($d) use($form, $eCashflow) {
				$d->autocompleteDispatch = '[data-operation="'.$form->getId().'"]';
				$d->attributes['data-operation'] = $form->getId();
				$d->default = fn($e, $property) => get('operation');
				$d->label = '';
				$d->wrapper = 'operation';
				$d->autocompleteBody = function() {
					return [
					];
				};
				$d->group += ['wrapper' => 'operation'];
				new \journal\OperationUi()->query($d, GET('farm', '?int'), $eCashflow);
			});

		$h .= '<div class="stick-sm util-overflow-sm">';
			$h .= '<table id="cashflow-operations" class="tr-even mt-2 tr-hover '.($cOperation->empty() ? 'hide' : '').'">';

				$h .= '<thead>';
					$h .= '<tr class="row-header">';
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
		$h .= '</div>';

		$footer = '<div class="cashflow-attach-panel-footer" onrender="CashflowAttach.recalculate();">'.
			'<div><div id="cashflow-attach-difference-warning" class="util-warning-outline hide" style="margin: 0;">'.
				s("⚠️ Le montant de l'opération bancaire ne correspond pas au total des écritures sélectionnées ({span} de différence). Vous pouvez quand même valider.", ['span' => '<span id="cashflow-attach-missing-value"></span>']).
			'</div></div>'.
			'<div>'.s("Total sélectionné : {value}", '<span data-field="totalAmount" class="mr-2">'.\util\TextUi::money(0).'</span>').'</div>'.
			$form->submit(s("Rattacher"), ['class' => 'btn btn-secondary']).
			'</div>';

		return new \Panel(
			id: 'panel-cashflow-attach',
			title: s("Rattacher l'opération bancaire"),
			body: $h,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			footer: $footer,
		);

	}

	public static function getOperationLineForAttachment(Cashflow $eCashflow, \journal\Operation $eOperation): string {

		$form = new \util\FormUi();

		$amount = $eOperation['type'] === \journal\Operation::DEBIT ? -1 * $eOperation['amount'] :$eOperation['amount'];
		foreach($eOperation['cOperationLinked'] as $eOperationLinked) {
			$amount += $eOperationLinked['type'] === \journal\Operation::DEBIT ?  -1 * $eOperationLinked['amount'] : $eOperationLinked['amount'];
		}

		$h = '<tr data-operation="'.$eOperation['id'].'">';
			$h .= '<td '.($eCashflow['date'] < $eOperation['date'] ? 'class="color-warning" title="'.s("Attention, l'opération bancaire est antérieure à l'écriture comptable").'"' : '').'>';
					$h .= \util\DateUi::numeric($eOperation['date']);
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

	public static function getName(Cashflow $eCashflow): string {

		return s("Transaction #{id}", ['id' => $eCashflow['id']]);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Cashflow::model()->describer($property, [
			'date' => s("Date"),
			'document' => s("Pièce comptable"),
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

			case 'document':
				$d->after = \util\FormUi::info(s("Nom de la pièce comptable de référence (n° facture, ...)."));
				$d->attributes = [
					'onchange' => 'Cashflow.copyDocument(this)'
				];
				break;

			case 'type':
				$d->values = [
					CashflowElement::CREDIT => s("Crédit"),
					CashflowElement::DEBIT => s("Débit"),
				];
				break;

			case 'status' :
				$d->values = [
					CashflowElement::ALLOCATED => s("Traitée"),
					CashflowElement::DELETED => s("Supprimée"),
					CashflowElement::WAITING => s("À traiter"),
				];
				$d->translation = [
					CashflowElement::ALLOCATED => ['singular' => s("Traitée"), 'plural' => s("Traitées")],
					CashflowElement::DELETED => ['singular' => s("Supprimée"), 'plural' => s("Supprimées")],
					CashflowElement::WAITING => ['singular' => s("À traiter"), 'plural' => s("À traiter")],
				];
				$d->shortValues = [
					CashflowElement::ALLOCATED => s("T"),
					CashflowElement::DELETED => s("S"),
					CashflowElement::WAITING => s("A"),
				];
				break;
		}

		return $d;

	}
}

?>
