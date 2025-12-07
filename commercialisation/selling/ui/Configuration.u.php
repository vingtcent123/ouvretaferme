<?php
namespace selling;

class ConfigurationUi {

	public function __construct() {

		\Asset::css('selling', 'configuration.css');
		\Asset::js('selling', 'configuration.js');

	}

	public static function getDefaultInvoicePrefix(): string {
		return s("FA");
	}

	public static function getDefaultDeliveryNotePrefix(): string {
		return s("BL");
	}

	public static function getDefaultOrderFormPrefix(): string {
		return s("DE");
	}

	public static function getDefaultCreditPrefix(): string {
		return s("AV");
	}

	public function update(\farm\Farm $eFarm, \Collection $cCustomize, Sale $eSaleExample, \Collection $cAccount): string {

		$h = '<div class="tabs-h" id="selling-configure" onrender="'.encode('Lime.Tab.restore(this, "settings")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="settings" onclick="Lime.Tab.select(this)">'.s("Administratif").'</a>';
				$h .= '<a class="tab-item" data-tab="orderForm" onclick="Lime.Tab.select(this)">'.s("Devis").'</a>';
				$h .= '<a class="tab-item" data-tab="deliveryNote" onclick="Lime.Tab.select(this)">'.s("Bons de livraisons").'</a>';
				$h .= '<a class="tab-item" data-tab="invoice" onclick="Lime.Tab.select(this)">'.s("Factures").'</a>';
				if(FEATURE_PRE_ACCOUNTING) {
					$h .= '<a class="tab-item tab-item-accounting" data-tab="accounting" onclick="Lime.Tab.select(this)">'.s("Comptabilité").'</a>';
				}
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="settings">';
				$h .= $this->updateSettings($eFarm);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="orderForm">';
				$h .= $this->updateOrderForm($eFarm, $eSaleExample, $cCustomize);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="deliveryNote">';
				$h .= $this->updateDeliveryNote($eFarm, $eSaleExample, $cCustomize);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="invoice">';
				$h .= $this->updateInvoice($eFarm, $eSaleExample, $cCustomize);
			$h .= '</div>';

			if(FEATURE_PRE_ACCOUNTING) {
				$h .= '<div class="tab-panel" data-tab="accounting">';
					$h .= $this->updateAccounting($eFarm, $cAccount);
				$h .= '</div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function updateSettings(\farm\Farm $eFarm): string {

		$eConfiguration = $eFarm->selling();

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/configuration:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

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
				$h .= $form->dynamicGroup($eConfiguration, 'invoiceVat', $eConfiguration['hasVat'] ? NULL : function(\PropertyDescriber $d) {
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
				$h .= $form->dynamicGroups($eConfiguration, ['documentCopy', 'paymentMode', 'pdfNaturalOrder', 'marketSaleDefaultDecimal']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateOrderForm(\farm\Farm $eFarm, Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->selling();

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

		$h .= '<h2 class="mb-2">'.s("Personnalisation des devis").'</h2>';

		$h .= '<div class="util-section mb-2">';
			$h .= $form->openAjax('/selling/configuration:doUpdateOrderForm', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->dynamicGroups($eConfiguration, ['documentTarget', 'orderFormPrefix', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();
		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des devis par e-mail").'</h2>';

		$h .= '<div class="util-section mb-2">';
			$h .= $this->updateDocumentMail('order-form', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<h2>'.s("Exemple de devis").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, Pdf::ORDER_FORM, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateInvoice(\farm\Farm $eFarm, Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->selling();

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

		$h .= '<h2 class="mb-2">'.s("Personnalisation des factures").'</h2>';

		$h .= '<div class="util-section mb-2">';
			$h .= $form->openAjax('/selling/configuration:doUpdateInvoice', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$eConfiguration['documentInvoices']++;
				$h .= $form->dynamicGroups($eConfiguration, ['invoicePrefix', 'creditPrefix', 'documentInvoices', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();
		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des factures par e-mail").'</h2>';

		$h .= '<div class="util-section mb-2">';
			$h .= $this->updateDocumentMail('invoice', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<h2>'.s("Exemple de facture").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, Pdf::INVOICE, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateAccounting(\farm\Farm $eFarm, \Collection $cAccount): string {

		if($eFarm->hasAccounting() === FALSE) {
			return '<div class="util-block-help">'.s("Activez le <link>module de comptabilité</link> pour vous servir de cette fonctionnalité !", ['link' => '<a href="'.\company\CompanyUi::urlSettings($eFarm).'">']).'</div>';
		}

		$eConfiguration = $eFarm->selling();

		$profiles = ProductUi::p('profile')->values;

		$h = '<div class="util-info">'.s("En configurant directement vos types de produits, tous les produits de ce type que vous créerez auront la bonne classe de compte, ce qui facilitera votre comptabilité.").'</div>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/selling/configuration:doUpdateProfileAccount', ['id' => 'farm-update-profile', 'autocomplete' => 'off']);

		$h .= $form->hidden('id', $eConfiguration['id']);

		foreach($profiles as $key => $profile) {

			$e = new Product(['farm' => $eFarm]);
			if(($eConfiguration['profileAccount'][$key] ?? NULL) !== NULL) {
				$e['privateAccount'] = $cAccount->offsetGet($eConfiguration['profileAccount'][$key]);
			}

			$h .= $form->group(
				$profile,
				$form->dynamicField($e, 'privateAccount', function($d) use($key, $eConfiguration) {
					$d->name = 'profileAccount['.$key.']';
				})
			);

		}

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return $h;
	}

	public function updateDeliveryNote(\farm\Farm $eFarm, Sale $eSaleExample, \Collection $cCustomize): string {

		$eConfiguration = $eFarm->selling();

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

		$h .= '<h2 class="mb-2">'.s("Personnalisation des bons de livraison").'</h2>';

		$h .= '<div class="util-section mb-2">';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/selling/configuration:doUpdateDeliveryNote', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->dynamicGroups($eConfiguration, ['documentTarget', 'deliveryNotePrefix']);

				$h .= $form->group(
					content: $form->submit(s("Enregistrer"))
				);

			$h .= $form->close();

		$h .= '</div>';

		$h .= '<h2 class="mb-2">'.s("Envoi des bons de livraison par e-mail").'</h2>';

		$h .= '<div class="util-section mb-2">';
			$h .= $this->updateDocumentMail('delivery-note', $eFarm, $eSaleExample, $cCustomize);
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h2>'.s("Exemple de bon de livraison").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= new \selling\PdfUi()->getDocument($eSaleExample, Pdf::DELIVERY_NOTE, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateDocumentMail(string $type, \farm\Farm $eFarm, Sale $eSaleExample, \Collection $cCustomize): string {

		$customizePrivate = match($type) {
			'order-form' => \mail\Customize::SALE_ORDER_FORM_PRIVATE,
			'delivery-note' => \mail\Customize::SALE_DELIVERY_NOTE_PRIVATE,
			'invoice' => \mail\Customize::SALE_INVOICE_PRIVATE,
		};

		$customizePro = match($type) {
			'order-form' => \mail\Customize::SALE_ORDER_FORM_PRO,
			'delivery-note' => \mail\Customize::SALE_DELIVERY_NOTE_PRO,
			'invoice' => \mail\Customize::SALE_INVOICE_PRO,
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

	protected function getMailExample(string $type, \farm\Farm $eFarm, Sale $eSaleExample, string $customize, ?string $template): string {

		switch($type) {

			case 'order-form' :
				[$title, , $html] = new PdfUi()->getOrderFormMail($eFarm, $eSaleExample, $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

			case 'delivery-note' :
				[$title, , $html] = new PdfUi()->getDeliveryNoteMail($eFarm, $eSaleExample, $customize, $template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

			case 'invoice' :
				[$title, , $html] = new PdfUi()->getInvoiceMail($eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]), $customize,$template);
				return new \mail\CustomizeUi()->getMailExample($title, $html);

		}

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Configuration::model()->describer($property, [
			'documentInvoices' => s("Prochain numéro de facture ou d'avoir"),
			'documentTarget' => s("Permettre l'édition de devis et de bons de livraison"),
			'hasVat' => s("Assujettissement à la TVA"),
			'invoiceVat' => s("Numéro de TVA intracommunautaire"),
			'defaultVat' => s("Taux de TVA par défaut sur vos produits"),
			'defaultVatShipping' => s("Taux de TVA par défaut sur les frais de livraison"),
			'organicCertifier' => s("Organisme de certification pour l'Agriculture Biologique"),
			'paymentMode' => s("Moyens de paiement affichés sur les devis et les factures"),
			'deliveryNotePrefix' => s("Préfixe pour la numérotation des bons de livraison"),
			'orderFormPrefix' => s("Préfixe pour la numérotation des devis"),
			'orderFormDelivery' => s("Afficher la date de livraison de la commande sur les devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement affichées sur les devis"),
			'orderFormHeader' => s("Ajouter un texte personnalisé affiché en haut des devis"),
			'orderFormFooter' => s("Ajouter un texte personnalisé affiché en bas des devis"),
			'creditPrefix' => s("Préfixe pour la numérotation des avoirs"),
			'invoicePrefix' => s("Préfixe pour la numérotation des factures"),
			'invoicePaymentCondition' => s("Conditions de paiement affichées sur les factures"),
			'invoiceHeader' => s("Ajouter un texte personnalisé affiché en haut des factures"),
			'invoiceFooter' => s("Ajouter un texte personnalisé affiché en bas des factures"),
			'documentCopy' => s("Recevoir une copie sur l'adresse e-mail de la ferme des devis, bons de livraisons et factures que vous envoyez aux clients"),
			'pdfNaturalOrder' => s("Trier les commandes et les étiquettes exportées en PDF pour faciliter la découpe"),
			'marketSaleDefaultDecimal' => s("Quel valeur voulez-vous saisir par défaut dans le logiciel de caisse pour les produits vendus au poids ?"),
		]);

		switch($property) {

			case 'invoiceVat' :
				$d->palceholder = s("Exemple : {value}", 'FR01234567890');
				$d->after = \util\FormUi::info(s("Indiquez ici un numéro de TVA intracommunautaire si vous souhaitez le voir apparaître sur les factures."));
				break;

			case 'hasVat' :
				$d->field = 'yesNo';
				$d->after = \util\FormUi::info(s("Le changement dans la redevabilité de la TVA n'est pris en compte que pour les ventes créées ultérieurement."));
				break;

			case 'documentInvoices' :
				$d->after = \util\FormUi::info(s("Si ce numéro est déjà utilisé, alors le système affectera le premier numéro suivant non utilisé."));
				break;

			case 'defaultVat' :
				$d->field = 'select';
				$d->values = function(Configuration $e) {
					return SaleUi::getVat($e['farm']);
				};
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

			case 'defaultVatShipping' :
				$d->field = 'select';
				$d->values = function(Configuration $e) {
					return SaleUi::getVat($e['farm']);
				};
				$d->attributes = [
					'placeholder' => s("Calculé automatiquement en fonction du taux de TVA des articles vendus")
				];
				break;

			case 'organicCertifier' :
				$d->placeholder = s("Exemple : {value}", 'FR-BIO-01');
				$d->after = \util\FormUi::info(s("Indiquez ici le code ISO de votre organisme certificateur pour le voir apparaître sur les bons de commande, bons de livraison et factures."));
				break;

			case 'paymentMode' :
				$d->placeholder = s("Exemple : Paiement en espèces ou par virement bancaire. Coordonnées bancaires : FR76 XXXX XXXX XXXX XXXX XXX");
				$d->after = \util\FormUi::info(s("Indiquez ici les moyens de paiement autorisés pour régler vos devis et factures."));
				break;

			case 'deliveryNotePrefix' :
				$d->placeholder = ConfigurationUi::getDefaultOrderFormPrefix();
				break;

			case 'orderFormPrefix' :
				$d->placeholder = ConfigurationUi::getDefaultOrderFormPrefix();
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
				$d->placeholder = s("Exemple : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement données à vos clients après acceptation d'un devis."));
				break;

			case 'invoicePrefix' :
				$d->placeholder = ConfigurationUi::getDefaultInvoicePrefix();
				break;

			case 'creditPrefix' :
				$d->placeholder = ConfigurationUi::getDefaultCreditPrefix();
				break;

			case 'invoicePaymentCondition' :
				$d->placeholder = s("Exemple : Paiement à réception de facture.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement données à vos clients pour régler vos factures."));
				break;

		}

		return $d;

	}

}
?>
