<?php
namespace farm;

class ConfigurationUi {

	public function __construct() {

		\Asset::css('farm', 'configuration.css');
		\Asset::js('farm', 'configuration.js');

	}

	public function update(\farm\Farm $eFarm, \Collection $cAccount): string {

		if($eFarm->hasAccounting() === FALSE) {
			return $this->updateSettings($eFarm);
		}

		$h = '<div class="tabs-h" id="selling-configure" onrender="'.encode('Lime.Tab.restore(this, "settings")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="settings" onclick="Lime.Tab.select(this)">'.s("Administratif").'</a>';
				if($eFarm->hasAccounting()) {
					$h .= '<a class="tab-item tab-item-accounting" data-tab="accounting" onclick="Lime.Tab.select(this)">'.s("Comptabilité").'</a>';
				}
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="settings">';
				$h .= $this->updateSettings($eFarm);
			$h .= '</div>';

			if($eFarm->hasAccounting()) {
				$h .= '<div class="tab-panel" data-tab="accounting">';
					$h .= $this->updateAccounting($eFarm, $cAccount);
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function updateVatNumberPanel(\farm\Farm $eFarm): \Panel {

		$eConfiguration = $eFarm->conf();

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/configuration:doUpdateVatNumber', ['autocomplete' => 'off', 'class' => 'farm-vat-number-form']);

			$h .= '<div class="util-info">';
				$h .= s("Votre numéro de TVA intracommunautaire est nécessaire pour accéder à cette page.");
			$h .= '</div>';

			$h .= $form->hidden('id', $eConfiguration['id']);
			$h .= $form->hidden('isFromInvoicing', '1');

			$h .= $form->dynamicGroup($eConfiguration, 'vatNumber');

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();


		return new \Panel(
			id: 'panel-vat-number-update',
			title: s("Paramétrer le numéro de TVA intracommunautaire"),
			body: $h
		);

	}

	public function updateSettings(\farm\Farm $eFarm): string {

		$eConfiguration = $eFarm->conf();

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/farm/configuration:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->group(
					\farm\FarmUi::p('logo')->label,
					new \media\FarmLogoUi()->getCamera($eFarm, size: '15rem')
				);
				$h .= '<br/>';
				$h .= $form->group(content: '<h3>'.s("TVA").'</h3>');
				$h .= $form->dynamicGroup($eConfiguration, 'hasVat', function(\PropertyDescriber $d) {
					$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'Configuration.changeHasVat(this)'];
				});
				$h .= $form->dynamicGroup($eConfiguration, 'vatNumber', $eConfiguration['hasVat'] ? NULL : function(\PropertyDescriber $d) {
					$d->group['class'] = 'hide';
				});
				$h .= $form->dynamicGroup($eConfiguration, 'defaultVat', $eConfiguration['hasVat'] ? NULL : function(\PropertyDescriber $d) {
					$d->group['class'] = 'hide';
				});
				$h .= $form->dynamicGroup($eConfiguration, 'defaultVatShipping', $eConfiguration['hasVat'] ? NULL : function(\PropertyDescriber $d) {
					$d->group['class'] = 'hide';
				});
				$h .= $form->group(content: '<h3>'.s("Certification").'</h3>');
				$h .= $form->dynamicGroups($eConfiguration, ['organicCertifier']);
				$h .= $form->group(content: '<h3>'.s("Autres").'</h3>');
				$h .= $form->dynamicGroups($eConfiguration, ['saleClosing', 'documentCopy', 'paymentMode', 'pdfNaturalOrder', 'marketSaleDefaultDecimal']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateOrderForm(\farm\Farm $eFarm, \selling\Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->conf();

		$form = new \util\FormUi();

		$h = '';

		if($eFarm->isLegal() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= \farm\AlertUi::getError('Farm::notLegal', [
					'farm' => $eFarm,
					'btn' => 'btn-secondary'
				]);
			$h .= '</div>';

			return $h;

		}

		$h .= '<div class="mb-2">';
			$h .= $form->openAjax('/farm/configuration:doUpdateOrderForm', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->dynamicGroups($eConfiguration, ['documentTarget', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();
		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des devis par e-mail").'</h2>';

		$h .= '<div class="mb-2">';
			$h .= $this->updateDocumentMail('order-form', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<h2>'.s("Exemple de devis").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, \selling\Pdf::ORDER_FORM, $eSaleExample->getOrderForm($eFarm), $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateVat(\farm\Farm $eFarm): string {

		$eConfiguration = $eFarm->conf();
		\Asset::js('account', 'vat.js');

		if($eFarm['eFinancialYear']->notEmpty()) {
			$date = $eFarm['eFinancialYear']['startDate'];
		} else {
			$date = date('Y-01-01');
		}

		$form = new \util\FormUi();

		$h = '<div class="mb-2">';
			$h .= $form->openAjax('/farm/configuration:doUpdateVat', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->dynamicGroup($eConfiguration, 'hasVatAccounting', function(\PropertyDescriber $d) use ($eConfiguration) {
					$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'Vat.changeHasVat(this); Vat.toggleEffectiveAt(this, '.$eConfiguration
						['hasVatAccounting'].')'];
				});
				$eConfigurationHistory = new ConfigurationHistory();
				$h .= $form->dynamicGroup($eConfigurationHistory, 'effectiveAt', function(\PropertyDescriber $d) use($date) {
					$d->group['class'] = 'hide';
					$d->default = $date;
				});
				$h .= $form->dynamicGroups($eConfiguration, ['vatChargeability', 'vatFrequency']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();
		$h .= '</div>';

		return $h;

	}
	public function updateInvoiceMention(Farm $eFarm): \Panel {

		return new \Panel(
			id: 'panel-farm-update-invoice-mentions',
			title: s("Informations requises pour continuer"),
			body: $this->getInvoiceMentionForm($eFarm)
		);

	}

	public function getInvoiceMentionForm(Farm $eFarm): string {

		$eConfiguration = $eFarm->conf();
		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/configuration:doUpdateInvoiceMention', ['autocomplete' => 'off', 'class' => 'farm-update-invoice-mention']);

			$h .= '<div class="util-info">';
				$h .= s("Nous avons besoin que vous indiquiez les mentions obligatoires de vos factures pour accéder à cette page.");
				$h .= '<br/>'.s("La conformité réglementaire de Ouvretaferme n'est assurée que pour la FRANCE.");
			$h .= '</div>';

			$h .= $form->hidden('id', $eConfiguration['id']);
			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eConfiguration, ['invoiceMandatoryTexts', 'invoiceCollection', 'invoiceLateFees', 'invoiceDiscount']);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateInvoice(\farm\Farm $eFarm, \selling\Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->conf();

		$form = new \util\FormUi();

		$h = '<div class="mb-2">';
			$h .= $form->openAjax('/farm/configuration:doUpdateInvoice', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->group(
					'',
					'<h3>'.s("Mentions obligatoires").'</h3>',
				);
				$h .= $form->dynamicGroups($eConfiguration, ['invoiceMandatoryTexts*', 'invoiceCollection', 'invoiceLateFees', 'invoiceDiscount']);

				$h .= $form->group(
					'',
				'<h3>'.s("Paiements").'</h3>',
				);

				$h .= $form->group(
					self::p('invoiceDue')->label,
					$this->getInvoiceDueField($form, $eConfiguration),
					['wrapper' => 'invoiceDue invoiceDueDays invoiceDueMonth']
				);
				$h .= $form->dynamicGroups($eConfiguration, ['invoiceReminder', 'invoicePaymentCondition']);

				$h .= $form->group(
					'',
				'<h3>'.s("Textes supplémentaires").'</h3>',
				);

				$h .= $form->dynamicGroups($eConfiguration, ['invoiceHeader', 'invoiceFooter']);

				$eConfiguration['documentInvoices']++;

				$h .= '<div class="util-block-info">';
					$h .= '<h4>'.S("Personnaliser la nomenclature des factures").'</h4>';
					$h .= '<p>'.s("La loi interdit de supprimer une facture et les documents comptables doivent être tenus sans altération d’aucune sorte. L’administration fiscale a besoin de vérifier la bonne continuité de votre numérotation de facturation. Un trou dans la numérotation constitue une infraction fiscale.").'</p>';
					$h .= '<p>'.s("Si vous intégrez dans le préfixe de numérotation des factures l'année en cours, Ouvretaferme se chargera chaque 1<sup>er</sup>
 janvier de mettre la nouvelle année et recommencer la numérotation à 1.").'</p>';
					$h .= $form->dynamicGroups($eConfiguration, ['invoicePrefix', 'documentInvoices']);
				$h .= '</div>';

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();
		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des factures par e-mail").'</h2>';

		$h .= '<div class="mb-2">';
			$h .= $this->updateDocumentMail('invoice', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des relances de paiement par e-mail").'</h2>';

		$h .= '<div class="mb-2">';
			$h .= $this->updateDocumentMail('reminder', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<h2>'.s("Exemple de facture").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, \selling\Pdf::INVOICE, $eSaleExample['invoice']['number'], $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	protected function getInvoiceDueField(\util\FormUi $form, Configuration $e): string {

		$h = '<div class="configuration-field-due">';
			$h .= '<div>';
				$h .= $form->dynamicField($e, 'invoiceDue');
			$h .= '</div>';
			$h .= '<div class="configuration-field-due-date">';
				$h .= $form->dynamicField($e, 'invoiceDueDays');
				$h .= $form->dynamicField($e, 'invoiceDueMonth');
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateAccounting(\farm\Farm $eFarm, \Collection $cAccount): string {

		if($eFarm->hasAccounting() === FALSE) {
			return '<div class="util-block-help">'.s("Activez le <link>module de comptabilité</link> pour vous servir de cette fonctionnalité !", ['link' => '<a href="'.\company\CompanyUi::urlSettings($eFarm).'">']).'</div>';
		}

		$eConfiguration = $eFarm->conf();

		$profiles = \selling\ProductUi::p('profile')->values;


		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/configuration:doUpdateProfileAccount', ['id' => 'farm-update-profile', 'autocomplete' => 'off']);

		$h .= $form->hidden('id', $eConfiguration['id']);

		$h .= $form->group(
			content: '<h3>'.s("Numéros de compte par défaut").'</h3>'.
			'<div class="util-info">'.s("Ces numéros de compte seront automatiquement attribués aux futures produits que vous créerez.").'</div>'
		);


		foreach($profiles as $key => $profile) {

			$e = new \selling\Product(['farm' => $eFarm]);
			if(($eConfiguration['profileAccount'][$key]['privateAccount'] ?? '') !== '') {
				$e['privateAccount'] = $cAccount->offsetGet($eConfiguration['profileAccount'][$key]['privateAccount']);
			}
			if(($eConfiguration['profileAccount'][$key]['proAccount'] ?? '') !== '') {
				$e['proAccount'] = $cAccount->offsetGet($eConfiguration['profileAccount'][$key]['proAccount']);
				$hasProAccount = TRUE;
			} else {
				$hasProAccount = FALSE;
			}

			$dissociation = '<div class="form-info">'.$form->checkbox('accountDissociation', '1', [
				'callbackLabel' => fn($input) => $input.' '.s("Dissocier le numéro de compte pour la vente aux particuliers et aux professionnels"),
				'onclick' => 'Configuration.accountDissociation(this)',
				'checked' => $hasProAccount
			]).'</div>';

			$label = $profile.\util\FormUi::info(s("Vente aux clients particuliers"), $hasProAccount ? '' : 'hide');

			$h .= $form->group(
				$label,
				$form->dynamicField($e, 'privateAccount', function($d) use($key, $eConfiguration) {
					$d->name = 'profileAccount['.$key.'][privateAccount]';
				}).$dissociation
			);

			$label = $profile.\util\FormUi::info(s("Vente aux clients professionnels"));
			$h .= $form->group(
				$label,
				$form->dynamicField($e, 'proAccount', function($d) use($key, $eConfiguration) {
					$d->name = 'profileAccount['.$key.'][proAccount]';
				}),
				$hasProAccount ? [] : ['class' => 'hide']
			);

		}

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return $h;
	}

	public function updateDeliveryNote(\farm\Farm $eFarm, \selling\Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->conf();

		$h = '<div class="mb-2">';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/farm/configuration:doUpdateDeliveryNote', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->dynamicGroups($eConfiguration, ['documentTarget', 'deliveryNoteHeader', 'deliveryNoteFooter']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();

		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des bons de livraison par e-mail").'</h2>';

		$h .= '<div class="mb-2">';
			$h .= $this->updateDocumentMail('delivery-note', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h2>'.s("Exemple de bon de livraison").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, \selling\Pdf::DELIVERY_NOTE, $eSaleExample->getDeliveryNote($eFarm), $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateDocumentMail(string $type, \farm\Farm $eFarm, \selling\Sale $eSaleExample, \Collection $cCustomize): string {

		$customizePrivate = match($type) {
			'order-form' => \mail\Customize::SALE_ORDER_FORM_PRIVATE,
			'delivery-note' => \mail\Customize::SALE_DELIVERY_NOTE_PRIVATE,
			'invoice' => \mail\Customize::SALE_INVOICE_PRIVATE,
			'reminder' => \mail\Customize::SALE_REMINDER_PRIVATE,
		};

		$customizePro = match($type) {
			'order-form' => \mail\Customize::SALE_ORDER_FORM_PRO,
			'delivery-note' => \mail\Customize::SALE_DELIVERY_NOTE_PRO,
			'invoice' => \mail\Customize::SALE_INVOICE_PRO,
			'reminder' => \mail\Customize::SALE_REMINDER_PRO,
		};

		$h = '<div class="tabs-h mb-3" id="selling-configure-mail-'.$type.'" onrender="'.encode('Lime.Tab.restore(this, "private")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item" data-tab="private" onclick="Lime.Tab.select(this)">';
					$h .= s("Aux particuliers");
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="pro" onclick="Lime.Tab.select(this)">';
					$h .= s("Aux professionnels");
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div data-tab="private" class="tab-panel selected">';

				$eCustomize = $cCustomize[$customizePrivate] ?? new \mail\Customize();

				$h .= '<div class="util-title">';
					$h .= '<div>';
						$h .= '<p class="util-info mb-0">';
							$h .= match($type) {
								'order-form' => s("Cet e-mail est envoyé lorsque vous envoyez un devis par e-mail à un client particulier."),
								'delivery-note' => s("Cet e-mail est envoyé lorsque vous envoyez un bon de livraison par e-mail à un client particulier."),
								'invoice' => s("Cet e-mail est envoyé lorsque vous envoyez une facture par e-mail à un client particulier."),
								'reminder' => s("Cet e-mail est envoyé lorsque vous envoyez une relance de paiement à un client particulier."),
							};
						$h .= '</p>';
					$h .= '</div>';
					$h .= '<div>';
						$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.$customizePrivate.'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
						if($eCustomize->notEmpty()) {
							$h .= ' <a data-ajax="/mail/customize:doDelete" post-id="'.$eCustomize['id'].'" data-confirm="'.s("Revenir sur le titre et le contenu par défaut ?").'" class="btn btn-outline-primary">'.s("Réinitialiser").'</a>';
						}
					$h .= '</div>';
				$h .= '</div>';

				$h .= $this->getMailExample($type, $eFarm, $eSaleExample, $customizePrivate, $eCustomize->empty() ? NULL : $eCustomize['template']);

			$h .= '</div>';

			$h .= '<div data-tab="pro" class="tab-panel">';

				$eCustomize = $cCustomize[$customizePro] ?? new \mail\Customize();

				if($eCustomize->notEmpty()) {

					$h .= '<div class="util-title">';
						$h .= '<div>';
							$h .= '<p class="util-info mb-0">';
								$h .= match($type) {
									'order-form' => s("Cet e-mail est envoyé lorsque vous envoyez un devis par e-mail à un client professionnel."),
									'delivery-note' => s("Cet e-mail est envoyé lorsque vous envoyez un bon de livraison par e-mail à un client professionnel."),
									'invoice' => s("Cet e-mail est envoyé lorsque vous envoyez une facture par e-mail à un client professionnel."),
									'reminder' => s("Cet e-mail est envoyé lorsque vous envoyez une relance de paiement à un client professionnel."),
								};
							$h .= '</p>';
						$h .= '</div>';
						$h .= '<div>';
							$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.$customizePro.'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
							if($eCustomize->notEmpty()) {
								$h .= ' <a data-ajax="/mail/customize:doDelete" post-id="'.$eCustomize['id'].'" data-confirm="'.s("Revenir sur le titre et le contenu par défaut ?").'" class="btn btn-outline-primary">'.s("Réinitialiser").'</a>';
							}
						$h .= '</div>';
					$h .= '</div>';

				} else {

					$h .= '<div class="util-block-help">';
						$h .= '<p>';
							$h .= match($type) {
								'order-form' => s("Vous n'avez pas personnalisé l'e-mail pour les devis des clients professionnels. Vos clients professionnels recevront l'e-mail configuré pour les clients particuliers."),
								'delivery-note' => s("Vous n'avez pas personnalisé l'e-mail pour les bons de livraison des clients professionnels. Vos clients professionnels recevront l'e-mail configuré pour les clients particuliers."),
								'invoice' => s("Vous n'avez pas personnalisé l'e-mail pour les factures des clients professionnels. Vos clients professionnels recevront l'e-mail configuré pour les clients particuliers."),
								'reminder' => s("Vous n'avez pas personnalisé l'e-mail pour les relances de paiement aux clients professionnels. Vos clients professionnels recevront l'e-mail configuré pour les clients particuliers."),
							};
						$h .= '</p>';
						$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.$customizePro.'" class="btn btn-secondary">'.s("Personnaliser l'e-mail").'</a>';
					$h .= '</div>';

				}

				if($eCustomize->notEmpty()) {

					$h .= $this->getMailExample($type, $eFarm, $eSaleExample, $customizePro, $eCustomize['template']);


				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getMailExample(string $type, \farm\Farm $eFarm, \selling\Sale $eSaleExample, string $customize, ?string $template): string {

		switch($type) {

			case 'order-form' :
				[$title, , $html] = new \selling\PdfUi()->getOrderFormMail($eFarm, $eSaleExample, $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

			case 'delivery-note' :
				[$title, , $html] = new \selling\PdfUi()->getDeliveryNoteMail($eFarm, $eSaleExample, $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

			case 'invoice' :
				[$title, , $html] = new \selling\PdfUi()->getInvoiceMail($eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]), $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

			case 'reminder' :
				[$title, , $html] = new \selling\PdfUi()->getReminderMail($eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]), $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

		}

	}

	public function getInvoiceMention(string $type): string {

		return match($type) {
			'collection' => s("En cas de retard, une indemnité forfaitaire pour frais de recouvrement de 40 € sera exigée."),
			'lateFees' => s("Tout retard de paiement engendre une pénalité exigible à compter de la date d'échéance, calculée sur la base de trois fois le taux d'intérêt légal."),
			'discount' => s("Les règlements reçus avant la date d'échéance ne donneront pas lieu à escompte."),
			default => throw new \Exception('unknown type'),
		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Configuration::model()->describer($property, [
			'documentInvoices' => s("Prochain numéro de facture ou d'avoir"),
			'documentTarget' => s("Permettre l'édition de devis et de bons de livraison"),
			'hasVat' => s("Êtes-vous redevable de la TVA ?"),
			'hasVatAccounting' => s("Êtes-vous redevable de la TVA ?"),
			'vatFrequency' => s("Fréquence de déclaration de TVA"),
			'vatChargeability' => s("Exigibilité de la TVA"),
			'vatNumber' => s("Numéro de TVA intracommunautaire"),
			'defaultVat' => s("Taux de TVA par défaut sur vos produits"),
			'defaultVatShipping' => s("Taux de TVA par défaut sur les frais de livraison"),
			'organicCertifier' => s("Organisme de certification pour l'Agriculture Biologique"),
			'paymentMode' => s("Moyens de paiement affichés sur les devis et les factures"),
			'deliveryNoteHeader' => s("Ajouter un texte personnalisé affiché en haut des bons de livraison"),
			'deliveryNoteFooter' => s("Ajouter un texte personnalisé affiché en bas des bons de livraison"),
			'orderFormDelivery' => s("Afficher la date de livraison de la commande sur les devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement affichées sur les devis"),
			'orderFormHeader' => s("Ajouter un texte personnalisé affiché en haut des devis"),
			'orderFormFooter' => s("Ajouter un texte personnalisé affiché en bas des devis"),
			'invoicePrefix' => s("Préfixe pour la numérotation des factures"),
			'invoiceDue' => s("Date d'échéance par défaut sur les factures"),
			'invoiceReminder' => s("Autoriser les relances sur les factures non payées"),
			'invoicePaymentCondition' => s("Conditions de paiement affichées sur les factures"),
			'invoiceHeader' => s("Ajouter un texte personnalisé affiché en haut des factures"),
			'invoiceFooter' => s("Ajouter un texte personnalisé affiché en bas des factures"),
			'invoiceMandatoryTexts' => s("Indiquer les mentions obligatoires sur les factures"),
			'invoiceCollection' => s("Mention relative aux frais de recouvrement"),
			'invoiceLateFees' => s("Mention relative aux pénalités de retard"),
			'invoiceDiscount' => s("Mention relative à l’escompte ou à son absence"),
			'documentCopy' => s("Recevoir une copie sur l'adresse e-mail de la ferme des devis, bons de livraisons et factures que vous envoyez aux clients"),
			'saleClosing' => s("Clôture des ventes du logiciel de caisse"),
			'pdfNaturalOrder' => s("Trier les commandes et les étiquettes exportées en PDF pour faciliter la découpe"),
			'marketSaleDefaultDecimal' => s("Quel valeur voulez-vous saisir par défaut dans le logiciel de caisse pour les produits vendus au poids ?"),
		]);

		switch($property) {

			case 'vatNumber' :
				$d->palceholder = s("Ex. : {value}", 'FR01234567890');
				$d->after = fn(\util\FormUi $form, Configuration $e) => ($e->exists() === FALSE or \pdp\PdpLib::isActive($e['farm']) === FALSE) ? \util\FormUi::info(s("Indiquez ici un numéro de TVA intracommunautaire si vous souhaitez le voir apparaître sur les factures.")) : NULL;
				break;

			case 'hasVat' :
				$d->field = 'yesNo';
				$d->after = \util\FormUi::info(s("Le changement dans la redevabilité de la TVA n'est pris en compte que pour les ventes créées ultérieurement."));
				break;

			case 'hasVatAccounting' :
				$d->field = 'yesNo';
				break;

			case 'documentInvoices' :
				$d->labelAfter = \util\FormUi::info(s("Si le numéro indiqué est déjà utilisé, alors le système affectera le premier numéro suivant non utilisé."));
				break;

			case 'defaultVat' :
				$d->field = 'select';
				$d->values = function(Configuration $e) {
					return \selling\SaleUi::getVat($e['farm']);
				};
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

			case 'defaultVatShipping' :
				$d->field = 'select';
				$d->values = function(Configuration $e) {
					return \selling\SaleUi::getVat($e['farm']);
				};
				$d->attributes = [
					'placeholder' => s("Calculé automatiquement en fonction du taux de TVA des articles vendus")
				];
				break;

			case 'saleClosing' :
				$d->field = 'select';
				$d->attributes['mandatory'] = TRUE;
				$d->values = [
					7 => s("Clôture automatique 7 jours après la vente"),
					30 => s("Clôture automatique 30 jours après la vente"),
					60 => s("Clôture automatique 60 jours après la vente"),
					90 => s("Clôture automatique 90 jours après la vente"),
					365 => s("Clôture automatique 1 an après la vente"),
				];
				$d->after = \util\FormUi::info(s("Vous avez l'obligation légale de clôturer les ventes réalisées avec le logiciel de caisse. {siteName} le fait automatiquement avec un délai après la vente que vous choisissez."));
				break;

			case 'organicCertifier' :
				$d->placeholder = s("Ex. : {value}", 'FR-BIO-01');
				$d->after = \util\FormUi::info(s("Indiquez ici le code ISO de votre organisme certificateur pour le voir apparaître sur les bons de commande, bons de livraison et factures."));
				break;

			case 'paymentMode' :
				$d->placeholder = s("Ex. : Paiement en espèces ou par virement bancaire. Coordonnées bancaires : FR76 XXXX XXXX XXXX XXXX XXX");
				$d->after = \util\FormUi::info(s("Indiquez ici les moyens de paiement autorisés pour régler vos devis et factures."));
				break;

			case 'orderFormDelivery' :
			case 'documentCopy' :
				$d->field = 'yesNo';
				break;

			case 'pdfNaturalOrder' :
				$d->field = 'yesNo';
				$d->labelAfter = \util\FormUi::info(s("Les étiquettes devront être coupées empilées après impression et déposées l'une sur l'autre du coin haut gauche au coin bas droite des feuilles pour conserver le tri"));
				break;

			case 'marketSaleDefaultDecimal' :

				$d->values = [
					Configuration::NUMBER => s("Saisir la quantité par défaut"),
					Configuration::PRICE => s("Saisir le prix par défaut")
				];

				break;

			case 'documentTarget' :

				$d->values = [
					Configuration::ALL => s("Pour tous les clients"),
					Configuration::PRIVATE => s("Pour les clients particuliers"),
					Configuration::PRO => s("Pour les clients professionnels"),
					Configuration::DISABLED => s("Désactiver cette fonctionnalité"),
				];

				$d->field = 'select';
				$d->attributes['mandatory'] = TRUE;

				$h = s("L'édition des factures reste toujours disponible pour tous les clients.");
				$d->after = \util\FormUi::info($h);
				break;

			case 'orderFormPaymentCondition' :
				$d->placeholder = s("Ex. : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement données à vos clients après acceptation d'un devis."));
				break;

			case 'invoiceDue' :
				$d->field = 'yesNo';
				break;

			case 'invoiceDueDays' :
				$d->prepend = s("À date de facturation").'   '.\Asset::icon('plus');
				$d->append = s("jours");
				break;

			case 'invoiceReminder' :
				$d->append = s("jours après la date d'échéance");
				$d->after = \util\FormUi::info(s("Laissez vide pour ne pas relancer sur les factures non payées"));
				break;

			case 'invoiceDueMonth' :
				$d->attributes['callbackLabel'] = fn($input) => $input.'  '.s("Fin de mois");
				break;

			case 'invoicePrefix' :
				$d->placeholder = \selling\SellingSetting::INVOICE;
				break;

			case 'invoicePaymentCondition' :
				$d->placeholder = s("Ex. : Paiement à réception de facture.");
				$d->labelAfter = \util\FormUi::info(s("Indiquez ici les conditions de paiement données à vos clients pour régler vos factures."));
				break;

			case 'invoiceMandatoryTexts':
				$d->field = 'yesNo';
				$d->attributes['onchange'] = 'Configuration.changeInvoiceMandatoryTexts();';
				$d->after = fn(\util\FormUi $form, Configuration $e) => \util\FormUi::info(s("Vos factures ne seront pas conformes sans ces mentions !"), $e['invoiceMandatoryTexts'] ? 'hide' : '');
				break;

			case  'invoiceCollection':
			case  'invoiceLateFees':
			case  'invoiceDiscount':
				$d->group = fn(Configuration $e) => ($e['invoiceMandatoryTexts']) ? [] : ['class' => 'hide'];
				break;

			case 'vatFrequency' :
				$d->values = [
					Configuration::MONTHLY => s("Mensuelle"),
					Configuration::QUARTERLY => s("Trimestrielle"),
					Configuration::ANNUALLY => s("Annuelle"),
				];
				$d->attributes['mandatory'] = TRUE;
				$d->group = fn(Configuration $e) => ($e['hasVatAccounting'] ?? NULL) ? [] : ['class' => 'hide'];
				break;

			case 'vatChargeability' :
				$cash = '<h4>'.s("TVA sur les encaissements").'</h4>';
				$cash .= '<ul>';
					$cash .= '<li>'.s("La TVA est due dès l'encaissement.").'</li>';
					$cash .= '<li>'.s("Exigibilité par défaut pour les Régimes Simplifiés (Micro-BA, RSA...).").'</li>';
					$cash .= '<li>'.s("Les prestations de service entrent dans cette règle d'office.").'</li>';
				$cash .= '</ul>';

				$debit = '<h4>'.s("TVA sur les débits").'</h4>';
				$debit .= '<ul>';
					$debit .= '<li>'.s("La TVA est due dès l'émission d'une facture, sa date faisant foi.").'</li>';
				$debit .= '</ul>';

				$d->values = [
					Configuration::CASH => $cash,
					Configuration::DEBIT => $debit,
				];
				$d->attributes['mandatory'] = TRUE;
				$d->group = fn(Configuration $e) => ($e['hasVatAccounting'] ?? NULL) ? [] : ['class' => 'hide'];
				break;

		}

		return $d;

	}

}
?>
