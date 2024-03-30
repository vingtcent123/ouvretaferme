<?php
namespace selling;

class ConfigurationUi {

	public function __construct() {

		\Asset::css('selling', 'configuration.css');
		\Asset::js('selling', 'configuration.js');

	}

	public function update(\farm\Farm $eFarm, Configuration $eConfiguration, \Collection $cCustomize, Sale $eSaleExample): string {

		$h = '<div class="tabs-h" id="selling-configure" onrender="'.encode('Lime.Tab.restore(this, "settings")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="settings" onclick="Lime.Tab.select(this)">'.s("Administratif").'</a>';
				$h .= '<a class="tab-item" data-tab="orderForm" onclick="Lime.Tab.select(this)">'.s("Devis").'</a>';
				$h .= '<a class="tab-item" data-tab="deliveryNote" onclick="Lime.Tab.select(this)">'.s("Bons de livraisons").'</a>';
				$h .= '<a class="tab-item" data-tab="invoice" onclick="Lime.Tab.select(this)">'.s("Factures").'</a>';
				$h .= '<a class="tab-item" data-tab="mail" onclick="Lime.Tab.select(this)">'.s("E-mails").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="settings">';
				$h .= $this->updateSettings($eConfiguration, $eFarm);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="orderForm">';
				$h .= $this->updateOrderForm($eConfiguration, $eFarm, $eSaleExample);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="deliveryNote">';
				$h .= $this->updateDeliveryNote($eConfiguration, $eFarm, $eSaleExample);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="invoice">';
				$h .= $this->updateInvoice($eConfiguration, $eFarm, $eSaleExample);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="mail">';
				$h .= $this->updateMail($eConfiguration, $cCustomize, $eFarm, $eSaleExample);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function updateSettings(Configuration $eConfiguration, \farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = '';

		if($eConfiguration->isComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Pour générer des devis, bons de livraison et factures sur <i>{siteName}</i>, veuillez renseigner à minima les informations suivantes :").'</p>';
				$h .= '<ul>';
					if($eConfiguration['legalName'] === NULL) {
						$h .= '<li>'.self::p('legalName')->label.'</li>';
					}
					if($eConfiguration['legalEmail'] === NULL) {
						$h .= '<li>'.self::p('legalEmail')->label.'</li>';
					}
					if($eConfiguration->hasInvoiceAddress() === FALSE) {
						$h .= '<li>'.s("Adresse de facturation de la ferme").'</li>';
					}
				$h .= '</ul>';
			$h .= '</div>';

		}

		$h .= $form->openAjax('/selling/configuration:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eConfiguration['id']);

				$h .= $form->group(content: '<h3>'.s("Facturation").'</h3>');
				$h .= $form->dynamicGroups($eConfiguration, ['legalName', 'legalEmail']);
				$h .= $form->addressGroup(s("Adresse de facturation de la ferme"), 'invoice', $eConfiguration);
				$h .= $form->dynamicGroups($eConfiguration, ['invoiceRegistration', 'paymentMode', 'documentCopy']);
				$h .= '<br/>';
				$h .= $form->group(
					\farm\FarmUi::p('logo')->label,
					(new \media\FarmLogoUi())->getCamera($eFarm, size: '15rem')
				);
				$h .= '<br/>';
				$h .= $form->group(
					\farm\FarmUi::p('banner')->label,
					(new \media\FarmBannerUi())->getCamera($eFarm, width: '500px', height: 'auto')
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

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateOrderForm(Configuration $eConfiguration, \farm\Farm $eFarm, Sale $eSaleExample): string {

		$form = new \util\FormUi();

		$h = '';

		if($eConfiguration->isComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pourrez personnaliser les devis dès lors que vous aurez terminé la configuration administrative de la commercialisation !").'</p>';
			$h .= '</div>';

			return $h;

		}

		$h .= $form->openAjax('/selling/configuration:doUpdateOrderForm', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eConfiguration['id']);

			$h .= $form->dynamicGroups($eConfiguration, ['orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		$h .= '<br/>';

		$h .= '<h2>'.s("Exemple de devis").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= (new \selling\PdfUi())->getDocument($eSaleExample, Pdf::ORDER_FORM, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateInvoice(Configuration $eConfiguration, \farm\Farm $eFarm, Sale $eSaleExample): string {

		$form = new \util\FormUi();

		$h = '';

		if($eConfiguration->isComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pourrez personnaliser les factures dès lors que vous aurez terminé la configuration administrative de la commercialisation !").'</p>';
			$h .= '</div>';

			return $h;

		}

		$h .= $form->openAjax('/selling/configuration:doUpdateInvoice', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eConfiguration['id']);

			$h .= $form->dynamicGroups($eConfiguration, ['invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		$h .= '<br/>';

		$h .= '<h2>'.s("Exemple de facture").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= (new \selling\PdfUi())->getDocument($eSaleExample, Pdf::INVOICE, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateDeliveryNote(Configuration $eConfiguration, \farm\Farm $eFarm, Sale $eSaleExample): string {

		$h = '';

		if($eConfiguration->isComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pourrez personnaliser les bons de livraison dès lors que vous aurez terminé la configuration administrative de la commercialisation !").'</p>';
			$h .= '</div>';

			return $h;

		}

		$h .= '<h2>'.s("Exemple de bon de livraison").'</h2>';

		$h .= '<div class="configuration-sale-example-wrapper">';
			$h .= '<div class="configuration-sale-example">';
				$h .= (new \selling\PdfUi())->getDocument($eSaleExample, Pdf::DELIVERY_NOTE, $eFarm, $eSaleExample['cItem']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateMail(Configuration $eConfiguration, \Collection $cCustomize, \farm\Farm $eFarm, Sale $eSaleExample): string {

		$h = '';

		if($eConfiguration->isComplete() === FALSE) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pourrez personnaliser les e-mails dès lors que vous aurez terminé la configuration administrative de la commercialisation !").'</p>';
			$h .= '</div>';

			return $h;

		}

		$h .= '<div class="util-block-help">';
			$h .= '<h4>'.s("Personnalisation des e-mails").'</h4>';
			$h .= '<p>'.s("Vous pouvez personnaliser le contenu des e-mails envoyés à vos clients pour leur transmettre vos devis, bons de livraison et factures.").'</p>';
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Envoi de devis").'</h3>';
			$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.\mail\Customize::SALE_ORDER_FORM.'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
		$h .= '</div>';

		[$title, , $html] = (new PdfUi())->getOrderFormMail($eFarm, $eSaleExample, $cCustomize[\mail\Customize::SALE_ORDER_FORM]['template'] ?? NULL);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Envoi de bon de livraison").'</h3>';
			$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.\mail\Customize::SALE_DELIVERY_NOTE.'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
		$h .= '</div>';

		[$title, , $html] = (new PdfUi())->getDeliveryNoteMail($eFarm, $eSaleExample, $cCustomize[\mail\Customize::SALE_DELIVERY_NOTE]['template'] ?? NULL);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Envoi de facture").'</h3>';
			$h .= '<a href="/mail/customize:create?farm='.$eFarm['id'].'&type='.\mail\Customize::SALE_INVOICE.'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
		$h .= '</div>';

		[$title, , $html] = (new PdfUi())->getInvoiceMail($eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]), $cCustomize[\mail\Customize::SALE_INVOICE]['template'] ?? NULL);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Configuration::model()->describer($property, [
			'legalName' => s("Raison sociale de la ferme"),
			'invoiceRegistration' => s("Numéro d'immatriculation SIRET"),
			'hasVat' => s("Assujettissement à la TVA"),
			'invoiceVat' => s("Numéro de TVA intracommunautaire"),
			'legalEmail' => s("Adresse e-mail utilisée pour la facturation de vos clients"),
			'defaultVat' => s("Taux de TVA par défaut sur vos produits"),
			'defaultVatShipping' => s("Taux de TVA par défaut sur les frais de livraison"),
			'organicCertifier' => s("Organisme de certification pour l'Agriculture Biologique"),
			'paymentMode' => s("Moyens de paiement affichés sur les devis et les factures"),
			'orderFormDelivery' => s("Afficher la date de livraison de la commande sur les devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement affichées sur les devis"),
			'orderFormHeader' => s("Ajouter un texte personnalisé affiché en haut des devis"),
			'orderFormFooter' => s("Ajouter un texte personnalisé affiché en bas des devis"),
			'invoicePaymentCondition' => s("Conditions de paiement affichées sur les factures"),
			'invoiceHeader' => s("Ajouter un texte personnalisé affiché en haut des factures"),
			'invoiceFooter' => s("Ajouter un texte personnalisé affiché en bas des factures"),
			'invoicePaymentCondition' => s("Conditions de paiement affichées sur les factures"),
			'invoiceHeader' => s("Ajouter un texte personnalisé affiché en haut des factures"),
			'invoiceFooter' => s("Ajouter un texte personnalisé affiché en bas des factures"),
			'documentCopy' => s("Recevoir une copie sur l'adresse e-mail de la ferme des devis, bons de livraisons et factures que vous envoyez aux clients"),
		]);

		switch($property) {

			case 'invoiceRegistration' :
				$d->palceholder = s("Exemple : {value}", '123 456 789 00013');
				$d->after = \util\FormUi::info(s("Indiquez ici un numéro SIRET que vous souhaitez voir apparaître sur les factures."));
				break;

			case 'invoiceVat' :
				$d->palceholder = s("Exemple : {value}", 'FR01234567890');
				$d->after = \util\FormUi::info(s("Indiquez ici un numéro de TVA intracommunautaire si vous souhaitez le voir apparaître sur les factures."));
				break;

			case 'hasVat' :
				$d->field = 'yesNo';
				$d->after = \util\FormUi::info(s("Le changement d'assujettissement n'est pris en compte que pour les ventes créées ultérieurement."));
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

			case 'orderFormDelivery' :
			case 'documentCopy' :
				$d->field = 'yesNo';
				break;

			case 'orderFormPaymentCondition' :
				$d->placeholder = s("Exemple : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Indiquez ici les conditions de paiement données à vos clients après acceptation d'un devis."));
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
