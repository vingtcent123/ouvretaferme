<?php
namespace mail;

class CustomizeUi {

	public function create(Customize $e, \selling\Sale $eSaleExample): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/mail/customize:doCreate');

			$h .= $form->hidden('farm', $e['farm']);
			$h .= $form->hidden('type', $e['type']);

			if($e['shop']->notEmpty()) {
				$h .= $form->hidden('shop', $e['shop']['id']);
			}

			$h .= '<div class="util-block-gradient">';
				$h .= '<p>'.s("Vous pouvez personnaliser dynamiquement le contenu de cet e-mail en copiant les variables suivantes, qui s'adapteront automatiquement à la vente sélectionnée :").'</p>';

				$h .= '<div class="util-overflow-xs">';
					$h .= '<table class="tr-bordered">';
						$h .= '<thead>';
							$h .= '<tr>';
								$h .= '<th>'.s("Variable").'</th>';
								$h .= '<th>'.s("Description").'</th>';
								$h .= '<th>'.s("Exemple").'</th>';
							$h .= '</tr>';
						$h .= '</thead>';
						$h .= '<tbody>';

							$variables = self::getExampleVariables($e['type'], $e['farm'], $eSaleExample);

							foreach($this->getCreateVariables($e, $eSaleExample) as $name => $title) {

								$h .= '<tr>';
									$h .= '<td><b>@'.$name.'</b></td>';
									$h .= '<td>'.$title.'</td>';
									$h .= '<td>'.nl2br($variables[$name]).'</td>';
								$h .= '</tr>';

							}

						$h .= '</tbody>';
					$h .= '</table>';
				$h .= '</div>';
			$h .= '</div>';

			if(in_array($e['type'], [Customize::SHOP_CONFIRMED_HOME, Customize::SHOP_CONFIRMED_PLACE])) {

				$h .= '<div class="util-info">';
					$h .= \Asset::icon('exclamation-triangle-fill').' '.s("L'e-mail de confirmation de commande est le même pour toutes les commandes, quelque soit le moyen de paiement utilisé. Vous devez en tenir compte lorsque vous le rédigez. Pensez à utiliser la variable <i>@payment</i> pour rappeler à votre client le moyen de paiement qu'il a sélectionné.");
				$h .= '</div>';

			}

			$e['template'] ??= self::getDefaultTemplate($e['type']);
			$h .= $form->dynamicGroup($e, 'template');

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-customize-create',
			title: $this->getCreateTitle($e['type']),
			body: $h
		);

	}

	protected function getCreateTitle(string $type): string {

		return match($type) {

			Customize::SALE_ORDER_FORM => s("Personnaliser l'e-mail pour les devis"),
			Customize::SALE_DELIVERY_NOTE => s("Personnaliser l'e-mail pour les bons de livraison"),
			Customize::SALE_INVOICE => s("Personnaliser l'e-mail pour les factures"),
			Customize::SHOP_CONFIRMED_PLACE => s("Personnaliser l'e-mail de confirmation de commande pour les livraisons en point de retrait"),
			Customize::SHOP_CONFIRMED_HOME => s("Personnaliser l'e-mail de confirmation de commande pour les livraisons à domicile"),

		};

	}

	protected function getCreateVariables(Customize $e, \selling\Sale $eSaleExample): array {

		return match($e['type']) {

			Customize::SALE_ORDER_FORM => [
				'number' => s("Numéro de devis"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'valid' => s("Date d'échéance du devis")
			],

			Customize::SALE_DELIVERY_NOTE => [
				'number' => s("Numéro du bon de livraison"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'delivered' => s("Date de livraison")
			],

			Customize::SALE_INVOICE => [
				'number' => s("Numéro de facture"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'amount' => s("Montant de la facture"),
				'date' => s("Date de facturation"),
				'sales' => s("Ventes facturées")
			],

			Customize::SHOP_CONFIRMED_PLACE, Customize::SHOP_CONFIRMED_HOME => [
				'number' => s("Numéro de vente"),
				'farm' => s("Nom de votre ferme"),
				'amount' => s("Montant de la vente"),
				'products' => s("Liste des produits commandés"),
				'payment' => s("Moyen de paiement utilisé"),
				'delivery' => s("Date de livraison"),
				'address' => s("Adresse de livraison")
			],

		};

	}

	public static function getExampleVariables(string $type, \farm\Farm $eFarm, \selling\Sale $eSaleExample): array {

		switch($type) {
			case Customize::SALE_ORDER_FORM :
			case Customize::SALE_DELIVERY_NOTE :
				return self::getSaleVariables($type, $eFarm, $eSaleExample);

			case Customize::SALE_INVOICE :
				return self::getSaleVariables($type, $eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]));

			case Customize::SHOP_CONFIRMED_HOME :
			case Customize::SHOP_CONFIRMED_PLACE :

				$eSaleExample['paymentMethod'] = \selling\Sale::OFFLINE;
				$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][match($type) {
					Customize::SHOP_CONFIRMED_HOME => \shop\Point::HOME,
					Customize::SHOP_CONFIRMED_PLACE => \shop\Point::PLACE
				}];

				return self::getShopVariables($type, $eSaleExample, $eSaleExample['cItem']);

		};

	}

	public static function getSaleVariables(string $type, \farm\Farm $eFarm, ...$more): array {

		switch($type) {

			case Customize::SALE_ORDER_FORM :

				[$eSale] = $more;

				return [
					'number' => $eSale->getOrderForm(),
					'customer' => encode($eSale['customer']['legalName'] ?? $eSale['customer']['name']),
					'farm' => encode($eFarm['name']),
					'valid' => $eSale['orderFormValidUntil'] ? \util\DateUi::numeric($eSale['orderFormValidUntil']) : s("limite de validité inconnue"),
				];

			case Customize::SALE_DELIVERY_NOTE :

				[$eSale] = $more;

				return [
					'number' => $eSale->getDeliveryNote(),
					'customer' => encode($eSale['customer']['legalName'] ?? $eSale['customer']['name']),
					'farm' => encode($eFarm['name']),
					'delivered' => \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE),
				];

			case Customize::SALE_INVOICE :

				[$eInvoice, $cSale] = $more;

				if($cSale->count() === 1) {
					$sales = s("Cette facture correspond à la livraison du {date}.", ['date' => \util\DateUi::numeric($cSale->first()['deliveredAt'])]);
				} else {

					$sales = s("Cette facture inclut :")."\n\n";

					foreach($cSale as $eSale) {
						$sales .= '- '.s("Livraison du {date} ({amount})", ['date' => \util\DateUi::numeric($eSale['deliveredAt']), 'amount' => \util\TextUi::money($eSale['priceIncludingVat'])])."\n";
					}

				}

				return [
					'number' => $eInvoice->getInvoice(),
					'customer' => encode($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name']),
					'farm' => encode($eFarm['name']),
					'amount' => \util\TextUi::money($eInvoice['priceIncludingVat']).' '.($eInvoice['hasVat'] ? ' '.\selling\SaleUi::getTaxes(\selling\Sale::INCLUDING) : ''),
					'date' => \util\DateUi::textual($eInvoice['date']),
					'sales' => $sales
				];

		}

	}

	public static function getShopVariables(string $type, \selling\Sale $eSale, \Collection $cItem): array {

		switch($type) {

			case Customize::SHOP_CONFIRMED_HOME :
			case Customize::SHOP_CONFIRMED_PLACE :

				$ePoint = $eSale['shopPoint'];

				switch($eSale['paymentMethod']) {

					case \selling\Sale::TRANSFER :
						$payment = s("Vous avez choisi de régler cette commande par virement bancaire.");
						if($eSale['shop']['paymentTransferHow']) {
							$payment .= "\n".encode($eSale['shop']['paymentTransferHow']);
						}
						break;

					case \selling\Sale::ONLINE_CARD :
						$payment = s("Vous avez choisi de régler cette commande par carte bancaire.")."\n";
						$payment .= s("Votre paiement a bien été accepté.");
						break;

					case \selling\Sale::OFFLINE :
						$payment = s("Vous avez choisi de régler cette commande en direct avec votre producteur.");
						if($eSale['shop']['paymentOfflineHow']) {
							$payment .= "\n".encode($eSale['shop']['paymentOfflineHow']);
						}
						break;

					default :
						throw new \Exception('Not compatible');

				}

				$products = '';

				foreach($cItem as $eItem) {
					$products .= '- '.encode($eItem['name']).' : '.\main\UnitUi::getValue($eItem['number'], $eItem['unit'], noWrap: FALSE)."\n";
				}

				$products = rtrim($products);

				$address = '<div style="padding-left: 1rem; border-left: 3px solid #888888">';

				switch($ePoint['type']) {

					case \shop\Point::HOME :
						$address .= encode($eSale->getDeliveryAddress());
						break;

					case \shop\Point::PLACE :
						$address .= encode($ePoint['name'])."\n";
						if($ePoint['description']) {
							$address .= encode($ePoint['description'])."\n\n";
						}
						$address .= encode($ePoint['address'])."\n";
						$address .= encode($ePoint['place']);
						break;

				};

				$address .= '</div>';

				return [
					'number' => $eSale['document'],
					'farm' => encode($eSale['farm']['name']),
					'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
					'products' => $products,
					'payment' => $payment,
					'delivery' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate']),
					'address' => $address,
				];

		}

	}

	public static function convertTemplate(string $template, array $variables) {

		$template = encode($template);

		foreach($variables as $key => $value) {
			$template = str_ireplace('@'.$key, $value, $template);
		}

		return $template;

	}

	public static function getDefaultTemplate(string $type) {

		switch($type) {

			case Customize::SALE_ORDER_FORM :
				return s("Bonjour,

Vous trouverez en pièce jointe notre proposition commerciale.

Cordialement,
@farm");

			case Customize::SALE_DELIVERY_NOTE :
				return s("Bonjour,

Vous trouverez en pièce jointe le bon de livraison pour la commande livrée le @delivered.

Cordialement,
@farm");

			case Customize::SALE_INVOICE :
				return s("Bonjour,

Vous trouverez en pièce jointe notre facture d'un montant de @amount.
@sales

Cordialement,
@farm");

			case Customize::SHOP_CONFIRMED_PLACE :
				return s("Bonjour,

Votre commande n°@number d'un montant de @amount a bien été enregistrée.

Vous avez commandé :
@products

@payment

Vous pourrez venir retirer votre commande le @delivery au point de retrait suivant :
@address

Merci et à bientôt,
@farm");

			case Customize::SHOP_CONFIRMED_HOME :
				return s("Bonjour,

Votre commande n°@number d'un montant de @amount a bien été enregistrée.

Vous avez commandé :
@products

@payment

Votre commande vous sera livrée le @delivery à l'adresse suivante :
@address

Merci et à bientôt,
@farm");

		}

	}

	public function getMailExample(string $title, string $content): string {

		$h = '<div class="util-overflow-md mt-2 mb-2">';
			$h .= '<dl class="util-presentation util-presentation-1" style="column-gap: 2rem; row-gap: 0.5rem; max-width: 1000px">';
				$h .= '<dt>'.s("Titre de l'e-mail").'</dt>';
				$h .= '<dd><b>'.encode($title).'</b></dd>';
				$h .= '<dt>'.s("Contenu de l'e-mail").'</dt>';
				$h .= '<dd><div class="util-block" style="font-weight: normal">'.$content.'</div></dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Customize::model()->describer($property, [
			'template' => s("Nouveau contenu"),
		]);

		switch($property) {

			case 'template' :
				$d->attributes['style'] = 'height: 20rem';
				break;

		}

		return $d;

	}

}
?>
