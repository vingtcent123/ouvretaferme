<?php
namespace journal;

class OperationUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::css('journal', 'journal.css');
	}

	public static function getDescriptionBank(string $type): string {
		return match($type) {
			'incoming-client' => s("Paiement vente"),
			'outgoing-client' => s("Remboursement vente"),
			'incoming-supplier' => s("Remboursement achat"),
			'outgoing-supplier' => s("Paiement achat"),
		};
	}

	public static function getAccountLabelFromAccountPrefix(string $accountPrefix): string {

		return str_pad($accountPrefix, 8, 0);

	}

	public function createDocumentCollection(\farm\Farm $eFarm, \Collection $cOperation): \Panel {

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateDocumentCollection',
			[
				'id' => 'journal-operation-document',
				'class' => 'panel-dialog',
			],
		);

		$h = '';

		$h .= '<div class="util-info">';
			$h .= s("La pièce comptable est l'identifiant de la pièce justificative de vos écritures. Cette identification vous permet de faire plus facilement le lien entre vos écritures comptables et le justificatif.");
		$h .= '</div>';

		$h .= '<h3>';
			$h .= s("Récapitulatif des opérations sélectionnées");
		$h .= '</h3>';

		$h .= new JournalUi()->list($eFarm, NULL, $cOperation, eFinancialYearSelected: $eFarm['eFinancialYear'], readonly: TRUE, columnsSelected: ['document']);

		$h .= '<h3>'.s("Nouvelle pièce comptable").'</h3>';

		$h .= $form->dynamicGroup(new Operation(), 'document');
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}

		$dialogClose = $form->close();
		$footer = $form->submit(s("Enregistrer"));
		return new \Panel(
			id: 'panel-journal-operations-document',
			title: s("Modifier la pièce comptable"),
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);
	}

	public function getDelete(\farm\Farm $eFarm, \Collection $cOperation, Operation $eOperation): \Panel {

		\Asset::css('bank', 'cashflow.css');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doDelete',
			[
				'id' => 'operation-delete','class' => 'panel-dialog',]
		);

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eOperation['id']);

		if($cOperation->notEmpty()) {

			if($cOperation->count() > 1) {
				$h .= '<div class="util-info">';
					$h .= s("Cette écriture comptable fait partie d'un groupe d'écritures. En la supprimant, vous supprimerez tout le groupe.");
				$h .= '</div>';
			}

			$h .= '<h3 class="mt-2">';
				$h .= p("Écriture comptable à supprimer", "Toutes les écritures comptables à supprimer", $cOperation->count());
			$h .= '</h3>';

			$h .= '<div class="bg-background">';
				$h .= new \journal\JournalUi()->list($eFarm, NULL, $cOperation, $eFarm['eFinancialYear'], readonly: TRUE);
			$h .= '</div>';

		} else {

			$h .= '<h3 class="mt-2">';
				$h .= s("Écriture comptable à supprimer");
			$h .= '</h3>';

			$h . $this->getSummary($eOperation);

		}

		$submit = s("Confirmer la suppression");
		$waiter = s("Suppression en cours...");
		$title = p("Suppression d'une écriture comptable", "Suppression d'écritures comptables", $cOperation->count());

		$h .= $form->submit(
			$submit,
			[
				'id' => 'submit-delete-operation',
				'class' => 'btn btn-primary',
				'data-waiter' => $waiter,
			],
		);

		return new \Panel(
			id         : 'panel-operation-delete',
			title      : $title,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
			body       : $h,
			header     : '<h1>'.p("Supprimer une écriture comptable", "Supprimer des écritures comptables", $cOperation->count()).'</h1>',
		);
	}

	public function formatOperationForUpdate(\bank\Cashflow $eCashflow, \Collection $cOperation, Operation $eOperationBase, string $for): \Collection {

		// Formattage des opérations (association de la TVA avec son écriture d'origine + ne pas mettre l'opération de banque)
		$cOperationFormatted = new \Collection();
		$linkedOperationIds = [];

		//Cas particulier des comptes en 409x : ne pas afficher l'écriture en 44581 et remettre l'écriture originale en HT
		$cOperationRegulVat = $cOperation->find(fn($e) => (
			// L'opération courante doit être une écriture en 44581
			\account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::VAT_DEPOSIT_CLASS) and
			$e['operation']->notEmpty() and
			// L'opération parente doit être une 409 ou une 419
			\account\AccountLabelLib::isDeposit($cOperation[$e['operation']['id']]['accountLabel'] ?? '')
		));

		if($cOperationRegulVat->notEmpty()) {
			foreach($cOperationRegulVat as $eOperationRegulVat) {
				$cOperation->offsetUnset($eOperationRegulVat['id']); // Ne pas afficher
				$cOperation[$eOperationRegulVat['operation']['id']]['amount'] -= $eOperationRegulVat['amount']; // Remettre le montant HT

			}
		}

		foreach($cOperation as $eOperation) {

			if(in_array($eOperation['id'], $linkedOperationIds)) {
				continue;
			}

			// L'opération de banque est automatiquement gérée
			if(
				$eCashflow->notEmpty() and
				abs($eOperation['amount']) === abs($eCashflow['amount']) and
				\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS)
			) {
				$linkedOperationIds[] = $eOperation['id'];
				continue;
			}

			// On regarde s'il y a une opération de TVA liée
			$cOperationVAT = $cOperation->find(fn($e) => $e['operation']->notEmpty() and $e['operation']['id'] === $eOperation['id']);
			if($cOperationVAT->notEmpty() and \account\AccountLabelLib::isFromClass($cOperationVAT->first()['accountLabel'], \account\AccountSetting::VAT_CLASS)) {
				$eOperationVAT = $cOperationVAT->first();
				$linkedOperationIds[] = $eOperationVAT['id'];
				if($for === 'copy') {
					unset($eOperationVAT['id']);
				}
				$eOperation['vatAmount'] = $eOperationVAT['amount'];
				$eOperation['vatOperation'] = $eOperationVAT;
			}

			if($for === 'copy') {
				unset($eOperation['id']);
				$eOperation['date'] = $eCashflow['date'];
				$eOperation['paymentDate'] = $eCashflow['date'];
			}
			$eOperation['cJournalCode'] = $eOperationBase['cJournalCode'];

			$cOperationFormatted->append($eOperation);

		}

		return $cOperationFormatted;
	}

	public function getUpdate(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cOperation, \Collection $cPaymentMethod, \bank\Cashflow $eCashflow, Operation $eOperationBase): \Panel {

		\Asset::css('journal', 'operation.css');
		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'amount.js');
		\Asset::js('account', 'thirdParty.js');

		if($eCashflow->notEmpty()) {
			\Asset::css('bank', 'cashflow.css');
			\Asset::js('bank', 'cashflow.js');
		}

		$cOperationFormatted = $this->formatOperationForUpdate($eCashflow, $cOperation, $eOperationBase, 'update');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperationBase['id'].'/doUpdate',
			array_merge([
				'id' => 'journal-operation-update',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog',
				'data-has-vat' => (int)$eFinancialYear['hasVat'],
			], $eCashflow->empty() ? [] : ['data-cashflow']),
		);

		$h = '';

		$h .= $form->hidden('id', $eOperationBase['id']);
		$h .= $form->hidden('hash', $cOperation->first()['hash']);
		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);
		$h .= $form->hidden('paymentMethod', $cOperation->first()['paymentMethod']['id'] ?? '');

		if($eCashflow->notEmpty()) {

			$defaultValues = [
				'paymentDate' => $eCashflow['date'],
				'paymentMethod' => \bank\CashflowUi::extractPaymentTypeFromCashflowDescription($eCashflow->getMemo(), $cPaymentMethod->filter(fn($e) => $e['use']->value(\payment\Method::ACCOUNTING))),
			];

			$h .= new \bank\CashflowUi()->getAllocateGeneralPayment($eFinancialYear,  $cPaymentMethod, $defaultValues, $form, 'update');

		}

		$h .= self::getUpdateGrid(
			eFinancialYear: $eFinancialYear,
			form: $form,
			cPaymentMethod: $cPaymentMethod,
			cOperation: $cOperationFormatted,
			eCashflow: $eCashflow,
			eOperationRequested: $eOperationBase,
			for: 'update',
		);

		$attributes = [
			'post-index' => $cOperationFormatted->count(),
			'post-financial-year' => $eFinancialYear['id'],
		];
		if($eCashflow->notEmpty()) {
			$url = \company\CompanyUi::urlBank($eFarm).'/cashflow:addAllocate';
			$attributes['post-id'] = $eCashflow['id'];
			$attributes['post-third-party'] = $cOperationFormatted->first()['thirdParty']['id'] ?? '';
		} else {
			$url = \company\CompanyUi::urlJournal($eFarm).'/operation:addOperation';
		}
		$addButton = '<a id="add-operation" data-ajax="'.$url.'" '.attrs($attributes).' class="btn btn-outline-secondary">';
			$addButton .= \Asset::icon('plus-circle').'&nbsp;'.s("Ajouter une autre écriture");
		$addButton .= '</a>';
		$saveButton = $form->submit(
			s("Modifier"),
			[
				'id' => 'submit-save-operation',
				'data-text-singular' => s("Modifier l'écriture"),
				'data-text-plural' => s(("Modifier les écritures")),
				'data-confirm-text-singular' => s("Il y a une incohérence de valeur de TVA, voulez-vous quand même enregistrer ?"),
				'data-confirm-text-plural' => s("Il y a plusieurs incohérences de valeur de TVA, voulez-vous quand même enregistrer ?"),
			] + ($eCashflow->empty() ? [] : ['data-confirm-text' => s("Il y a une incohérence entre les écritures saisies et le montant de l'opération bancaire. Voulez-vous vraiment les enregistrer tel quel ?")]),
		);

		$dialogClose = $form->close();

		$footer = '<div class="operation-create-button-add" onrender="Operation.showOrHideDeleteOperation();">'.$saveButton.$addButton.'</div>';

		if($eCashflow->empty()) {
			$title = $cOperationFormatted->count() > 1 ? s("Modifier les écritures") : s("Modifier une écriture");
		} else {
			$title = new \bank\CashflowUi()->getAllocateTitle($eCashflow);
		}

		return new \Panel(
			id: 'panel-journal-operation-update',
			title: $title,
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	public function getView(\farm\Farm $eFarm, Operation $eOperation): \Panel {

		$args = $_GET;
		foreach(['id', 'operation', 'origin', 'farm', 'app'] as $filteredField) {
			unset($args[$filteredField]);
		}
		\Asset::css('journal', 'operation.css');

		$h = $this->getSummary($eOperation);
		$h .= $this->getUtil($eFarm, $eOperation);
		$h .= $this->getLinked($eFarm, $eOperation);

		return new \Panel(
			id: 'panel-operation-view',
			title: s("Détail d'écriture"),
			body: $h,
			close: 'passthrough',
			url: \company\CompanyUi::urlJournal($eFarm).'/livre-journal?operation='.$eOperation['id'].'&'.http_build_query($args),
		);
	}
	public function getSummary(Operation $eOperation): string {

		$h = '<table class="tr-even">';

			$h .= '<tr>';
				$h .= '<th>'.s("Date d'écriture").'</th>';
				$h .= '<td>'.\util\DateUi::numeric($eOperation['date']).' - '.s("Exercice {value}", $eOperation['financialYear']->getLabel()).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th>'.s("Date de paiement").'</th>';
				$h .= '<td>';
					$h .= $eOperation['paymentDate'] !== NULL ? \util\DateUi::numeric($eOperation['paymentDate']) : '<i>'.s("Non indiqué").'</i>';
				$h .= '</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th>'.s("Numéro de compte").'</th>';
				$h .= '<td>'.encode($eOperation['accountLabel']).' - '.encode($eOperation['account']['description']).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th>'.s("Tiers").'</th>';
				$h .= '<td>'.encode($eOperation['thirdParty']['name'] ?? '').'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th>'.s("Type").'</th>';
				$h .= '<td>'.s("{type} de {amount}", ['type' => self::p('type')->values[$eOperation['type']], 'amount' => \util\TextUi::money($eOperation['amount'])]).'</td>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<th>'.s("Créée").'</th>';
				$h .= '<td>'.s("le {date} par {name}", ['name' => ($eOperation['createdBy']->notEmpty() ? $eOperation['createdBy']->getName() : '-'), 'date' => \util\DateUi::numeric($eOperation['createdAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'</td>';
			$h .= '</tr>';

		$h .= '</table>';

		return $h;

	}

	public function getUtil(\farm\Farm $eFarm, Operation $eOperation): string {

		if($eOperation->canUpdate()) {
			$eOperation->setQuickAttribute('farm', $eFarm['id']);
		}
		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Informations générales").'</h3>';
		$h .= '</div>';

		if(\asset\AssetLib::isAsset($eOperation['accountLabel']) and $eOperation['asset']->empty()) {
			$h .= '<div class="mt-1 mb-1">';
				$h .= '<span class="color-warning">'.\Asset::icon('exclamation-triangle').' '.s("Attention, l'immobilisation correspondante n'a pas encore été créée ou rattachée.").'</span>';
				$h .= '<br />';
				$h .= '<a class="btn btn-outline-warning" href="'.\company\CompanyUi::urlFarm($eFarm).'/asset/:create?ids[]='.$eOperation['id'].'">'.s("Créer").'</a>';
				$h .= ' <a class="btn btn-outline-warning" href="'.\company\CompanyUi::urlFarm($eFarm).'/asset/:attach?ids[]='.$eOperation['id'].'">'.s("Rattacher").'</a>';
			$h .= '</div>';
		}

		$h .= '<div class="operation-view-label">';
			$h .= s("Montant");
		$h .= '</div>';
		$h .= '<div class="operation-view-value">';
			$amount = \util\TextUi::money($eOperation['amount']);
			$h .= $amount;
		$h .= '</div>';

		$h .= '<div class="operation-view-label">';
			$h .= s("Libellé");
		$h .= '</div>';
		$h .= '<div class="operation-view-value">';
			$description = $eOperation['description'] ? $eOperation['description'] : '<i>'.s("Non indiqué").'</i>';
			$h .= $description;
		$h .= '</div>';

		$h .= '<div class="operation-view-label">';
			$h .= s("Pièce comptable");
		$h .= '</div>';
		$h .= '<div class="operation-view-value">';
			$document = $eOperation['document'] ? $eOperation['document'] : '<i>'.s("Non indiqué").'</i>';
			$h .= $document;
		$h .= '</div>';

		$h .= '<div class="operation-view-label">';
			$h .= s("Journal");
		$h .= '</div>';
		$h .= '<div class="operation-view-value">';
			if($eOperation['journalCode']->notEmpty()) {
				$journalCode = '<div>';
				$journalCode .= ' ';

				$journalCode .= '<span style=" border-radius: var(--radius); padding: 0.5rem 0.75rem; '.new JournalCodeUi()->getInlineStyle($eOperation['journalCode']).'">'.encode($eOperation['journalCode']['name']);
			} else {
				$journalCode = '<i>'.s("Non indiqué").'</i>';
			}
			$journalCode .= '</div>';
			$h .= $journalCode;
		$h .= '</div>';

		$h .= '<div class="operation-view-label">';
			$h .= s("Moyen de paiement");
		$h .= '</div>';
		$h .= '<div class="operation-view-value">';
			$paymentMethod = ($eOperation['paymentMethod']['name'] ?? NULL) !== NULL ? \payment\MethodUi::getName($eOperation['paymentMethod']) : '<i>'.s("Non indiqué").'</i>';
			$h .= $paymentMethod;
		$h .= '</div>';

		$isLinkedToAsset = $eOperation['cOperationHash']->getColumnCollection('asset')->find(fn($e) => $e->notEmpty())->notEmpty();
		if($eOperation->acceptUpdate() and $eOperation->canUpdate() and $eOperation->acceptWrite() and $isLinkedToAsset === FALSE) {
			$h .= '<div class="text-center"><a class="btn btn-outline-secondary" href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'/update">'.s("Modifier").'</a></div>';
		}

		return $h;
	}

	public function getLinked(\farm\Farm $eFarm, Operation $eOperation): string {

		if(
			($eOperation['cOperationHash']->empty() or $eOperation['cOperationHash']->count() < 2)
			and $eOperation['cOperationCashflow']->empty()
		) {
			return '';
		}

		$totalDebit = 0;
		$totalCredit = 0;

		$h = '';

		if($eOperation['cOperationCashflow']->notEmpty()) {

			$h .= '<div class="util-title mt-2">';
				$h .= '<h3>'.p("Opération bancaire liée", "Opérations bancaires liées", $eOperation['cOperationCashflow']->count()).'</h3>';
			$h .= '</div>';

			foreach($eOperation['cOperationCashflow'] as $eOperationCashflow) {

				$eCashflow = $eOperationCashflow['cashflow'];

				$h .= '<table class="tr-even">';
					$h .= '<tr>';
						$h .= '<th colspan="2" class="text-center">';
							$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/operations?id='.$eCashflow['id'].'&bankAccount='.$eCashflow['account']['id'].'" target="_blank">';
								$h .= s("Opération #{id} du {date}", [
									'id' => encode($eCashflow['id']),
									'icon' => \Asset::icon('box-arrow-up-right').'</a>',
									'date' => \util\DateUi::numeric($eCashflow['date']),
								]);
								$h .= ' '.\Asset::icon('box-arrow-up-right').'</a>';
							$h .= '</a>';
						$h .= '</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<div class="operation-view-label">';
								$h .= s("Montant");
							$h .= '</div>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\TextUi::money($eCashflow['amount']);
						$h .= '</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<div class="operation-view-label">';
								$h .= s("Libellé");
							$h .= '</div>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= encode($eCashflow->getMemo());
						$h .= '</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>';
							$h .= '<div class="operation-view-label">';
							$h .= s("Import");
							$h .= '</div>';
						$h .= '</td>';
						$h .= '<td>';
							$h .= s("le {date}<br />par {user}", [
								'date' => \util\DateUi::numeric($eCashflow['createdAt'], \util\DateUi::DATE_HOUR_MINUTE),
								'user' => $eCashflow['createdBy']->getName(),
							]);
						$h .= '</td>';
					$h .= '</tr>';
				$h .= '</table>';

			}

		}

		if($eOperation['cOperationHash']->count() > 1) {

			$count = (clone $eOperation['cOperationHash'])->find(fn($e) => $e['id'] !== $eOperation['id'])->count();

			$h .= '<div class="util-title mt-2">';
				$h .= '<h3>'.p("Écriture comptable liée","Écritures comptables liées", $count).'</h3>';
				if($count > 1) {
					$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?hash='.$eOperation['hash'].'">'.s("Tout voir dans le journal").'</a>';
				}
			$h .= '</div>';

			foreach($eOperation['cOperationHash'] as $eOperationHash) {

				if($eOperationHash['type'] === Operation::CREDIT) {
					$totalCredit += $eOperationHash['amount'];
				} else {
					$totalDebit += $eOperationHash['amount'];
				}

				if($eOperationHash['id'] === $eOperation['id']) {
					continue;
				}

				$h .= $this->getLinkedOperationDetails($eFarm, $eOperationHash);

			}

		}

		if($totalCredit !== 0 or $totalDebit !== 0) {

			$h .= '<div class="util-title mt-2">';
				$h .= '<h3>'.s("Contrôle d'équilibre").'</h3>';
			$h .= '</div>';

			$h .= '<table class="tr-even">';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Total débit").'</th>';
					$h .= '<th class="text-center">'.s("Total crédit").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td class="text-center">'.\util\TextUi::money($totalDebit).'</td>';
					$h .= '<td class="text-center">'.\util\TextUi::money($totalCredit).'</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td colspan="2" class="text-center">';
						if(round($totalCredit, 2) !== round($totalDebit, 2)) {
							$difference = round(abs($totalCredit - $totalDebit), 2);
							$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Vos écritures ne sont pas équilibrées<br />(différence de {value})", \util\TextUi::money($difference)).'</span>';
						} else {
							$h .= '<span class="color-success">'.\Asset::icon('check').' '.s("Vos écritures sont équilibrées").'</span>';
						}
					$h .= '</td>';
				$h .= '</tr>';
			$h .= '</table>';

		}

		return $h;

	}

	protected function getLinkedOperationDetails(\farm\Farm $eFarm, Operation $eOperation): string {

		$h = '<table class="tr-even">';
			$h .= '<tr>';
				$h .= '<th colspan="2">';
					$h .= '<div class="operation-linked-title">';
						$h .= '<div>';
							$h .= ($eOperation['journalCode'] ? new JournalCodeUi()->getColoredButton($eOperation['journalCode']): '');
							$h .= ' ';
								$h .= match($eOperation['type']) {
										Operation::DEBIT => s("Débit du {value}", \util\DateUi::numeric($eOperation['date'])),
										Operation::CREDIT => s("Crédit du {value}", \util\DateUi::numeric($eOperation['date'])),
								};
						$h .= '</div>';
						$h .= '<div>';
							$h .= ' <a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['id'].'">';
								$h .= s("détails");
							$h .= '</a>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</th>';
			$h .= '</tr>';

			$h .= '<tr>';
				$h .= '<td>';
					$h .= '<div class="operation-view-label">';
						$h .= s("Montant");
					$h .= '</div>';
				$h .= '</td>';
				$h .= '<td>';
					$h .= \util\TextUi::money($eOperation['amount']);
				$h .= '</td>';
			$h .= '</tr>';
			$h .= '<tr>';
				$h .= '<td>';
					$h .= '<div class="operation-view-label">';
						$h .= s("Numéro de compte");
					$h .= '</div>';
				$h .= '</td>';
				$h .= '<td>';
					$h .= encode($eOperation['accountLabel']);
					$h .= ' <small>('.encode($eOperation['account']['description']).')</small>';
				$h .= '</td>';
			$h .= '</tr>';
			$h .= '<tr>';
				$h .= '<td>';
					$h .= '<div class="operation-view-label">';
						$h .= s("Libellé");
					$h .= '</div>';
				$h .= '</td>';
				$h .= '<td>';
					$h .= encode($eOperation['description']);
				$h .= '</td>';
			$h .= '</tr>';
		$h .= '</table>';

		return $h;
	}

	public function view(Operation $eOperation): \Panel {

		$h = '';

		return new \Panel(
			id: 'panel-journal-view',
			title: s("Détail de l'écriture comptable"),
			dialogOpen: '',
			dialogClose: '',
			body: $h,
			footer: '',
		);

	}

	public function create(\farm\Farm $eFarm, Operation $eOperation, \account\FinancialYear $eFinancialYear, \Collection $cPaymentMethod): \Panel {

		\Asset::css('journal', 'operation.css');
		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'amount.js');
		\Asset::js('account', 'thirdParty.js');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doCreate',
			[
				'id' => 'journal-operation-create',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog',
				'data-has-vat' => (int)$eFinancialYear['hasVat'],
			],
		);

		$h = '';

		$h .= '<div style="display: flex;">';

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('financialYear', $eFinancialYear['id']);

			$index = 0;
			$defaultValues = $eOperation->getArrayCopy();

			$h .= self::getCreateGrid($eFarm, $eOperation, new \bank\Cashflow(), $eFinancialYear, $index, $form, $defaultValues, $cPaymentMethod);

		$h .= '</div>';

		$addButton = '<a id="add-operation" data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/operation:addOperation" post-index="'.($index + 1).'" post-financial-year="'.$eFinancialYear['id'].'" class="btn btn-outline-secondary">';
			$addButton .= \Asset::icon('plus-circle').'&nbsp;'.s("Ajouter une autre écriture");
		$addButton .= '</a>';

		$saveButton = $form->submit(
			s("Enregistrer l'écriture"),
			[
				'id' => 'submit-save-operation',
				'data-text-singular' => s("Enregistrer l'écriture"),
				'data-text-plural' => s(("Enregistrer les écritures")),
				'data-confirm-text-singular' => s("Il y a une incohérence de valeur de TVA, voulez-vous quand même enregistrer ?"),
				'data-confirm-text-plural' => s("Il y a plusieurs incohérences de valeur de TVA, voulez-vous quand même enregistrer ?"),
			],
		);

		$dialogClose = $form->close();

		$footer = '<div class="operation-create-button-add">'.$saveButton.$addButton.'</div>';

		$defaultTitle = s("Enregistrer une écriture");
		return new \Panel(
			id: 'panel-journal-operation-create',
			title: '<div id="panel-journal-operation-create-title" data-text-singular="'.$defaultTitle.'" data-text-plural="'.s("Ajouter plusieurs écritures").'">'.$defaultTitle.'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	private static function getCreateHeader(\account\FinancialYear $eFinancialYear, \bank\Cashflow $eCashflow): string {

		$h = '<div class="operation-create operation-create-headers">';

			$h .= '<h4>&nbsp;</h4>';
			$h .= '<div class="operation-create-header">'.self::p('date')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('document')->label.'</div>';
			$h .= '<div class="operation-create-header">'.self::p('thirdParty')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('account')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('journalCode')->label.'</div>';
			$h .= '<div class="operation-create-header">'.self::p('accountLabel')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header" data-wrapper="asset-label">'.self::p('asset')->label.'</div>';
			$h .= '<div class="operation-create-header">'.self::p('description')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('type')->label.' '.\util\FormUi::asterisk().'</div>';

			if($eCashflow->empty()) {

				$h .= '<div class="operation-create-header">'.self::p('paymentDate')->label.'</div>';
				$h .= '<div class="operation-create-header">'.self::p('paymentMethod')->label.'</div>';

			}
			if($eFinancialYear['hasVat']) {
				$h .= '<div class="operation-create-header amount-header"></div>';
			}
			$h .= '<div class="operation-create-header">'.($eFinancialYear['hasVat'] ? self::p('amount')->label : s("Montant")).' '.\util\FormUi::asterisk().'</div>';

			if($eFinancialYear['hasVat']) {
				$h .= '<div class="operation-create-header">'.s("TVA").' '.\util\FormUi::asterisk().'</div>';

				$h .= '<div class="operation-create-header">';
					$h .= s("Montant TTC");
					if($eCashflow->notEmpty()) {
						$h .= '<h5 class="util-badge bg-primary ml-1">'.\util\TextUi::money(abs($eCashflow['amount'])).'</h5>';
					}
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	private static function getAmountButtonIcons(string $type, int $index): string {

		$h = '<div class="merchant-write hide">'.\Asset::icon('pencil').'</div>';
		$h .= '<div class="merchant-lock hide">'.\Asset::icon('lock-fill').'</div>';
		$h .= '<div class="merchant-erase ">';
			$h .= '<a '.attr('onclick', "Operation.resetAmount('".$type."', ".$index.")").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
		$h .= '</div>';

		return $h;
	}

	public static function getFieldsCreateGrid(
		\util\FormUi $form,
		Operation $eOperation,
		\bank\Cashflow $eCashflow,
		\account\FinancialYear $eFinancialYear,
		?string $suffix,
		array $defaultValues,
		array $disabled,
		\Collection $cPaymentMethod,
	): string {

		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'amount.js');

		$index = ($suffix !== NULL) ? mb_substr($suffix, 1, mb_strlen($suffix) - 2) : NULL;
		$isFromCashflow = $eCashflow->notEmpty();

		$h = '<div class="operation-create'.(($eOperation['isRequested'] ?? FALSE) ? ' operation-update-selected' : '').'" data-index="'.$index.'">';

			if(($eOperation['id'] ?? NULL) !== NULL) {
				$h .= $form->hidden('id['.$index.']', $eOperation['id']);
			}
			if(($eOperation['vatOperation']['id'] ?? NULL) !== NULL) {
				$h .= $form->hidden('vatOperation['.$index.']', $eOperation['vatOperation']['id']);
			}

			$h .= '<div class="operation-create-title">';
				$h .= '<h3>'.s("Écriture #{number}", ['number' => $index + 1]).'</h3>';

					$h .= '<div class="operation-create-actions">';
					$h .= '<div class="hide" data-index="'.$index.'" data-operation-delete>';
						$h .= '<a onclick="Operation.deleteOperation(this)" class="btn btn-outline-primary">'.\Asset::icon('trash').'</a>';
					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div data-wrapper="date'.$suffix.'">';
				$h .= $form->date('date'.$suffix, $defaultValues['date'] ?? '', [
						'min' => $eFinancialYear['startDate'],
						'max' => $eFinancialYear['endDate'],
						'data-date' => $form->getId(),
						'data-index' => $index,
						'data-accounting-type' => $eFinancialYear['accountingType'],
					]);
			$h .='</div>';

			$h .= '<div data-wrapper="document'.$suffix.'">';
				$h .=  $form->dynamicField($eOperation, 'document'.$suffix, function($d) use($index) {
					$d->attributes['data-index'] = $index;
					$d->attributes['data-field'] = 'document';
				});
			$h .='</div>';

			$h .= '<div data-wrapper="thirdParty'.$suffix.'">';

			$h .= $form->dynamicField($eOperation, 'thirdParty'.$suffix, function($d) use($form, $index, $disabled, $suffix) {
				$d->autocompleteDispatch = '[data-third-party="'.$form->getId().'"][data-index="'.$index.'"]';
				$d->attributes['data-index'] = $index;
				if(in_array('thirdParty', $disabled) === TRUE) {
					$d->attributes['disabled'] = TRUE;
				}
				$d->attributes['data-third-party'] = $form->getId();
				$d->default = fn($e, $property) => get('thirdParty');
			});

			$h .='</div>';

			$h .= '<div data-wrapper="account'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'account'.$suffix, function($d) use($form, $index, $disabled, $suffix) {
					$d->autocompleteDispatch = '[data-account="'.$form->getId().'"][data-index="'.$index.'"]';
					$d->attributes['data-wrapper'] = 'account'.$suffix;
					$d->attributes['data-index'] = $index;
					if(in_array('account', $disabled) === TRUE) {
						$d->attributes['disabled'] = TRUE;
					}
					$d->attributes['data-account'] = $form->getId();
					$d->label .=  ' '.\util\FormUi::asterisk();
				});
			$h .='</div>';

			$h .= '<div data-wrapper="journalCode'.$suffix.'" class="company_form_group-with-tip">';
				$h .=  $form->dynamicField($eOperation, 'journalCode'.$suffix, function($d) use($eOperation, $index) {
					$d->attributes['data-index'] = $index;
					$d->attributes['data-field'] = 'journalCode';
					$d->default = fn() => isset($eOperation['journalCode']) === FALSE ? GET('journalCode') : $eOperation['journalCode'];
				});
				$h .= '<div data-journal-code="journal-code-info" class="hide" data-index="'.$index.'" data-journal-suggested="" onclick=Operation.applyJournal('.$index.');>';
					$h .= '<a class="btn btn-outline-warning operation-hint" data-dropdown="bottom" data-dropdown-hover="true">';
						$h .= \Asset::icon('exclamation-triangle');
					$h .= '</a>';
					$h .= '<div class="dropdown-list bg-primary dropdown-list-bottom">';
						$h .= '<span class="dropdown-item">';
						$h .= s("Ce numéro de compte est normalement configuré pour être dans le journal \"{value}\".", ['value' => '<span data-index="'.$index.'" data-journalCode="journal-name"></span>']);
						$h .= '</span>';
					$h .= '</div>';
				$h .= '</div>';
			$h .='</div>';

			$h .= '<div data-wrapper="accountLabel'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'accountLabel'.$suffix, function($d) use($form, $index, $suffix) {
					$d->autocompleteDispatch = '[data-account-label="'.$form->getId().'"][data-index="'.$index.'"]';
					$d->attributes['data-wrapper'] = 'accountLabel'.$suffix;
					$d->attributes['data-index'] = $index;
					$d->attributes['data-account-label'] = $form->getId();
					$d->label .=  ' '.\util\FormUi::asterisk();
				});
			$h .='</div>';

			$h .= '<div data-wrapper="asset'.$suffix.'" >';
				$h .= '<div data-asset-container '.(\asset\AssetLib::isAsset($eOperation['accountLabel'] ?? '') ? '' : ' class="hide"').'>';
					$h .= $form->dynamicField($eOperation, 'asset'.$suffix, function($d) use($eOperation, $form, $index, $suffix) {
						$d->autocompleteDispatch = '[data-asset="'.$form->getId().'"][data-index="'.$index.'"]';
						$d->attributes['data-wrapper'] = 'asset'.$suffix;
						$d->attributes['data-index'] = $index;
						$d->attributes['data-asset'] = $form->getId();
						$d->label .=  ' '.\util\FormUi::asterisk();
						if($eOperation->exists() === FALSE) {
							$d->after = '<div data-account="asset-create" class="form-info" data-index="'.$index.'">'.
							s("Si l'immobilisation n'existe pas encore, vous pourrez la créer juste après avoir enregistré cette écriture.").
							'</div>';
						}
					});
				$h .='</div>';
			$h .='</div>';

			$h .= '<div data-wrapper="description'.$suffix.'">';
				if($eOperation->exists() === FALSE) {
					$eOperation['description'] = $defaultValues['description'] ?? '';
				}
				$h .= $form->dynamicField($eOperation, 'description'.$suffix, function($d) use($defaultValues, $form, $index, $suffix) {
					$d->attributes['onfocus'] = 'this.select()';
					$d->attributes['data-description'] = $form->getId();
				});
			$h .='</div>';

			$h .= '<div data-wrapper="type'.$suffix.'">';
				$h .= $form->radios('type'.$suffix, self::p('type')->values, $defaultValues['type'] ?? '', [
						'data-index' => $index,
						'columns' => 2,
						'data-field' => 'type',
						'mandatory' => TRUE,
					]);
			$h .= '</div>';

			if($eCashflow->empty()) {

				$h .= '<div data-wrapper="paymentDate'.$suffix.'">';
					$h .= $form->date('paymentDate'.$suffix, $defaultValues['paymentDate'] ?? '', ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
				$h .= '</div>';

				$h .= '<div data-wrapper="paymentMethod'.$suffix.'">';
					$h .= $form->select(
						'paymentMethod'.$suffix,
						$cPaymentMethod,
						$defaultValues['paymentMethod'] ?? '',
					);
				$h .= '</div>';

			}
			if($eFinancialYear['hasVat']) {

				$h .= '<div data-wrapper="amounts" class="operation-create-title">';

					$h .= '<h4 style="margin: auto 0;">'.s("Montants").'</h4>';

					$more = s("Seule la cohérence de l'ensemble des écritures (HT + TVA) est vérifiée par rapport à l'opération bancaire.");

					$h .= '<div data-index="'.$index.'" class="flex-align-center">';

						$h .= '<a class="btn btn-sm btn-outline-primary hide" data-index="'.$index.'" data-check-amount="0" onclick="Operation.toggleCorrect('.$index.')" title="'.s("Les montants ne sont pas vérifiés. {value}", $more).'">';
							$h .= \Asset::icon('x-lg');
							$h .= '<span class="operation-amount-check-legend"> '.s("Mode manuel").'</span>';
						$h .= '</a>';

						$h .= '<a class="btn btn-sm btn-outline-primary" data-index="'.$index.'" data-check-amount="1" onclick="Operation.toggleCorrect('.$index.')" title="'.s("Les montants sont ajustés dès que possible").'">';
							$h .= '<span data-check-amount-icon="ok">'.\Asset::icon('check-lg').'</span>';
							$h .= '<span data-check-amount-icon="ko" class="hide">'.\Asset::icon('exclamation-triangle').'</span>';
							$h .= '<span class="operation-amount-check-legend" data-index="'.$index.'" data-check-amount-legend-ok="'.s("Mode automatique").'" data-check-amount-legend-ko="'.s("Incohérence détectée ").'">';
								$h .= s("Mode automatique");
							$h .= '</span>';
						$h .= '</a>';

						if($isFromCashflow === TRUE) {
							$h .= '<a data-index="'.$index.'" data-magic onclick="Cashflow.recalculate('.$index.')" class="btn btn-sm btn-outline-primary" title="'.s("Réinitialiser par rapport aux autres écritures").'">'.\Asset::icon('magic').'</a>';
						}
					$h .= '</div>';
				$h .='</div>';

			}


			$h .= '<div data-wrapper="amount'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'amount'.$suffix, function($d) use($defaultValues, $index) {
					$d->default = $defaultValues['amount'] ?? '';
					$d->attributes['min'] = 0;
					$d->attributes['step'] = 0.01;
					$d->attributes['data-field'] = 'amount';
					$d->attributes['data-index'] = $index;
					$d->prepend = OperationUi::getAmountButtonIcons('amount', $index);
				});
			$h .='</div>';

			if($eFinancialYear['hasVat']) {

				if(($eOperation['vatAmount'] ?? NULL) !== NULL) {

					$vatAmountDefault = $eOperation['vatAmount'];
					$vatRateDefault = $eOperation['vatRate'];

				} else {

					$vatRateDefault = 0;

					if($eOperation->exists() === TRUE) { // Cas d'un update

						$vatRateDefault = $eOperation['vatRate'];

					} else if($eOperation['account']->exists() === TRUE) { // Tous les autres cas

						if($eOperation['account']['vatRate'] !== NULL) {

							$vatRateDefault = $eOperation['account']['vatRate'];

						} else if($eOperation['account']['vatAccount']->exists() === TRUE) {

							$vatRateDefault = $eOperation['account']['vatAccount']['vatRate'];

						}
					}
					$vatAmountDefault = $vatRateDefault !== 0 ? round(($defaultValues['amount'] ?? 0) * $vatRateDefault / 100,2) : 0;

				}

				$eOperation['vatRate'.$suffix] = '';

				$h .= '<div data-wrapper="vatRate'.$suffix.'" class="company_form_group-with-tip">';
					$h .= $form->inputGroup(
						$form->addon(OperationUi::getAmountButtonIcons('vatValue', $index)).
						$form->calculation(
							'vatValue'.$suffix,
							$vatAmountDefault ?? '',
							['data-index' => $index, 'data-field' => 'vatValue']
						).
						$form->addon(s("€"))
					);
					$h .= '<div data-vat-value-warning class="hide" data-index="'.$index.'">';
						$h .= '<a class="btn btn-warning" data-dropdown="bottom" data-dropdown-hover="true" data-vat-value-link data-index="'.$index.'" onclick="Operation.setVatValue('.$index.');">';
							$h .= \Asset::icon('exclamation-triangle');
						$h .= '</a>';
						$h .= '<div class="dropdown-list bg-primary dropdown-list-bottom">';
							$h .= '<span class="dropdown-item">';
								$h .= s(
									"La valeur indiquée de la TVA, {vatValue}, ne correspond pas au calcul (montant HT * taux de TVA) qui est {vatValueCalculated}.",
									[
										'vatValue' => '<b><span data-vat-value-vat-warning-value data-index="'.$index.'"></span></b>',
										'vatValueCalculated' => '<b><span data-vat-value-vat-warning-calculated-value data-index="'.$index.'"></span></b>',
									]
								);
							$h .= '</span>';
						$h .= '</div>';
					$h .= '</div>';
					$h .= $form->inputGroup(
					$form->addon(s('Taux')).
						$form->number(
							'vatRate'.$suffix,
							$vatRateDefault,
							['data-index' => $index, 'data-field' => 'vatRate', 'data-vat-rate' => $form->getId(), 'min' => 0, 'max' => 20, 'step' => 0.1, 'tabindex' => -1],
						).
						$form->addon(s("%"))
					);
				$h .= '</div>';

			$h .= '<div data-wrapper="amountIncludingVAT'.$suffix.'" class="company_form_group-with-tip">';
				$h .= $form->inputGroup($form->addon(self::getAmountButtonIcons('amountIncludingVAT', $index))
					.$form->calculation(
						'amountIncludingVAT'.$suffix,
						$defaultValues['amountIncludingVAT'] ?? '',
						[
							'min' => 0, 'step' => 0.01,
							'data-field' => 'amountIncludingVAT',
							'data-index' => $index,
						]
					)
					.$form->addon('€ '));
					$h .= '<div data-amount-including-vat-warning class="hide" data-index="'.$index.'">';
						$h .= '<a class="btn btn-warning operation-hint" data-dropdown="bottom" data-dropdown-hover="true" data-vat-rate-link data-index="'.$index.'" onclick="Operation.setAmountIncludingVat('.$index.');">';
							$h .= \Asset::icon('exclamation-triangle');
						$h .= '</a>';
						$h .= '<div class="dropdown-list bg-primary dropdown-list-bottom">';
							$h .= '<span class="dropdown-item">';
								$h .= s(
									"Le montant de <b>{amountIncludingVat} TTC</b> n'est pas cohérent avec le calcul (HT + TVA) qui est de <b>{amountIncludingVatCalculated}</b>",
									[
										'amountIncludingVat' => '<span data-amount-including-vat-warning-value data-index="'.$index.'"></span>',
										'amountIncludingVatCalculated' => '<span data-amount-including-vat-warning-calculated-value data-index="'.$index.'"></span>',
									]
								);
							$h .= '</span>';
						$h .= '</div>';
					$h .= '</div>';
			$h .='</div>';
			}

		$h .= '</div>';

		return $h;

	}

	private static function getCreateValidate(bool $hasVat, bool $isFromCashflow): string {

		$h = '<div class="operation-create operation-create-validation">';

			$h .= '<h4></h4>';
			$h .= '<div></div>';
			$h .= '<div></div>';
			$h .= '<div></div>';
			$h .= '<div></div>';
			$h .= '<div></div>';
			if($hasVat) {
				$h .= '<div></div>';
			}
			if($isFromCashflow === FALSE) {
				$h .= '<div></div>';
				$h .= '<div></div>';
			}
			if($isFromCashflow) {
				$h .= '<div class="cashflow-warning" style="display: flex; align-items: end; ">';
					$h .= '<div style="width: 100%;">';
						$h .= '<span id="cashflow-allocate-difference-warning" class="warning hide">';
							$h .= '<span data-direction="missing" class="hide">';
								$h .= s("⚠️ Manque <span></span>", ['span' => '<span cashflow-allocate-difference-value>']);
							$h .= '</span>';
							$h .= '<span data-direction="tooMuch" class="hide">';
								$h .= '⚠️ &nbsp;<span cashflow-allocate-difference-value></span>';
								$h .= '<br />'.s("en trop").'';
							$h .= '</span>';
						$h .= '</span>';
					$h .= '</div>';
				$h .= '</div>';
			} else {
				$h .= '<div class="balance-information" style="display: flex; align-items: end; ">';
					$h .= '<div style="width: 100%;">';
						$h .= '<span id="balance-information-warning" class="warning hide">';
							$h .= s("⚠️ Écritures déséquilibrées");
						$h .= '</span>';
					$h .= '</div>';
				$h .= '</div>';
			}
			$h .= '<div class="cashflow-create-operation-validate" data-field="amount"><div><span>=</span><span data-type="value"></span></div></div>';
			if($hasVat) {
				$h .= '<div class="cashflow-create-operation-validate" data-field="vatValue"><div><span>=</span><span data-type="value"></span></div></div>';
				$h .= '<div class="cashflow-create-operation-validate" data-field="amountIncludingVAT"><div><span>=</span><span data-type="value"></span></div></div>';
			}



		$h .= '</div>';

		return $h;

	}

	public static function getCreateGrid(
		\farm\Farm $eFarm,
		Operation $eOperation,
		\bank\Cashflow $eCashflow,
		\account\FinancialYear $eFinancialYear,
		int $index,
		\util\FormUi $form,
		array $defaultValues,
		\Collection $cPaymentMethod,
	): string {

		$suffix = '['.$index.']';

		$h = '<div id="operation-create-list" class="operation-create-several-container" data-columns="1" data-cashflow="'.($eCashflow->notEmpty() ? '1' : '0').'">';

			$h .= self::getCreateHeader($eFinancialYear, $eCashflow);
			$h .= self::getFieldsCreateGrid($form, $eOperation, $eCashflow, $eFinancialYear, $suffix, $defaultValues, [], $cPaymentMethod);

			$h .= self::getCreateValidate($eFinancialYear['hasVat'], $eCashflow->notEmpty());

		$h .= '</div>';

		return $h;

	}

	public static function getUpdateGrid(
		\account\FinancialYear $eFinancialYear,
		\util\FormUi $form,
		\Collection $cPaymentMethod,
		\Collection $cOperation,
		\bank\Cashflow $eCashflow,
		Operation $eOperationRequested,
		string $for,
	): string {

		$isFromCashflow = $eCashflow->notEmpty();

		$index = 0;

		$hasAsset = $cOperation->find(fn($e) => \asset\AssetLib::isAsset($e['accountLabel']))->count() > 0;

		$h = '';

		if($eCashflow->notEmpty()) {
			$h .= '<span name="cashflow-amount" class="hide">'.$eCashflow['amount'].'</span>';
			$h .= $form->hidden('type', $eCashflow['type']);
			$h .= $form->hidden('cashflow', $eCashflow['id']);
		}

		$attributes = ['class' => 'operation-create-several-container', 'data-columns' => $cOperation->count(), 'data-cashflow' => $isFromCashflow ? '1' : '0'];
		if($hasAsset) {
			$attributes['data-asset'] = 1;
		}
		if($for === 'update') {
			$attributes['id'] = 'operation-update-list';
			if($eCashflow->empty()) {
				$attributes['onrender'] = 'OperationAmount.checkAmounts(0);';
			}
		} else {
			$attributes['id'] = 'operation-create-list';
		}
		$h .= '<div '.attrs($attributes).'>';
			$h .= self::getCreateHeader($eFinancialYear, $eCashflow);

			foreach($cOperation as $eOperation) {

				$suffix = '['.$index.']';
				$eOperation['isRequested'] = ($eOperationRequested->notEmpty() and $eOperationRequested['id'] === $eOperation['id']);

				$eOperation['amountIncludingVAT'] = $eOperation['amount'] + ($eOperation['vatAmount'] ?? 0);
				$h .= self::getFieldsCreateGrid($form, $eOperation, $eCashflow, $eFinancialYear, $suffix, $eOperation->getArrayCopy(), [], $cPaymentMethod);
				$index++;
			}

			$h .= self::getCreateValidate($eFinancialYear['hasVat'], $isFromCashflow);

		$h .= '</div>';

		return $h;

	}

	public function getAttach(\farm\Farm $eFarm, \Collection $cOperation, \Collection $cCashflow, ?string $tip): \Panel {

		$h = '';

		if($tip) {
			$h .= new \farm\TipUi()->get($eFarm, $tip, 'inline');
		}

		$h .= '<h3>';
			$h .= p("Écriture à rattacher", "Groupe d'écritures à rattacher", $cOperation->count());
		$h .= '</h3>';

		$h .= '<div class="bg-background">';
			$h .= new JournalUi()->list($eFarm, NULL, $cOperation, $eFarm['eFinancialYear'], readonly: TRUE, displayTotal: TRUE);
		$h .= '</div>';

		if($cOperation->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS))->notEmpty()) {
			$h .= '<div class="color-warning">';
				$h .= s("Attention, dans les écritures que vous avez sélectionnées, il y a déjà des écritures en compte de banques <b>{bankAccount}</b>.", ['bankAccount' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);
			$h .= '</div>';
		}

		$h .= '<h3 class="mt-1">';
			$h .= s("Rattachement");
		$h .= '</h3>';

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/operations:doAttach', ['method' => 'post', 'id' => 'cashflow-doAttach', 'class' => 'panel-dialog']);

		$amount = 0;
		foreach($cOperation as $eOperation) {

			$h .= $form->hidden('operations[]', $eOperation['id']);

			if($eOperation['type'] === Operation::CREDIT) {

				$amount += $eOperation['amount'];

			} else {

				$amount -= $eOperation['amount'];

			}
		}

		$h .= $form->hidden('operation-amount', $amount);

		$eThidParty = $cOperation->first()['thirdParty'];
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

		if($cCashflow->notEmpty()) {

			$cashflow = '<table class="tr-hover tr-even bg-background">';

				$cashflow .= '<thead>';
					$cashflow .= '<tr>';
						$cashflow .= '<th class="td-checkbox"></th>';
						$cashflow .= '<th>'.s("Date").'</th>';
						$cashflow .= '<th>'.s("Libellé").'</th>';
						$cashflow .= '<th class="text-end highlight-stick-right td-min-content">'.s("Débit").'</th>';
						$cashflow .= '<th class="text-end highlight-stick-left td-min-content">'.s("Crédit").'</th>';
					$cashflow .= '</tr>';
				$cashflow .= '</thead>';

				$cashflow .= '<tbody>';

					foreach($cCashflow as $eCashflow) {

						$cashflow .= '<tr>';
							$cashflow .= '<td class="td-checkbox">';
								$cashflow .= $form->radio('cashflow', $eCashflow['id'], '');
							$cashflow .= '</td>';
							$cashflow .= '<td>';
								$cashflow .= \util\DateUi::numeric($eCashflow['date']);
							$cashflow .= '</td>';
							$cashflow .= '<td>';
								$cashflow .= encode($eCashflow->getMemo());
							$cashflow .= '</td>';
							$cashflow .= '<td class="text-end highlight-stick-right td-min-content">';
								if($eCashflow['amount'] < 0) {
									$cashflow .= \util\TextUi::money($eCashflow['amount']);
								}
							$cashflow .= '</td>';
							$cashflow .= '<td class="text-end highlight-stick-left td-min-content">';
								if($eCashflow['amount'] >= 0) {
									$cashflow .= \util\TextUi::money($eCashflow['amount']);
								}
							$cashflow .= '</td>';
						$cashflow .= '</tr>';

					}

				$cashflow .= '</tbody>';
			$cashflow .= '</table>';
			$cashflow .= '<div class="util-annotation">';
				$cashflow .= s("Vous ne trouvez pas l'opération que vous cherchez ? Essayez de la retrouver en <link>consultant toutes les opérations bancaires sans écriture</link>.", ['link' => '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/operations?status=waiting">']);
			$cashflow .= '</div>';

		} else {

			$cashflow = '<div class="util-block-secondary">';
				$cashflow .= s("Aucune opération bancaire non rattachée à des écritures et d'un montant ±1€ n'a pu être retrouvée. Tentez l'action dans l'autre sens en <link>partant des opérations bancaires</link> pour y rattacher vos écritures !", ['link' => '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/banque/operations?status=waiting">']);
			$cashflow .= '</div>';

		}


		$h .= $form->group(
			s("Opération bancaire").\util\FormUi::asterisk(),
			$cashflow,
			['wrapper' => 'cashflow']
		);

		$h .= $form->group($form->submit(s("Rattacher"), ['class' => 'btn btn-secondary']));

		return new \Panel(
			id: 'panel-operation-attach',
			title: p("Rattacher l'écriture à une opération bancaire", "Rattacher les écritures à une opération bancaire", $cOperation->count()),
			body: $h,
			dialogOpen : $dialogOpen,
			dialogClose: $form->close(),
		);

	}
	public static function url(\farm\Farm $eFarm, Operation $eOperation): string {

		return \company\CompanyUi::urlJournal($eFarm).'/livre-journal?id='.$eOperation['id'];

	}

	public static function link(\farm\Farm $eFarm, Operation $eOperation, bool $newTab): string {

		return '<a href="'.self::url($eFarm, $eOperation).'" class="btn btn-sm btn-outline-primary" '.($newTab ? 'target="_blank"' : '').'>'.$eOperation['id'].'</a>';

	}

	public function getTitle(Operation $eOperation): string {

		if($eOperation['type'] === Operation::CREDIT) {
			return s(
				"Crédit du {date} de {amount}",
				['date' => \util\DateUi::numeric($eOperation['date']), 'amount' => \util\TextUi::money($eOperation['amount'])]
			);
		}

		return s(
			"Débit du {date} de {amount}",
			['date' => \util\DateUi::numeric($eOperation['date']), 'amount' => \util\TextUi::money($eOperation['amount'])]
		);

	}

	public static function getAutocompleteDescriptions(string $description): array {

		\Asset::css('media', 'media.css');

		return [
			'value' => $description,
			'itemText' => encode($description),
			'itemHtml' => $description,
		];

	}

	public static function getAutocomplete(\bank\Cashflow $eCashflow, Operation $eOperation): array {

		\Asset::css('media', 'media.css');

		if($eOperation['document']) {
			$document = '<br /><small class="color-muted">'.s("Pièce comptable : ").encode($eOperation['document']).'</small>';
		} else {
			$document = '';
		}

		$tableRow = \bank\CashflowUi::getOperationLineForAttachment($eCashflow, $eOperation);

		$amountExcludingVat = 0;
		$amountIncludingVat = 0;

		foreach($eOperation['cOperationHash'] as $eOperationHash) {

			if(\account\AccountLabelLib::isFromClass($eOperationHash['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS)) {
				continue;
			}

			$amountIncludingVat += $eOperationHash['type'] === \journal\Operation::DEBIT ? $eOperationHash['amount'] : -1 * $eOperationHash['amount'];

			if(\account\AccountLabelLib::isFromClass($eOperationHash['accountLabel'], \account\AccountSetting::VAT_CLASS) === FALSE) {
				if($eOperationHash['type'] === \journal\Operation::DEBIT) {
					$amountExcludingVat += $eOperationHash['amount'];
				} else {
					$amountExcludingVat += (-1 * $eOperationHash['amount']);
				}
			}

		}

		$itemHtml = '<div style="display: flex; gap: 0.5rem;">';
			$itemHtml .= '<div class="text-end">';
				$itemHtml .= \util\DateUi::numeric($eOperation['date']);
			$itemHtml .= '</div>';
			$itemHtml .= '<div>';
				if($eOperation['cOperationHash']->notEmpty()) {
					$itemHtml .= encode($eOperation['description']).' - '.s("Opération de {amount} <small>HT</small> ({amountIncludingVat} <small>TTC</small>)", [
						'amount' => \util\TextUi::money(abs($amountExcludingVat)),
						'amountIncludingVat' => \util\TextUi::money(abs($amountIncludingVat)),
					]);
				} else {
					$itemHtml .= encode($eOperation['description']).' - '.s("{type} de {amount}", [
						'type' => self::p('type')->values[$eOperation['type']],
						'amount' => \util\TextUi::money($eOperation['amount']),
					]);

				}

				if($eOperation['thirdParty']->notEmpty()) {
					$itemHtml .= '<br />';
					$itemHtml .= \Asset::icon('person-rolodex').' '.encode($eOperation['thirdParty']['name']);
				}

				$itemHtml .= $document;
			$itemHtml .= '</div>';
		$itemHtml .= '</div>';

		return [
			'value' => $eOperation['id'],
			'itemText' => encode($eOperation['description']),
			'itemHtml' => $itemHtml,
			'tableRow' => $tableRow,
		];

	}

	public function query(\PropertyDescriber $d, \farm\Farm $eFarm, \bank\Cashflow $eCashflow, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('journal-bookmark');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Écriture...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlFarm($eFarm).'/journal/operation:query';
		$d->autocompleteResults = function(Operation $eOperation) use($eCashflow) {
			return self::getAutocomplete($eCashflow, $eOperation);
		};

	}

	public static function getAutocompleteDeferral(\farm\Farm $eFarm, Operation $eOperation): array {

		\Asset::css('media', 'media.css');

		if($eOperation['document']) {
			$document = '<br /><small class="color-muted">'.s("Pièce comptable : ").encode($eOperation['document']).'</small>';
		} else {
			$document = '';
		}

		$itemHtml = '<div style="display: flex; gap: 0.5rem;">';
			$itemHtml .= '<div class="text-end">';
				$itemHtml .= \util\DateUi::numeric($eOperation['date']);
				$itemHtml .= '<br />'.encode($eOperation['accountLabel']);
			$itemHtml .= '</div>';
			$itemHtml .= '<div>';
				$itemHtml .= encode($eOperation['description']).' - '.s("{type} de {amount}", [
					'type' => \journal\OperationUi::p('type')->values[$eOperation['type']],
					'amount' => \util\TextUi::money($eOperation['amount']),
				]);
				if($eOperation['thirdParty']->notEmpty()) {
					$itemHtml .= '<br />';
					$itemHtml .= \Asset::icon('person-rolodex').' '.encode($eOperation['thirdParty']['name']);
				}

				$itemHtml .= $document;
			$itemHtml .= '</div>';
		$itemHtml .= '</div>';

		return [
			'value' => $eOperation['id'],
			'link' => \company\CompanyUi::urlFarm($eFarm).'/journal/deferral:set?id='.$eOperation['id'],
			'itemHtml' => $itemHtml,
			'itemText' => encode($eOperation['description']).' - '.s(
					"{type} de {amount}", [
					'type' => \journal\OperationUi::p('type')->values[$eOperation['type']],
					'amount' => $eOperation['amount'].' €',
				]
			)
		];

	}

	public function queryForDeferral(\PropertyDescriber $d, \farm\Farm $eFarm, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('journal-bookmark');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Écriture...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlFarm($eFarm).'/journal/operation:queryForDeferral';
		$d->autocompleteResults = function(Operation $eOperation) use($eFarm) {
			return self::getAutocompleteDeferral($eFarm, $eOperation);
		};

	}

	public function queryDescription(\PropertyDescriber $d, \farm\Farm $eFarm, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('pencil-fill');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Libellé...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = \company\CompanyUi::urlFarm($eFarm).'/journal/operation:queryDescription';
		$d->autocompleteResults = function(string $description) {
			return self::getAutocompleteDescriptions($description);
		};

		$d->autocompleteTextual = TRUE;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
			'account' => s("Compte"),
			'accountLabel' => s("Numéro de compte"),
			'asset' => s("Immobilisation liée"),
			'date' => s("Date de l'opération"),
			'description' => s("Libellé"),
			'document' => s("Pièce comptable"),
			'documentDate' => s("Date de la pièce comptable"),
			'amount' => s("Montant (HT)"),
			'type' => s("Type (débit / crédit)"),
			'thirdParty' => s("Tiers"),
			'paymentMethod' => s("Mode de paiement"),
			'paymentDate' => s("Date de paiement"),
			'vatRate' => s("Taux de TVA"),
			'vatValue' => s("Valeur de TVA"),
			'journalCode' => s("Journal"),
		]);

		switch($property) {

			case 'documentDate' :
			case 'date' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'type':
				$d->values = [
					Operation::DEBIT => s("Débit"),
					Operation::CREDIT => s("Crédit"),
				];
				break;

			case 'journalCode':
				$d->field = 'select';
				$d->values = fn($e) => $e['cJournalCode'] ?? new \Collection();

				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'));
				break;

			case 'description':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new OperationUi()->queryDescription($d, GET('farm', 'farm\Farm'));
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', 'farm\Farm'), query: GET('query'));
				break;

			case 'asset':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \asset\AssetUi()->query($d, GET('farm', 'farm\Farm'));
				break;

			case 'vatValue' :
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, Operation $e) {
					return $form->addon(s("€"));
				};
				break;

			case 'amount' :
				$d->field = 'calculation';
				$d->append = function(\util\FormUi $form, Operation $e) {
					return $form->addon(s("€"));
				};
				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() and (int)substr($e['accountLabel'], 0, 3) !== \account\AccountSetting::VAT_CLASS) ? \util\FormUi::info(s("Attention, pensez à répercuter ce changement sur la ligne de TVA si elle existe")) : '';
				break;


			case 'thirdParty':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', 'farm\Farm'));
				break;

			case 'paymentDate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'paymentMethod' :
				$d->values = fn(Operation $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				break;

		}

		return $d;

	}

}

?>
