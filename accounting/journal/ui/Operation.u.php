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

	public function createCommentCollection(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateCommentCollection',
			[
				'id' => 'journal-operation-comment',
				'class' => 'panel-dialog',
			],
		);

		$h = $form->dynamicGroup(new Operation(), 'comment');
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}

		$dialogClose = $form->close();
		$footer = $form->submit(s("Enregistrer"));
		return new \Panel(
			id: 'panel-journal-operations-comment',
			title: s("Commenter"),
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);
	}

	public function createDocumentCollection(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();
		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doUpdateDocumentCollection',
			[
				'id' => 'journal-operation-document',
				'class' => 'panel-dialog',
			],
		);

		$h = $form->dynamicGroup(new Operation(), 'document');
		foreach(get('ids', 'array') as $id) {
			$h .= $form->hidden('ids[]', $id);
		}

		$dialogClose = $form->close();
		$footer = $form->submit(s("Enregistrer"));
		return new \Panel(
			id: 'panel-journal-operations-document',
			title: s("Enregister la pièce comptable"),
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);
	}

	public function getView(\farm\Farm $eFarm, Operation $eOperation): \Panel {

		\Asset::css('journal', 'operation.css');

		$h = $this->getSummary($eOperation);
		$h .= $this->getUtil($eFarm, $eOperation);
		$h .= $this->getLinked($eFarm, $eOperation);

		return new \Panel(
			id: 'panel-operation-view',
			title: $this->getTitle($eOperation),
			body: $h,
			close: 'reload'
		);
	}
	public function getSummary(Operation $eOperation): string {

		$h = '<div class="util-block stick-xs bg-background-light">';

			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Date d'opération").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eOperation['date']).' ('.s("Exercice {value}", \account\FinancialYearUi::getYear($eOperation['financialYear'])).')</dd>';

				$h .= '<dt>'.s("Date de paiement").'</dt>';
				$h .= '<dd>';
					$h .= $eOperation['paymentDate'] !== NULL ? \util\DateUi::numeric($eOperation['paymentDate']) : '<i>'.s("Non indiqué").'</i>';
				$h .= '</dd>';
				$h .= '<dt>'.s("Numéro et libellé de compte").'</dt>';
				$h .= '<dd>'.encode($eOperation['accountLabel']).' - '.encode($eOperation['account']['description']).'</dd>';
				$h .= '<dt>'.s("Type") .'</dt>';
				$h .= '<dd>'.self::p('type')->values[$eOperation['type']].'</dd>';
				$h .= '<dt>'.s("Tiers").'</dt>';
				$h .= '<dd>'.encode($eOperation['thirdParty']['name']).'</dd>';
				$h .= '<dt>'.s("Montant") .'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eOperation['amount']).'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	private function pencil(): string {
		return \Asset::icon('pencil', ['class' => 'operation_view_icon']);
	}

	public function getUtil(\farm\Farm $eFarm, Operation $eOperation): string {

		if($eOperation->canUpdate()) {
			$eOperation->setQuickAttribute('farm', $eFarm['id']);
			$eOperation->setQuickAttribute('app', 'accounting');
		}
		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Informations générales").'</h3>';
		$h .= '</div>';

		if($eOperation['operation']->notEmpty()) {
			$h .= '<div class="util-info">'.s("Ces informations ne sont modifiables que depuis <link>l'écriture d'origine</link>.", ['link' => '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperation['operation']['id'].'">']).'</div>';
		}

		$h .= '<dl class="util-presentation util-presentation-1">';
		$h .= '<dt>'.s("Libellé d'opération").'</dt>';
		$h .= '<dd>';
		$description = $eOperation['description'] ? $eOperation['description'] : '<i>'.s("Non indiqué").'</i>';
		if($eOperation->canUpdateQuick()) {
			$h .= $eOperation->quick('description', $description.$this->pencil());
		} else {
			$h .= $description;
		}
		$h .= '</dd>';

			$h .= '<dt>'.s("Pièce comptable") .'</dt>';
			$h .= '<dd>';
				$document = $eOperation['document'] ? $eOperation['document'] : '<i>'.s("Non indiqué").'</i>';
				if($eOperation->canUpdateQuick()) {
					$h .= $eOperation->quick('document', $document.$this->pencil());
				} else {
					$h .= $document;
				}
			$h .= '</dd>';

			$h .= '<dt>'.s("Journal") .'</dt>';
			$h .= '<dd>';

				if($eOperation['journalCode'] !== NULL) {
					$journalCode = self::getShortJournal($eFarm, $eOperation['journalCode'], link: FALSE);
					$journalCode .= ' ';
					$journalCode .= '<span class="journal-'.$eOperation['journalCode'].'">'.($eOperation['journalCode'] ? self::p('journalCode')->values[$eOperation['journalCode']] : '');
				} else {
					$journalCode = '<i>'.s("Non indiqué").'</i>';
				}
				if($eOperation->canUpdateQuick()) {
					$h .= $eOperation->quick('journalCode', $journalCode.$this->pencil());
				} else {
					$h .= $journalCode;
				}
			$h .= '</dd>';

			$h .= '<dt>'.s("Moyen de paiement").'</dt>';
			$h .= '<dd>';
				$paymentMethod = ($eOperation['paymentMethod']['name'] ?? NULL) !== NULL ? \payment\MethodUi::getName($eOperation['paymentMethod']) : '<i>'.s("Non indiqué").'</i>';
				if($eOperation->canUpdateQuick()) {
					$h .= $eOperation->quick('paymentMethod', $paymentMethod.$this->pencil());
				} else {
					$h .= $paymentMethod;
				}
			$h .= '</dd>';

			$h .= '<dt>'.s("Commentaire").'</dt>';
			$h .= '<dd>';
				$comment = ($eOperation['comment'] !== NULL) ? encode($eOperation['comment']) : '<i>-</i>';
				if($eOperation->canUpdateQuick()) {
					$h .= $eOperation->quick('comment', $comment.$this->pencil());
				} else {
					$h .= $comment;
				}
			$h .= '</dd>';
		$h .= '</dl>';

		return $h;
	}

	public function getLinked(\farm\Farm $eFarm, Operation $eOperation): string {

		$linkedOperationIds = [$eOperation['id']];
		if($eOperation['operationLinked']->notEmpty()) {
			$linkedOperationIds[] = $eOperation['operationLinked']['id'];
		}

		if($eOperation['operationLinked']->empty() and $eOperation['cOperationCashflow']->empty() and ($eOperation['cOperationLinkedByCashflow']->count() - count($linkedOperationIds)) <= 0) {
			return '';
		}

		$h = '<div class="util-title mt-2">';
			$h .= '<h3>'.s("Écritures liées").'</h3>';
		$h .= '</div>';

		if($eOperation['cOperationCashflow']->notEmpty()) {

			$h .= '<h4>'.p("Opération bancaire", "Opérations bancaires", $eOperation['cOperationCashflow']->count()).'</h4>';

			$h .= '<table class="tr-even tr-hover">';
				$h .= '<thead>';
					$h .= '<tr class="tr-title">';
						$h .= '<th>#</th>';
						$h .= '<th>'.s("Date d'opération bancaire").'</th>';
						$h .= '<th>'.s("Description").'</th>';
						$h .= '<th>'.s("Montant").'</th>';
						$h .= '<th>'.s("Importée le").'</th>';
						$h .= '<th>'.s("Importée par").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($eOperation['cOperationCashflow'] as $eOperationCashflow) {

						$eCashflow = $eOperationCashflow['cashflow'];
						$h .= '<tr>';
							$h .= '<td><a href="'.\company\CompanyUi::urlBank($eFarm).'/cashflow?id='.$eCashflow['id'].'">'.encode($eCashflow['id']).'</a></td>';
							$h .= '<td>'.\util\DateUi::numeric($eCashflow['date']).'</td>';
							$h .= '<td>'.encode($eCashflow['memo']).'</td>';
							$h .= '<td>'.\util\TextUi::money($eCashflow['amount']).'</td>';
							$h .= '<td>'.\util\DateUi::numeric($eCashflow['createdAt']).'</td>';
							$h .= '<td>'.$eCashflow['createdBy']->getName().'</td>';
						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
		}

		if($eOperation['operationLinked']->notEmpty() or ($eOperation['cOperationLinkedByCashflow']->count() - count($linkedOperationIds)) > 0) {

			$count = ($eOperation['operationLinked']->notEmpty() ? 1 : 0) + $eOperation['cOperationLinkedByCashflow']->count() - count($linkedOperationIds);

			$h .= '<h4>'.p("Écriture liée", "Écritures liées", $count).'</h4>';

			$h .= '<table class="tr-even tr-hover">';
				$h .= '<thead>';
					$h .= '<tr class="tr-title">';
						$h .= '<th></th>';
						$h .= '<th>'.s("Numéro et libellé de compte").'</th>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Type").'</th>';
						$h .= '<th>'.s("Description").'</th>';
						$h .= '<th>'.s("Montant").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				if($eOperation['operationLinked']->notEmpty()) {
					$eOperationLinked = $eOperation['operationLinked'];
						$h .= '<tr>';
							$h .= '<td>'.self::getShortJournal($eFarm, $eOperationLinked['journalCode'], link: FALSE).'</td>';
							$h .= '<td>'.encode($eOperationLinked['accountLabel']).' - '.encode($eOperationLinked['account']['description']).'</td>';
							$h .= '<td>'.\util\DateUi::numeric($eOperationLinked['date']).'</td>';
							$h .= '<td>'.self::p('type')->values[$eOperationLinked['type']].'</td>';
							$h .= '<td>'.encode($eOperationLinked['description']).'</td>';
							$h .= '<td class="text-end">'.\util\TextUi::money($eOperationLinked['amount']).'</td>';
							$h .= '<td><a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperationLinked['id'].'">'.s("Voir").'</a></td>';
						$h .= '</tr>';
					}

				foreach($eOperation['cOperationLinkedByCashflow'] as $eOperationLinkedByCashflow) {

					if(in_array($eOperationLinkedByCashflow['id'], $linkedOperationIds)) {
						continue;
					}

					$h .= '<tr>';
						$h .= '<td>'.self::getShortJournal($eFarm, $eOperationLinkedByCashflow['journalCode'], link: FALSE).'</td>';
						$h .= '<td>'.encode($eOperationLinkedByCashflow['accountLabel']).' - '.encode($eOperationLinkedByCashflow['account']['description']).'</td>';
						$h .= '<td>'.\util\DateUi::numeric($eOperationLinkedByCashflow['date']).'</td>';
						$h .= '<td>'.self::p('type')->values[$eOperationLinkedByCashflow['type']].'</td>';
						$h .= '<td>'.encode($eOperationLinkedByCashflow['description']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eOperationLinkedByCashflow['amount']).'</td>';
						$h .= '<td><a href="'.\company\CompanyUi::urlJournal($eFarm).'/operation/'.$eOperationLinkedByCashflow['id'].'">'.s("Voir").'</a></td>';
					$h .= '</tr>';
				}

				$h .= '</tbody>';
			$h .= '</table>';
		}

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

	public function createPayment(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, Operation $eOperation, \Collection $cBankAccount): \Panel {

		\Asset::css('journal', 'operation.css');
		\Asset::js('journal', 'payment.js');
		\Asset::js('account', 'thirdParty.js');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doCreatePayment',
			[
				'id' => 'journal-operation-create-payment',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog',
			],
		);

		$h = $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);

		$h .= $form->group(
			s("Type de paiement").\util\FormUi::asterisk(),
			'<div class="form-control field-radio-group operation-payment-type-radio">'
					.'<label>'
						.s("Encaissement : ")
						.$form->radios(
						'paymentType',
						['incoming-client' => s("Un client paie une facture"), 'incoming-supplier' => s("Un fournisseur me rembourse")],
						attributes: ['mandatory' => TRUE, 'onchange' => 'PaymentOperation.updateAccountLabel();'])
				.'</label>'
				.'<label>'
						.s("Décaissement : ")
					.$form->radios(
						'paymentType',
						['outgoing-client' => s("Je rembourse un client"), 'outgoing-supplier' => s("Je paie un fournisseur")],
						attributes: ['mandatory' => TRUE, 'onchange' => 'PaymentOperation.updateAccountLabel();'])
				.'</label>'
			.'</div>'
		);

		$h .= $form->dynamicGroup($eOperation, 'thirdParty*', function($d) use($form) {
			$d->autocompleteDispatch = '[data-third-party="'.$form->getId().'"]';
			$d->attributes['data-third-party'] = $form->getId();
			$d->default = fn($e, $property) => get('thirdParty');
		});

		$h .= $form->dynamicGroup($eOperation, 'amount*', function($d) {
			$d->label = s("Montant du paiement");
		});
		$h .= $form->dynamicGroups($eOperation, ['paymentMethod*', 'paymentDate*']);

		$bankValues = $cBankAccount->makeArray(fn($e) => ['value' => $e['id'], 'label' => $e['label']]);

		$h .= $form->group(
			s("Libellé de compte banque").\util\FormUi::asterisk(),
			$form->select(
				'bankAccountLabel',
				$bankValues,
				first($bankValues),
				attributes: ['mandatory' => TRUE]
			).\util\FormUi::info(s(
				"Pour modifier vos comptes bancaires, rendez-vous dans les <link>Paramètres > Les comptes bancaires</link> de votre ferme",
				['link' => '<a href="'.\company\CompanyUi::urlBank($eFarm).'/account" target="_blank">'])
			)
		);

		$h .= $form->group(s("Compte"), $form->text('accountLabel', NULL, ['disabled' => TRUE]).\util\FormUi::info(s("Le numéro de compte (fournisseur ou client) est automatiquement rempli")));

		$h .= '<div id="waiting-operations-list-container" class="hide">'
			.$form->group(
				s("Écritures non lettrées"),
				'<div class="util-info" id="waiting-operations-list-info">'.$this->letteringInfo().'</div><div id="waiting-operations-list"></div>'
			)
		.'</div>';

		$saveButton = $form->submit(
			s("Enregistrer le paiement et lettrer automatiquement"),
			[
				'id' => 'submit-save-payment',
			],
		);

		$dialogClose = $form->close();

		return new \Panel(
			id: 'panel-journal-payment-create',
			title: s("Ajouter un paiement"),
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: '<div class="operation-create-buttons">'.$saveButton.'</div>',
		);

	}

	public function letteringInfo(): string {
		return s("Note : Les opérations non lettrées sont lettrées dans l'ordre chronologique d'écriture (de la plus ancienne à la plus récente).");
	}

	public function listWaitingOperations(\farm\Farm $eFarm, \util\FormUi $form, \Collection $cOperation): string {

		$h = '<table class="tr-even">';
			$h .= '<tr>';
				$h .= '<th class="text-center">#</th>';
				$h .= '<th>'.s("Date").'</th>';
				$h .= '<th>'.s("Libellé").'</th>';
				$h .= '<th>'.s("Compte").'</th>';
				$h .= '<th class="text-end">'.s("Débit (restant à lettrer)").'</th>';
				$h .= '<th class="text-end">'.s("Crédit (restant à lettrer)").'</th>';
			$h .= '</tr>';

			foreach($cOperation as $eOperation) {

				$balance = $eOperation['amount']
					- $eOperation['cLetteringDebit']->reduce(fn($e, $n) => $n + $e['amount'], 0)
					- $eOperation['cLetteringCredit']->reduce(fn($e, $n) => $n + $e['amount'], 0);

				$h .= '<tr>';
					$h .= '<td class="td-min-content text-center">'.OperationUi::link($eFarm, $eOperation, newTab: TRUE).'</td>';
					$h .= '<td>'.\util\DateUi::numeric($eOperation['date']).'</td>';
					$h .= '<td>'.encode($eOperation['description']).'</td>';
					$h .= '<td>'.encode($eOperation['accountLabel']).'</td>';

					$h .= '<td class="text-end">';
						if($eOperation['type'] === Operation::DEBIT) {
							$h .= \util\TextUi::money($balance);
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="text-end">';
						if($eOperation['type'] === Operation::CREDIT) {
							$h .= \util\TextUi::money($balance);
						} else {
							$h .= '-';
						}
					$h .= '</td>';
				$h .= '</tr>';
			}

		$h .= '</table>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, Operation $eOperation, \account\FinancialYear $eFinancialYear, array $assetData, \Collection $cPaymentMethod, ?string $invoice = NULL): \Panel {

		\Asset::css('journal', 'operation.css');
		\Asset::js('journal', 'operation.js');
		\Asset::js('journal', 'asset.js');
		\Asset::js('account', 'thirdParty.js');

		$dialogOpen = '';

		$invoiceFileForm = new \util\FormUi();

		$importButton = $invoiceFileForm->openAjax(\company\CompanyUi::urlJournal($eFarm).'/operation:readInvoice', ['id' => 'read-invoice', 'binary' => TRUE, 'method' => 'post']);
			$importButton .= $invoiceFileForm->hidden('farm', $eFarm['id']);
			$importButton .= $invoiceFileForm->hidden('columns', 1);
			$importButton .= $invoiceFileForm->file('invoice', ['onchange' => 'Operation.submitReadInvoice();', 'accept' => 'image/*,.pdf']);
			$importButton .= $invoiceFileForm->submit(s("Envoyer la facture"), ['class' => 'hide', 'id' => 'read-invoice-submit']);
		$importButton .= $invoiceFileForm->close();

		$dialogOpen .= $importButton;

		$form = new \util\FormUi();

		$dialogOpen .= $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doCreate',
			[
				'id' => 'journal-operation-create',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog',
				'data-has-vat' => (int)$eFinancialYear['hasVat'],
			],
		);

		$h = '';

		$h .= '<div class="util-block-help hide" data-help="dropbox">';
			$h .= s("Le saviez-vous ? En connectant votre compte {siteName} à un gestionnaire de fichiers comme Dropbox, vos documents comptables seront automatiquement rangés lorsque vous les importez lors de la saisie de vos opérations ! Vous pouvez le faire <link>dans les réglages de comptabilité de votre ferme</link>.",
				['link' => '<a href="'.\company\CompanyUi::url($eFarm).'/company:update?id='.$eFarm['id'].'" target="_blank">']);
		$h .= '</div>';
		$h .= '<div>';

		$h .= '<div class="util-block-help hide" data-help="grant">';
			$h .= '<h4>'.s("Quelques précisions sur les immobilisations").'</h4>';
			$h .= '<p>'.s("En règle générale, les durées sont de :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s('5 ans pour un montant < 1 000 € (petites aides à effet immédiat)').'</li>';
				$h .= '<li>'.s('7 ans pour un montant compris entre 1 000 € et 10 000 € (aide intermédiaire)').'</li>';
				$h .= '<li>'.s('10 ans pour un montant > 10 000 € (durée recommandée par défaut)').'</li>';
			$h .= '</ul>';
		$h .= '</div>';

		$h .= '<div class="util-block-help hide" data-help="grant">';
			$h .= '<h4>'.s("Quelques précisions sur les subventions").'</h4>';
			$h .= '<p>'.s("La durée de la subvention est équivalente à celle de l'immobilisation liée.").'</p>';
			$h .= '<p>'.s("Si aucune suvention n'est liée, voici quelques indications pour la durée de l'utilisation comptable de la subvention :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s('5 à 10 ans pour du matériel agricole (compte 2153)').'</li>';
				$h .= '<li>'.s('4 à 7 ans pour du matériel de transport (compte 2154)').'</li>';
				$h .= '<li>'.s('15 à 25 ans pour des constructions agricoles (compte 2132)').'</li>';
				$h .= '<li>'.s('sans amortissement pour les terrains (compte 212)').'</li>';
			$h .= '</ul>';
		$h .= '</div>';

			$h .= '<div style="display: flex;">';

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('financialYear', $eFinancialYear['id']);

				$index = 0;
				$defaultValues = $eOperation->getArrayCopy();

				$h .= self::getCreateGrid($eFarm, $eOperation, $eFinancialYear, $index, $form, $defaultValues, $assetData, $cPaymentMethod);

				$h .= '<div class="operation-invoice-preview hide">';
					$h .= '<embed class="hide"/>';
					$h .= '<img class="hide"/>';
					$h .= $form->hidden('invoiceFile', '');
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		$addButton = '<a id="add-operation" data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/operation:addOperation" post-index="'.($index + 1).'" post-amount="" post-third-party="" class="btn btn-outline-secondary">';
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

		$footer = '<div class="operation-create-buttons">'
			.'<div class="import-invoice-button">';

				$footer .= '<label class="btn btn-outline-secondary" onclick="Operation.openInvoiceFileForm();">'
						.s("Importer une facture")
					.'</label>';

			$footer .= '</div>'
			.'<div class="operation-create-button-add">'.$addButton.$saveButton.'</div>'
		.'</div>';

		$defaultTitle = s("Ajouter une écriture");
		return new \Panel(
			id: 'panel-journal-operation-create',
			title: '<div id="panel-journal-operation-create-title" data-text-singular="'.$defaultTitle.'" data-text-plural="'.s("Ajouter plusieurs écritures").'">'.$defaultTitle.'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	private static function getCreateHeader(\account\FinancialYear $eFinancialYear, bool $isFromCashflow): string {

		$h = '<div class="operation-create operation-create-headers">';

			$h .= '<h4>&nbsp;</h4>';
			$h .= '<div class="operation-create-header">'.self::p('date')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('document')->label.'</div>';
			$h .= '<div class="operation-create-header">'.self::p('thirdParty')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('account')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('accountLabel')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('description')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-create-header">'.self::p('comment')->label.'</div>';
			$h .= '<div class="operation-create-header">'.s("Montant TTC").'</div>';
			$h .= '<div class="operation-create-header">'.self::p('amount')->label.' '.\util\FormUi::asterisk().'</div>';

			$h .= '<div class="operation-asset" data-is-asset="1">';
				$h .= '<h4>'.s("Immobilisation").'</h4>';
			$h .= '</div>';
			$h .= '<div class="operation-asset" data-is-asset="1">';
				$h .= \asset\AssetUi::p('type')->label.' '.\util\FormUi::asterisk();
			$h .= '</div>';
			$h .= '<div class="operation-asset" data-is-asset="1" data-asset-link="grant">'.\asset\AssetUi::p('grant')->label.'</div>';
			$h .= '<div class="operation-asset" data-is-asset="1" data-asset-link="asset">'.\asset\AssetUi::p('asset')->label.'</div>';
			$h .= '<div class="operation-asset" data-is-asset="1">'.\asset\AssetUi::p('acquisitionDate')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-asset" data-is-asset="1">'.\asset\AssetUi::p('startDate')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-asset" data-is-asset="1">'.\asset\AssetUi::p('value')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="operation-asset" data-is-asset="1">'.\asset\AssetUi::p('duration')->label.' '.\util\FormUi::asterisk().'</div>';

			$h .= '<div class="operation-create-header">'.self::p('type')->label.' '.\util\FormUi::asterisk().'</div>';

			if($eFinancialYear['hasVat']) {

				$h .= '<div class="operation-create-header">'.self::p('vatRate')->label.' '.\util\FormUi::asterisk().'</div>';
				$h .= '<div class="operation-create-header" data-wrapper="vatValue">'.self::p('vatValue')->label.' '.\util\FormUi::asterisk().'</div>';
			}

			if($isFromCashflow === FALSE) {

				$mandatory = $eFinancialYear->isCashAccounting() ? ' '.\util\FormUi::asterisk() : '';
				$h .= '<div class="operation-create-header">'.self::p('paymentDate')->label.$mandatory.'</div>';
				$h .= '<div class="operation-create-header">'.self::p('paymentMethod')->label.$mandatory.'</div>';

			}

		$h .= '</div>';

		return $h;

	}

	private static function getAmountButtonIcons(string $type, int $index): string {

		$activeIcon = match($type) {
			'amount' => 'lock',
			default => 'erase',
		};
		$h = '<div class="merchant-write hide">'.\Asset::icon('pencil').'</div>';
		$h .= '<div class="merchant-lock '.($activeIcon === 'lock' ? '' : 'hide').'">'.\Asset::icon('lock-fill').'</div>';
		$h .= '<div class="merchant-erase '.($activeIcon === 'erase' ? '' : 'hide').'">';
			$h .= '<a '.attr('onclick', "Operation.resetAmount('".$type."', ".$index.")").' title="'.s("Revenir à zéro").'">'.\Asset::icon('eraser-fill', ['style' => 'transform: scaleX(-1);']).'</a>';
		$h .= '</div>';

		return $h;
	}

	public static function getFieldsCreateGrid(
		\farm\Farm $eFarm,
		\util\FormUi $form,
		Operation $eOperation,
		\account\FinancialYear $eFinancialYear,
		?string $suffix,
		array $defaultValues,
		array $disabled,
		array $assetData,
		\Collection $cPaymentMethod,
	): string {

		\Asset::js('journal', 'asset.js');
		\Asset::js('journal', 'operation.js');

		$index = ($suffix !== NULL) ? mb_substr($suffix, 1, mb_strlen($suffix) - 2) : NULL;
		$isFromCashflow = ($eOperation['cOperationCashflow'] ?? new \Collection())->notEmpty();

		$cAssetGrant = $assetData['grant'] ?? new \Collection();
		$cAssetToLinkToGrant = $assetData['asset'] ?? new \Collection();

		if($eFinancialYear->isAccrualAccounting() and $index > 0) {
			$disabled[] = 'thirdParty';
		}

		$h = '<div class="operation-create" data-index="'.$index.'">';
			$h .= '<div class="operation-create-title">';
				$h .= '<h4>'.s("Écriture #{number}", ['number' => $index + 1]).'</h4>';

					$h .= '<div class="operation-create-actions">';
						if($isFromCashflow === TRUE) {

							$h .= '<div data-index="'.$index.'">';
								$h .= '<a onclick="Cashflow.recalculate('.$index.')" class="btn btn-outline-primary" title="'.s("Réinitialiser par rapport aux autres écritures").'">'.\Asset::icon('magic').'</a>';
							$h .= '</div>';

						}
					$h .= '<div class="hide" data-index="'.$index.'">';
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

			$h .= '<div data-wrapper="accountLabel'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'accountLabel'.$suffix, function($d) use($form, $index, $suffix) {
					$d->autocompleteDispatch = '[data-account-label="'.$form->getId().'"][data-index="'.$index.'"]';
					$d->attributes['data-wrapper'] = 'accountLabel'.$suffix;
					$d->attributes['data-index'] = $index;
					$d->attributes['data-account-label'] = $form->getId();
					$d->label .=  ' '.\util\FormUi::asterisk();
				});
			$h .='</div>';

			$h .= '<div data-wrapper="description'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'description'.$suffix, fn($d) => $d->default = $defaultValues['description'] ?? '');
			$h .='</div>';

			$h .= '<div data-wrapper="comment'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'comment'.$suffix, function($d) {
					$d->default = $defaultValues['comment'] ?? '';
				});
			$h .='</div>';

			$h .= '<div data-wrapper="amountIncludingVAT'.$suffix.'">';
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
			$h .='</div>';

			$h .= '<div data-wrapper="amount'.$suffix.'">';
				$h .= $form->dynamicField($eOperation, 'amount'.$suffix, function($d) use($defaultValues, $index) {
					$d->default = $defaultValues['amount'] ?? '';
					$d->attributes['min'] = 0;
					$d->attributes['step'] = 0.01;
					$d->attributes['data-field'] = 'amount';
					$d->attributes['data-index'] = $index;
					$d->attributes['disabled'] = TRUE;
					$d->prepend = OperationUi::getAmountButtonIcons('amount', $index);
				});
			$h .='</div>';

			$h .= '<div class="operation-asset" data-is-asset="1" data-index="'.$index.'">';
			$h .='</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[type]" data-is-asset="1" data-index="'.$index.'">';
				$h .= $form->radios('asset'.$suffix.'[type]', \asset\AssetUi::p('type')->values, '', [
					'data-index' => $index,
					'columns' => 3,
					'mandatory' => TRUE,
				]);
			$h .='</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[grant]" data-is-asset="1" data-index="'.$index.'" data-asset-link="grant">';
			$grants = $cAssetGrant->makeArray(fn($e) => ['value' => $e['id'], 'label' => s("{description} / montant : {amount} / durée : {duration} / date d'obtention : {date}", [
				'description' => $e['description'],
				'amount' => \util\TextUi::money($e['value']),
				'duration' => p('{value} an', '{value} ans', $e['duration']),
				'date' => \util\DateUi::numeric($e['acquisitionDate'])
				])]);
				$h .= $form->select('asset'.$suffix.'[grant]', $grants, NULL, ['placeholder' => s("< Choisir la subvention qui a financé tout ou partie cette immobilisation >")]);
			$h .='</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[asset]" data-is-asset="1" data-index="'.$index.'" data-asset-link="asset">';
			$grants = $cAssetToLinkToGrant->makeArray(fn($e) => ['value' => $e['id'], 'label' => s("{description} / date d'acquisition : {date} / valeur : {amount}", [
				'description' => $e['description'],
				'amount' => \util\TextUi::money($e['value']),
				'date' => \util\DateUi::numeric($e['acquisitionDate'])
				])]);
				$h .= $form->select('asset'.$suffix.'[asset]', $grants, NULL, ['placeholder' => s("< Choisir l'immobilisation amortissable >")]);
			$h .='</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[acquisitionDate]" data-is-asset="1" data-index="'.$index.'">';
				$h .= $form->date('asset'.$suffix.'[acquisitionDate]', '', ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
			$h .='</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[startDate]" data-is-asset="1" data-index="'.$index.'">';
				$h .= $form->date('asset'.$suffix.'[startDate]', '', ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
			$h .= '</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[value]" data-is-asset="1" data-index="'.$index.'">';
				$h .= $form->inputGroup(
					$form->number(
						'asset'.$suffix.'[value]',
						'',
						[
							'min' => 0, 'step' => 0.01,
						]
					)
					.$form->addon('€ ')
				);
			$h .= '</div>';

			$h .= '<div class="operation-asset" data-wrapper="asset'.$suffix.'[duration]" data-is-asset="1" data-index="'.$index.'">';
				$h .= '<div>'.$form->number('asset'.$suffix.'[duration]', '').'</div>';
			$h .= '</div>';

			$h .= '<div data-wrapper="type'.$suffix.'">';
				$h .= $form->radios('type'.$suffix, self::p('type')->values, $defaultValues['type'] ?? '', [
						'data-index' => $index,
						'columns' => 2,
						'data-field' => 'type',
						'mandatory' => TRUE,
					]);
			$h .= '</div>';

			if($eFinancialYear['hasVat']) {

				$vatRateDefault = 0;
				if($eOperation['account']->exists() === TRUE) {
					if($eOperation['account']['vatRate'] !== NULL) {
						$vatRateDefault = $eOperation['account']['vatRate'];
					} else if($eOperation['account']['vatAccount']->exists() === TRUE) {
						$vatRateDefault = $eOperation['account']['vatAccount']['vatRate'];
					}
				}
				$vatAmountDefault = $vatRateDefault !== 0 ? round(($defaultValues['amount'] ?? 0) * $vatRateDefault / 100,2) : 0;

				$eOperation['vatRate'.$suffix] = '';

				$h .= '<div data-wrapper="vatRate'.$suffix.'">';
					$h .= $form->inputGroup(
						$form->addon(self::getAmountButtonIcons('vatRate', $index))
						.$form->number(
							'vatRate'.$suffix,
							$vatRateDefault,
							['data-index' => $index, 'data-field' => 'vatRate', 'data-vat-rate' => $form->getId(), 'min' => 0, 'max' => 20, 'step' => 0.1],
						)
						.$form->addon('% '))
						.\util\FormUi::info('<div data-index="'.$index.'" data-vat="account-info" class="hide">'.s("Le compte de TVA utilisé est le <b>{value}</b>. Si vous souhaitez en spécifier un autre, indiquez 0% ici et créez une autre écriture.", ['value' => '<span data-index="'.$index.'" data-vat="account-value"></span>']).'</div>'
						.'<div>'.s("Les taux de TVA habituels en vigueur en France sont : 20%, 10% et 5.5% (<link>en savoir plus</link>)", ['link' => '<a href="https://www.economie.gouv.fr/cedef/les-fiches-pratiques/quels-sont-les-taux-de-tva-en-vigueur-en-france-et-dans-lunion" target="_blank">']).'</div>')
					;
						$h .= '<div class="warning hide mt-1" data-vat-rate-warning data-index="'.$index.'">';
							$h .= s(
								"Attention : Habituellement, pour la classe <b>{class}</b> le taux de <b>{vatRate}%</b> est utilisé. Souhaitez-vous <link>l'utiliser</link> ?",
								[
									'vatRate' => '<span data-vat-rate-default data-index="'.$index.'"></span>',
									'class' => '<span data-vat-rate-class data-index="'.$index.'"></span>',
									'link' => '<a data-vat-rate-link data-index="'.$index.'">',
								],
							);
						$h .= '</div>';
				$h .= '</div>';

				$h .= '<div data-wrapper="vatValue'.$suffix.'">';
					$h .= $form->dynamicField($eOperation, 'vatValue'.$suffix, function($d) use($vatAmountDefault, $index) {
						$d->default = $vatAmountDefault ?? '';
						$d->attributes['min'] = 0;
						$d->attributes['step'] = 0.01;
						$d->attributes['data-field'] = 'vatValue';
						$d->attributes['data-index'] = $index;
						$d->prepend = OperationUi::getAmountButtonIcons('vatValue', $index);
					});
					$h .= '<div class="warning hide mt-1" data-vat-warning data-index="'.$index.'">';
						$h .= s(
							"Il y a une incohérence de calcul de TVA, souhaitiez-vous plutôt indiquer {amountVAT} ?",
							['amountVAT' => '<a onclick="Operation.updateVatValue('.$index.')" data-vat-warning-value data-index="'.$index.'"></a>'],
						);
					$h .= '</div>';
				$h .= '</div>';
			}

			if($isFromCashflow === FALSE) {

				$h .= '<div data-wrapper="paymentDate'.$suffix.'">';
					$h .= $form->date('paymentDate'.$suffix, $defaultValues['paymentDate'] ?? '', ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
				$h .= '</div>';

				$h .= '<div data-wrapper="paymentMethod'.$suffix.'">';
					$h .= $form->select(
						'paymentMethod'.$suffix,
						$cPaymentMethod,
						$defaultValues['paymentMethod'] ?? '',
						['mandatory' => $eFinancialYear->isCashAccounting()],
					);
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	private static function getCreateValidate(bool $hasVat): string {

		$h = '<div class="operation-create operation-create-validation">';

			$h .= '<h4></h4>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate"></div>';
			$h .= '<div class="cashflow-create-operation-validate cashflow-warning">';
				$h .= '<div>';
					$h .= '<span id="cashflow-allocate-difference-warning" class="warning hide">';
					$h .= s("⚠️ Différence de <span></span>", ['span' => '<span id="cashflow-allocate-difference-value">']);
					$h .= '</span>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div class="cashflow-create-operation-validate" data-field="amountIncludingVAT"><div><span>=</span><span data-type="value"></span></div></div>';
			$h .= '<div class="cashflow-create-operation-validate" data-field="amount"><div><span>=</span><span data-type="value"></span></div></div>';

			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1"><h4></h4></div>';
			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1" data-field="assetValue"><div><span>=</span><span data-type="value"></span></div></div>';
			$h .= '<div class="cashflow-create-operation-validate operation-asset" data-is-asset="1"></div>';

			$h .= '<div class="cashflow-create-operation-validate"></div>';

			if($hasVat) {
				$h .= '<div class="cashflow-create-operation-validate"></div>';
				$h .= '<div class="cashflow-create-operation-validate" data-field="vatValue"><div><span>=</span><span data-type="value"></span></div></div>';
			}

		$h .= '</div>';

		return $h;

	}

	public static function getCreateGrid(
		\farm\Farm $eFarm,
		Operation $eOperation,
		\account\FinancialYear $eFinancialYear,
		int $index,
		\util\FormUi $form,
		array $defaultValues,
		array $assetData,
		\Collection $cPaymentMethod,
	): string {

		$suffix = '['.$index.']';
		$isFromCashflow = ($eOperation['cOperationCashflow'] ?? new \Collection())->notEmpty();

		$h = '<div id="operation-create-list" class="operation-create-several-container" data-columns="1" data-cashflow="'.($isFromCashflow ? '1' : '0').'">';

			$h .= self::getCreateHeader($eFinancialYear, $isFromCashflow);
			$h .= self::getFieldsCreateGrid($eFarm, $form, $eOperation, $eFinancialYear, $suffix, $defaultValues, [], $assetData, $cPaymentMethod);

			if($isFromCashflow === TRUE) {
				$h .= self::getCreateValidate($eFinancialYear['hasVat']);
			}

		$h .= '</div>';

		return $h;

	}

	public static function url(\farm\Farm $eFarm, Operation $eOperation): string {

		return \company\CompanyUi::urlJournal($eFarm).'/operations?id='.$eOperation['id'];

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

	public static function getShortJournal(\farm\Farm $eFarm, string $journalCode, bool $link): string {

		$code = self::p('journalCode')->shortValues[$journalCode];

		$journalName = match($journalCode) {
			Operation::ACH => s("Journal des achats"),
			Operation::VEN => s("Journal des ventes"),
			Operation::KS => s("Journal de caisse"),
			Operation::BAN => s("Journal de banque"),
			Operation::OD => s("Journal des opérations diverses"),
		};

		if($link) {
			return '<a class="btn btn-xs journal-button journal-'.$journalCode.'-button" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?code='.$journalCode.'" title="'.s("Filtrer sur le {value}", mb_strtolower($journalName)).'">'.$code.'</a>';
		}

		return '<span class="btn btn-xs journal-button journal-'.$journalCode.'-button" title="'.$journalName.'">'.$code.'</span>';

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
			'account' => s("Classe de compte"),
			'accountLabel' => s("Compte"),
			'date' => s("Date de l'opération"),
			'description' => s("Libellé"),
			'document' => s("Pièce comptable"),
			'documentDate' => s("Date de la pièce comptable"),
			'amount' => s("Montant (HT)"),
			'type' => s("Type (débit / crédit)"),
			'thirdParty' => s("Tiers"),
			'comment' => s("Commentaire"),
			'paymentMethod' => s("Mode de paiement"),
			'paymentDate' => s("Date de paiement"),
			'vatRate' => s("Taux de TVA"),
			'vatValue' => s("Valeur de TVA"),
			'journalCode' => s("Journal"),
		]);

		switch($property) {

			case 'documentDate' :
			case 'paymentDate' :
			case 'date' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'type':
				$d->values = [
					OperationElement::DEBIT => s("Débit"),
					OperationElement::CREDIT => s("Crédit"),
				];
				break;

			case 'journalCode':
				$d->values = [
					OperationElement::ACH => s("Achats"),
					OperationElement::VEN => s("Ventes"),
					OperationElement::BAN => s("Banque"),
					OperationElement::KS => s("Caisse"),
					OperationElement::OD => s("Opérations diverses"),
				];
				$d->shortValues = [
					OperationElement::ACH => s("JA"),
					OperationElement::VEN => s("JV"),
					OperationElement::BAN => s("JB"),
					OperationElement::KS => s("JC"),
					OperationElement::OD => s("JOD"),
				];
				$d->field = 'select';

				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() and ($e['cOperationLinked'] ?? new \Collection())->notEmpty()) ? \util\FormUi::info(s("Cette modification sera également reportée sur les opérations liées")) : '';

				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				new \account\AccountUi()->query($d, GET('farm', '?int'));
				break;

			case 'accountLabel':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'accountLabel'];
				new \account\AccountUi()->queryLabel($d, GET('farm', '?int'), query: GET('query'));
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
				$d->after = function(\util\FormUi $form, Operation $e, string $property, string $field, array $attributes) {
					return $form->hidden('thirdPartyVatNumber['.($attributes['data-index'] ?? 0).']')
						.$form->hidden('thirdPartyName['.($attributes['data-index'] ?? 0).']');
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', '?int'));
				break;

			case 'document':
				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() and ($e['cOperationLinked'] ?? new \Collection())->notEmpty()) ? \util\FormUi::info(s("Cette modification sera également reportée sur les opérations liées")) : '';
				break;

			case 'comment' :
				$d->attributes['data-limit'] = 250;
				break;

			case 'paymentMethod' :

				$d->values = fn(Operation $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->attributes = function(\util\FormUi $form, Operation $eOperation) use($property) {
					if($eOperation['financialYear']->isCashAccounting()) {
						return ['mandatory' => TRUE];
					}
					return [];
				};
				$d->before = fn(\util\FormUi $form, $e) => ($e->isQuick() and ($e['cOperationLinked'] ?? new \Collection())->notEmpty()) ? \util\FormUi::info(s("Cette modification sera également reportée sur les opérations liées")) : '';
				break;

		}

		return $d;

	}

}

?>
