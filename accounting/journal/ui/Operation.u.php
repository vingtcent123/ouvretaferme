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

	public function createPayment(\farm\Farm $eFarm, Operation $eOperation, \Collection $cBankAccount): \Panel {

		\Asset::css('journal', 'operation.css');
		\Asset::js('journal', 'payment.js');
		\Asset::js('account', 'thirdParty.js');

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/operation:doCreatePayment',
			[
				'id' => 'journal-operation-create-payment',
				'third-party-create-index' => 0,
				'class' => 'panel-dialog container',
			],
		);

		$h = $form->hidden('farm', $eFarm['id']);

		$h .= $form->group(
			s("Type de paiement").\util\FormUi::asterisk(),
			'<div class="form-control field-radio-group payment-type-radio">'
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
		$h .= $form->dynamicGroups($eOperation, ['paymentDate*']);

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
				'<div class="util-info">'.s("Note : Les opérations non lettrées sont lettrées automatiquement par date croissante d'écriture (de la plus ancienne à la plus récente).").'</div><div id="waiting-operations-list"></div>'
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
			footer: '<div class="create-operation-buttons">'.$saveButton.'</div>',
		);

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
				'class' => 'panel-dialog container',
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

				$h .= $form->hidden('company', $eFarm['id']);
				$h .= $form->hidden('financialYear', $eFinancialYear['id']);

				$index = 0;
				$defaultValues = $eOperation->getArrayCopy();

				$h .= self::getCreateGrid($eFarm, $eOperation, $eFinancialYear, $index, $form, $defaultValues, $assetData, $cPaymentMethod);

				$h .= '<div class="invoice-preview hide">';
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

		$footer = '<div class="create-operation-buttons">'
			.'<div class="import-invoice-button">';

				$footer .= '<label class="btn btn-outline-secondary" onclick="Operation.openInvoiceFileForm();">'
						.s("Importer une facture")
					.'</label>';

			$footer .= '</div>'
			.'<div class="create-operation-button-add">'.$addButton.$saveButton.'</div>'
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

	private static function getCreateHeader(\farm\Farm $eFarm, bool $isFromCashflow): string {

		$h = '<div class="create-operation create-operation-headers">';

			$h .= '<h4>&nbsp;</h4>';
			$h .= '<div class="create-operation-header">'.self::p('date')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('document')->label.'</div>';
			$h .= '<div class="create-operation-header">'.self::p('thirdParty')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('account')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('accountLabel')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('description')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('comment')->label.'</div>';
			$h .= '<div class="create-operation-header">'.s("Montant TTC").'</div>';
			$h .= '<div class="create-operation-header">'.self::p('amount')->label.' '.\util\FormUi::asterisk().'</div>';

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

			$h .= '<div class="create-operation-header">'.self::p('type')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header">'.self::p('vatRate')->label.' '.\util\FormUi::asterisk().'</div>';
			$h .= '<div class="create-operation-header" data-wrapper="vatValue">'.self::p('vatValue')->label.' '.\util\FormUi::asterisk().'</div>';

			if($isFromCashflow === FALSE) {

				$mandatory = $eFarm['company']->isCashAccounting() ? ' '.\util\FormUi::asterisk() : '';
				$h .= '<div class="create-operation-header">'.self::p('paymentDate')->label.$mandatory.'</div>';
				$h .= '<div class="create-operation-header">'.self::p('paymentMode')->label.$mandatory.'</div>';

			}

		$h .= '</div>';

		return $h;

	}

	private static function getAmountButtonIcons(string $type, int $index): string {

		$activeIcon = match($type) {
			'amountIncludingVAT' => 'lock',
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
		$isFromCashflow = (isset($defaultValues['cashflow']) and $defaultValues['cashflow']->exists() === TRUE);

		$cAssetGrant = $assetData['grant'] ?? new \Collection();
		$cAssetToLinkToGrant = $assetData['asset'] ?? new \Collection();

		if($eFarm['company']->isAccrualAccounting() and $index > 0) {
			$disabled[] = 'thirdParty';
		}

		$h = '<div class="create-operation" data-index="'.$index.'">';
			$h .= '<div class="create-operation-title">';
				$h .= '<h4>'.s("Écriture #{number}", ['number' => $index + 1]).'</h4>';

					$h .= '<div class="create-operation-actions">';
						if($isFromCashflow === TRUE) {

							$h .= '<div class="create-operation-magic" data-index="'.$index.'">';
								$h .= '<a onclick="Cashflow.recalculate('.$index.')" class="btn btn-outline-primary" title="'.s("Réinitialiser par rapport aux autres écritures").'">'.\Asset::icon('magic').'</a>';
							$h .= '</div>';

						}
					$h .= '<div class="create-operation-delete hide" data-index="'.$index.'">';
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
						'data-accounting-type' => $eFarm['company']['accountingType'],
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
							'disabled' => TRUE,
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
					.$form->addon('% '));
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

			if($isFromCashflow === FALSE) {

				$h .= '<div data-wrapper="paymentDate'.$suffix.'">';
					$h .= $form->date('paymentDate'.$suffix, $defaultValues['paymentDate'] ?? '', ['min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
				$h .= '</div>';

				$h .= '<div data-wrapper="paymentMethod'.$suffix.'">';
					$h .= $form->select(
						'paymentMethod'.$suffix,
						$cPaymentMethod,
						$defaultValues['paymentMethod'] ?? '',
						['mandatory' => $eFarm['company']->isCashAccounting()],
					);
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	private static function getCreateValidate(): string {

		$h = '<div class="create-operation create-operation-validation">';

			$h .= '<h4></h4>';
			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate cashflow-warning">';
				$h .= '<div>';
					$h .= '<span id="cashflow-allocate-difference-warning" class="warning hide">';
					$h .= s("⚠️ Différence de <span></span>", ['span' => '<span id="cashflow-allocate-difference-value">']);
					$h .= '</span>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div class="create-operation-validate" data-field="amountIncludingVAT"><div><span>=</span><span data-type="value"></span></div></div>';
			$h .= '<div class="create-operation-validate" data-field="amount"><div><span>=</span><span data-type="value"></span></div></div>';

			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1"><h4></h4></div>';
			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1"></div>';
			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1" data-field="assetValue"><div><span>=</span><span data-type="value"></span></div></div>';
			$h .= '<div class="create-operation-validate operation-asset" data-is-asset="1"></div>';

			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate"></div>';
			$h .= '<div class="create-operation-validate" data-field="vatValue"><div><span>=</span><span data-type="value"></span></div></div>';

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
		$isFromCashflow = ($defaultValues['cashflow']->exists() === TRUE);

		$h = '<div id="create-operation-list" class="create-operations-container" data-columns="1" data-cashflow="'.($isFromCashflow ? '1' : '0').'">';

			$h .= self::getCreateHeader($eFarm, $isFromCashflow);
			$h .= self::getFieldsCreateGrid($eFarm, $form, $eOperation, $eFinancialYear, $suffix, $defaultValues, [], $assetData, $cPaymentMethod);

			if($isFromCashflow === TRUE) {
				$h .= self::getCreateValidate();
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
			'paymentMode' => s("Mode de paiement"),
			'paymentMethod' => s("Mode de paiement"),
			'paymentDate' => s("Date de paiement"),
			'vatRate' => s("Taux de TVA"),
			'vatValue' => s("Valeur de TVA"),
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
					OperationElement::BAN => s("Trésorerie"),
					OperationElement::OD => s("Opérations diverses"),
				];
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
				$d->before = fn(\util\FormUi $form, $e) => $e->isQuick() && (int)substr($e['accountLabel'], 0, 3) !== \Setting::get('account\vatClass') ? \util\FormUi::info(s("Attention, pensez à répercuter ce changement sur la ligne de TVA si elle existe")) : '';
				break;


			case 'thirdParty':
				$d->autocompleteBody = function(\util\FormUi $form, Operation $e) {
					return [
					];
				};
				$d->after = function(\util\FormUi $form, Operation $e, string $property, string $field, array $attributes) {
					return $form->hidden('thirdPartyVatNumber['.$attributes['data-index'].']')
						.$form->hidden('thirdPartyName['.$attributes['data-index'].']');
				};
				new \account\ThirdPartyUi()->query($d, GET('farm', '?int'));
				break;

			case 'document':
				break;

			case 'comment' :
				$d->attributes['data-limit'] = 250;
				break;

			case 'paymentMethod' :

				$d->values = fn(Operation $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->attributes = function(\util\FormUi $form, Operation $eOperation) use($property) {
					if($eOperation['farm']['company']->isCashAccounting()) {
						return ['mandatory' => TRUE];
					}
					return [];
				};
				break;

			case 'paymentMode' :
				$d->values = [
					OperationElement::CREDIT_CARD => s("Carte bancaire"),
					OperationElement::CHEQUE => s("Chèque bancaire"),
					OperationElement::CASH => s("Espèces"),
					OperationElement::TRANSFER => s("Virement bancaire"),
					OperationElement::DIRECT_DEBIT => s("Prélèvement"),
				];
				break;

		}

		return $d;

	}

}

?>
