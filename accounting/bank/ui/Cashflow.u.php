<?php
namespace bank;

class CashflowUi {

	public function __construct() {
		\Asset::js('bank', 'cashflow.js');
		\Asset::css('company', 'company.css');
		\Asset::css('journal', 'journal.css');
	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYearSelected): string {

		$h = '<div id="cashflow-search" class="util-block-search stick-xs '.($search->empty(['ids', 'status']) ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH.'?financialYear='.$eFinancialYearSelected['id'];
		$statuses = CashflowUi::p('status')->values;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

		$h .= '<div>';
			$h .= $form->month('date', $search->get('date'), ['placeholder' => s("Mois")]);
			$h .= $form->text('memo', $search->get('memo'), ['placeholder' => s("Libellé")]);
			$h .= $form->select('status', $statuses, $search->get('status'), ['placeholder' => s("Statut")]);
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

		$h = '<ul class="util-summarize util-summarize-overflow">';

			foreach(CashflowUi::p('status')->translation as $status => $translation) {

				$count = $nCashflow[$status]['count'] ?? 0;

				$h .= '<li '.($search->get('status') === $status ? 'class="selected"' : '').'>';

					$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow?'.$search->toQuery(['status']).'&status='.$status.'">';

						$h .= '<h5>';
							if($count > 1) {
								$h .= $translation['plural'];
							} else {
								$h .= $translation['singular'];
							}
						$h .='</h5>';

						$h .= '<div>'.$count.'</div>';

					$h .= '</a>';

				$h .= '</li>';

			}
		$h .= '</ul>';
		return $h;
	}

	public function getCashflow(
		\farm\Farm $eFarm,
		\Collection $cCashflow,
		\account\FinancialYear $eFinancialYearSelected,
		Import $eImport,
		\Search $search,
	): string {

		\Asset::css('bank', 'cashflow.css');

		if($cCashflow->empty() === TRUE) {
			if($search->empty(['ids']) === FALSE) {
				return '<div class="util-info">'.
					s("Aucune opération bancaire ne correspond à vos critères de recherche.").
					'</div>';

			}
			return '<div class="util-info">'.
				s("Aucun import bancaire n'a été réalisé pour l'exercice {year} (<link>importer</link>)", [
					'year' => \account\FinancialYearUi::getYear($eFinancialYearSelected),
					'link' => '<a href="'.\company\CompanyUi::urlBank($eFarm).'/import">',
				]).
			'</div>';
		}

		$highlightedCashflowId = GET('id', 'int');
		$showMonthHighlight = $search->getSort() === 'date';

		$h = '';

		if($eImport->exists() === TRUE) {

			$h .= '<div class="util-block-search stick-xs">';
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
							$label = s("Numéro d'Op.");
							$h .= ($search ? $search->linkSort('id', $label) : $label);
						$h .= '</th>';
						$h .= '<th class="td-vertical-align-middle">';
							$label = s("Date");
							$h .= ($search ? $search->linkSort('date', $label) : $label);
						$h .= '</th>';
						$h .= '<th class="td-vertical-align-middle">';
							$label = s("Libellé");
							$h .= ($search ? $search->linkSort('memo', $label) : $label);
						$h .= '</th>';
						$h .= '<th class="text-end highlight-stick-right td-vertical-align-middle">'.s("Débit (D)").'</th>';
						$h .= '<th class="text-end highlight-stick-left td-vertical-align-middle">'.s("Crédit (C)").'</th>';
						$h .= '<th class="text-center td-vertical-align-middle">'.s("Statut").'</th>';
						$h .= '<th></th>';
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

					$h .= '<tr name="cashflow-'.$eCashflow['id'].'" '.($highlightedCashflowId === $eCashflow['id'] ? ' class="row-highlight"' : '').'>';

						$h .= '<td class="text-left">';
							$h .= encode($eCashflow['id']);
						$h .= '</td>';

						$h .= '<td>';
							$h .= \util\DateUi::numeric($eCashflow['date']);
						$h .= '</td>';

						$h .= '<td class="td-description color-primary">';
							$h .= '<div>';
								if($eCashflow['cInvoice']->notEmpty()) {
									$h .= '<span class="color-success" style="cursor: help" title="'.s("Une facture en attente de paiement a été trouvée").'">'.\Asset::icon('magic').'</span>&nbsp;';
								}
								$h .= encode($eCashflow['memo']);
							$h .= '</div>';
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-right">';
							$h .= match($eCashflow['type']) {
								CashflowElement::DEBIT => \util\TextUi::money(abs($eCashflow['amount'])),
								default => '',
							};
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-left">';
							$h .= match($eCashflow['type']) {
								CashflowElement::CREDIT => \util\TextUi::money(abs($eCashflow['amount'])),
								default => '',
							};
						$h .= '</td>';

					$h .= '<td class="td-min-content text-center">';
					if($eCashflow['status'] === CashflowElement::ALLOCATED) {
						$h .= '<a class="cashflow-status-label cashflow-status-'.$eCashflow['status'].'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?cashflow='.$eCashflow['id'].'">';
							$h .= CashflowUi::p('status')->values[$eCashflow['status']];
						$h .= '</a>';
					} else {
						$h .= '<div class="cashflow-status-label cashflow-status-'.$eCashflow['status'].'">';
							$h .= CashflowUi::p('status')->values[$eCashflow['status']];
						$h .= '</div>';
					}
					$h .= '</td>';

					$h .= '<td>';
							if(
								$eFinancialYearSelected['status'] === \account\FinancialYear::OPEN
								&& $eCashflow['date'] <= $eFinancialYearSelected['endDate']
								&& $eCashflow['date'] >= $eFinancialYearSelected['startDate']
							) {
								$h .= $this->getUpdate($eFarm, $eCashflow);
							}
						$h .= '</td>';

					$h .= '</tr>';
				}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}
	protected function getUpdate(\farm\Farm $eFarm, Cashflow $eCashflow): string {

		if($eFarm->canManage() === FALSE) {
			return '';
		}

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';

			if($eCashflow['status'] === CashflowElement::ALLOCATED) {

				$h .= '<div class="dropdown-title">'.s("Annuler les écritures comptables").'</div>';

				$confirm = s("Cette action dissociera les écritures de l'opération bancaire sans les supprimer. Confirmez-vous ?");
				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate" post-action="dissociate" post-id="'.$eCashflow['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
					$h .= s("Dissocier <div>(Ne supprimera <b><u>pas</u></b> les écritures)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '</a>';

				$confirm = s("Cette action supprimera toutes les écritures liées à l'opération bancaire. Confirmez-vous ?");
				$h .= '<a data-ajax="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:deAllocate" post-action="delete" post-id="'.$eCashflow['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
					$h .= s("Supprimer <div>(<b><u>Supprimera</u></b> les écritures)</div>", ['div' => '<div class="operations-delete-more">']);
				$h .= '</a>';

			} else if($eCashflow['status'] === CashflowElement::WAITING) {

				$h .= '<div class="dropdown-title">'.s("Créer des écritures comptables").'</div>';

				$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:allocate?id='.$eCashflow['id'].'" class="dropdown-item">';
					$h .= s("Créer de nouvelles écritures");
				$h .= '</a>';

				$h .= '<a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow:attach?id='.$eCashflow['id'].'" class="dropdown-item">';
					$h .= s("Rattacher des écritures comptables");
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
			mb_strpos(mb_strtolower($description), 'virement') !== FALSE
			or mb_strpos(mb_strtolower($description), 'sepa') !== FALSE
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'transfer')->first();
		}

		if(
			mb_strpos(mb_strtolower($description), 'prélèvement') !== FALSE
			or mb_strpos(mb_strtolower($description), 'prelevement') !== FALSE
		) {
			return $cPaymentMethod->find(fn($e) => $e['fqn'] === 'direct-debit')->first();
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

	public static function getAllocate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, array $assetData, \Collection $cPaymentMethod): \Panel {

		\Asset::js('journal', 'operation.js');
		\Asset::js('bank', 'cashflow.js');
		\Asset::js('journal', 'asset.js');
		\Asset::js('account', 'thirdParty.js');

		\Asset::css('journal', 'operation.css');
		\Asset::css('bank', 'cashflow.css');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlBank($eFarm).'/cashflow:doAllocate',
			[
				'id' => 'bank-cashflow-allocate',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog container',
			]
		);

		$eOperation = new \journal\Operation(['account' => new \account\Account()]);
		$index = 0;
		$defaultValues = [
			'date' => $eCashflow['date'],
			'amount' => abs($eCashflow['amount']),
			'type' => $eCashflow['type'],
			'description' => $eCashflow['memo'],
			'paymentDate' => $eCashflow['date'],
			'paymentMethod' => self::extractPaymentTypeFromCashflowDescription($eCashflow['memo'], $cPaymentMethod->filter(fn($e) => $e['use']->value(\payment\Method::ACCOUNTING))),
			'cashflow' => $eCashflow,
			'amountIncludingVAT' => abs($eCashflow['amount']),
		];

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eCashflow['id']);
		$h .= $form->hidden('type', $eCashflow['type']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);
		$h .= '<span name="cashflow-amount" class="hide">'.$eCashflow['amount'].'</span>';

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
		$subtitle .= '<div class="create-operation-cashflow-general mt-1">';
			$subtitle .= '<div class="create-operation-cashflow-title">'.\journal\OperationUi::p('paymentDate').'</div>';
			$subtitle .= $form->date(
				'paymentDate',
					$defaultValues['paymentDate'] ?? '',
				['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']],
			);
			$subtitle .= '<div class="create-operation-cashflow-title">'.\journal\OperationUi::p('paymentMethod').'</div>';
			$subtitle .= $form->select(
				'paymentMethod',
				$cPaymentMethod,
					$defaultValues['paymentMethod'] ?? '',
				['mandatory' => TRUE],
			);
		$subtitle .= '</div>';

		$h .= \journal\OperationUi::getCreateGrid($eFarm, $eOperation, $eFinancialYear, $index, $form, $defaultValues, $assetData, new \Collection());

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

		if($eCashflow['cInvoice']->notEmpty()) {

			if($eCashflow['cInvoice']->count() > 1) {

				$h .= '<div class="mt-1">';
					$h .= p("Une facture a été trouvée !", "{value} factures ont été trouvées !", $eCashflow['cInvoice']->count()).' ';
					$h .= s("Sélectionnez celle qui correspond à l'opération bancaire.");
				$h .= '</div>';

			}

			foreach($eCashflow['cInvoice'] as $eCashflowInvoice) {

				$eInvoice = $eCashflowInvoice['invoice'];

				$h .= '<div class="single-checkbox mt-1">'.$form->checkbox('invoice[id]', $eInvoice['id'], ['callbackLabel' => fn($input) => '<div>'.$input.'</div><div>'.s("Une facture {number} adressée à {clientName} d'un montant de {amount} du {date} a été trouvée.<br />Souhaitez-vous l'enregistrer comme <b>payée</b> ? Si oui, vous pouvez modifier le moyen de paiement si nécessaire :<br />{paymentMethod}", [
					'number' => '<b>'.encode($eInvoice['name']).'</b>',
					'date' => '<b>'.\util\DateUi::numeric($eInvoice['date']).'</b>',
					'paymentMethod' => $form->select('invoice[paymentMethod]', $cPaymentMethod->filter(fn($e) => $e['use']->value(\payment\Method::SELLING)), $defaultValues['paymentMethod'] ?? NULL, ['mandatory' => TRUE]),
					'clientName' => '<b>'.encode($eInvoice['customer']->getName()).'</b>',
					'amount' => '<b>'.\util\TextUi::money($eInvoice['priceIncludingVat']).'</b>',
					])]).'</div></div>';

			}

		}

		return new \Panel(
			id         : 'panel-bank-cashflow-allocate',
			title      : s("Créer des écritures pour une opération bancaire"),
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			header     : $title.$subtitle,
			footer     : $amountWarning.'<div class="create-operation-buttons">'.$addButton.$saveButton.'</div>',
		);

	}

	public static function addAllocate(\farm\Farm $eFarm, \journal\Operation $eOperation, \account\FinancialYear $eFinancialYear, Cashflow $eCashflow, int $index, array $assetData): string {

		$form = new \util\FormUi();
		$form->open('bank-cashflow-allocate');
		$defaultValues = [
			'date' => $eCashflow['date'],
			'type' => $eCashflow['type'],
			'description' => $eCashflow['memo'],
			'cashflow' => $eCashflow,
		];

		return \journal\OperationUi::getFieldsCreateGrid($eFarm, $form, $eOperation, $eFinancialYear, '['.$index.']', $defaultValues, [], $assetData, new \Collection());

	}

	public static function import(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$h = '';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Seul l'export au format <b>.ofx</b> est actuellement supporté. Ce format est disponible sur la plupart des sites bancaires.").'</p>';
			$h .= '<p>'.s("Si certains flux bancaires ont déjà été précédemment importés, ils seront ignorés.").'</p>';
		$h .= '</div>';

		$h .= '<div class="util-info">'.s("Si le compte bancaire n'existe pas encore, il sera automatiquement créé (et vous pourrez paramétrer son libellé dans Paramétrage > Les comptes bancaires).").'</div>';


		$h .= $form->openUrl(\company\CompanyUi::urlBank($eFarm).'/import:doImport', ['id' => 'cashflow-import', 'binary' => TRUE, 'method' => 'post']);
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

	public function getAttach(\farm\Farm $eFarm, Cashflow $eCashflow, \Collection $cOperation): \Panel {

		\Asset::js('bank', 'cashflow.js');
		$h = CashflowUi::getCashflowHeader($eCashflow);

		if($cOperation->empty() === TRUE) {

			$h .='<div class="util-warning">'.s("Aucune écriture comptable ne peut être actuellement rattachée à cette opération bancaire.").'</div>';

		} else {

			$form = new \util\FormUi();
			$h .= $form->openAjax(\company\CompanyUi::urlBank($eFarm).'/cashflow:doAttach', ['method' => 'post', 'id' => 'cashflow-doAttach']);

				$h .= $form->hidden('id', $eCashflow['id']);
				$h .= '<span class="hide" name="cashflow-amount">'.$eCashflow['amount'].'</span>';

				$h .= '<div class="stick-sm util-overflow-sm">';
					$h .= '<table class="tr-hover">';

						$h .= '<thead>';
							$h .= '<tr class="row-header">';
								$h .= '<th class="text-end">';
									$h .= s("Date");
								$h .= '</th>';
								$h .= '<th>';
									$h .= s("Description");
								$h .= '</th>';
								$h .= '<th>'.s("Tiers").'</th>';
								$h .= '<th>'.s("Moyen de paiement").'</th>';
								$h .= '<th class="text-end">'.s("Total").'</th>';
								$h .= '<th class="text-end">'.s("Débit (D)").'</th>';
								$h .= '<th class="text-end">'.s("Crédit (C)").'</th>';
								$h .= '<th class="text-center">'.s("Choisir").'</th>';
							$h .= '</tr>';
						$h .= '</thead>';

						$h .= '<tbody>';

						foreach($cOperation as $eOperation) {

							$eOperation->setQuickAttribute('farm', $eFarm['id']);
							$eOperation->setQuickAttribute('app', 'accounting');

							if($eOperation['links']->empty() === FALSE) {
								// Ajouter une ligne de résumé
								$h .= '<tr class="border-top">';
									$h .= '<td class="text-end">';
										$h .= \util\DateUi::numeric($eOperation['date']);
									$h .= '</td>';
									$h .= '<td>';
										$h .= encode($eOperation['description']);
									$h .= '</td>';

									$h .= '<td>';
									if($eOperation['thirdParty']->exists() === TRUE) {
										$h .= encode($eOperation['thirdParty']['name']);
									}
									$h .= '</td>';

									$h .= '<td></td>';

									$h .= '<td class="text-end">';
										$h .= $eOperation['difference'] < 1 ? '<b>' : '';
											$h .= \util\TextUi::money($eOperation['totalVATIncludedAmount']);
										$h .= $eOperation['difference'] < 1 ? '</b>' : '';
									$h .= '</td>';

									$h .= '<td class="text-end"></td>';

									$h .= '<td class="text-end">';
									$h .= '</td>';

									$h .= '<td class="text-center">';
										$h .= $form->checkbox('operation[]', $eOperation['id']);
										$h .= '<span class="hide" name="amount" data-operation="'.$eOperation['id'].'" data-type="'.$eOperation['type'].'">'.$eOperation['totalVATIncludedAmount'].'</span>';
									$h .= '</td>';
								$h .= '</tr>';
							}

							// Ligne principale
							$h .= CashflowUi::getOperationLineForAttachment($eOperation, $eOperation['links']->empty() === TRUE ? $form : NULL);

							// Lignes rattachées
							foreach($eOperation['links'] as $eOperationLinked) {
								$h .= CashflowUi::getOperationLineForAttachment($eOperationLinked, NULL);
							}

						}

						$h .= '<tr class="row-highlight row-bold">';
							$h .= '<td colspan="4" class="text-end">'.s("Total sélectionné :").'</td>';
							$h .= '<td class="text-end"><span data-field="totalAmount">'.\util\TextUi::money(0).'</span></td>';
							$h .= '<td class="text-end"></td>';
							$h .= '<td class="text-end"></td>';
							$h .= '<td class="text-end"></td>';
						$h .= '</tr>';

						$h .= '</tbody>';

					$h .= '</table>';
				$h .= '</div>';

				$h .= '<div id="cashflow-attach-difference-warning" class="util-warning-outline hide">';
				$h .= s("Attention, le montant de l'opération bancaire ne correspond pas au total des écritures sélectionnées. Vous pouvez quand même valider.");
				$h .= '</div>';

				$h .= '<div class="text-end">'.$form->submit(s("Rattacher"), ['class' => 'btn btn-secondary']).'</div>';

			$h .= $form->close();
		}

		return new \Panel(
			id: 'panel-cashflow-attach',
			title: s("Rattacher l'opération bancaire à une ou plusieurs écritures comptables"),
			body: $h
		);

	}

	protected static function getOperationLineForAttachment(\journal\Operation $eOperation, ?\util\FormUi $form): string {

		$h = '<tr class="'.($form === NULL ? 'row-light' : 'border-top').'">';
			$h .= '<td class="text-end">';
				if($form !== NULL) {
					$h .= \util\DateUi::numeric($eOperation['date']);
				}
			$h .= '</td>';
			$h .= '<td>';
			if($form !== NULL) {
				$h .= encode($eOperation['account']['class'].' '.$eOperation['account']['description']).' - '.encode($eOperation['description']);
			} else {
				$h .= '<span class="ml-1">'.\Asset::icon('arrow-return-right', ['class' => 'mr-1']).encode($eOperation['account']['class'].' '.$eOperation['account']['description']).'</span>';
			}
			$h .= '</td>';

			$h .= '<td>';
			if($eOperation['thirdParty']->exists() === TRUE and $form !== NULL) {
				$h .= encode($eOperation['thirdParty']['name']);
			}
			$h .= '</td>';

			$h .= '<td>';
				$h .= $eOperation->quick('paymentMethod', \payment\MethodUi::getName($eOperation['paymentMethod']) ?? '<i>'.s("Non défini").'</i>');
			$h .= '</td>';
			$h .= '<td class="text-end"></td>';

			$h .= '<td class="text-end">';
			$h .= match($eOperation['type']) {
				\journal\Operation::DEBIT => \util\TextUi::money($eOperation['amount']),
				default => '',
			};
			$h .= '</td>';

			$h .= '<td class="text-end">';
			$h .= match($eOperation['type']) {
				\journal\Operation::CREDIT => \util\TextUi::money($eOperation['amount']),
				default => '',
			};
			$h .= '</td>';
			$h .= '<td class="text-center">';
				$h .= $form !== NULL
					? $form->checkbox('operation[]', $eOperation['id'])
						.'<span class="hide" name="amount" data-operation="'.$eOperation['id'].'" data-type="'.$eOperation['type'].'">'.$eOperation['amount'].'</span>'
					: '';
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
			'account' => s("Compte"),
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
					CashflowElement::ALLOCATED => s("Attribuée"),
					CashflowElement::WAITING => s("Attente"),
				];
				$d->translation = [
					CashflowElement::ALLOCATED => ['singular' => s("Attribuée"), 'plural' => s("Attribuées")],
					CashflowElement::WAITING => ['singular' => s("En attente"), 'plural' => s("En attente")],
				];
				$d->shortValues = [
					CashflowElement::ALLOCATED => s("I"),
					CashflowElement::WAITING => s("A"),
				];
				break;
		}

		return $d;

	}
}

?>
