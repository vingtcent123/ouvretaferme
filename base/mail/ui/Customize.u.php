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
					$h .= '<table>';
						$h .= '<thead>';
							$h .= '<tr>';
								$h .= '<th>'.s("Variable").'</th>';
								$h .= '<th>'.s("Description").'</th>';
								$h .= '<th>'.s("Exemple").'</th>';
							$h .= '</tr>';
						$h .= '</thead>';
						$h .= '<tbody>';

							$variables = self::getExampleVariables($e['type'], $e['farm'], $eSaleExample);

							foreach($this->getCreateVariables($e) as $name => $title) {

								if(isset($variables[$name])) {

									$h .= '<tr>';
										$h .= '<td><b>@'.$name.'</b></td>';
										$h .= '<td>'.$title.'</td>';
										$h .= '<td>'.nl2br($variables[$name]).'</td>';
									$h .= '</tr>';

								}

							}

						$h .= '</tbody>';
					$h .= '</table>';
				$h .= '</div>';
			$h .= '</div>';

			if(
				in_array($e['type'], [Customize::SHOP_CONFIRMED_HOME, Customize::SHOP_CONFIRMED_PLACE]) and
				$eSaleExample['shop']->isPersonal()
			) {

				$h .= '<div class="util-block-help">';
					$h .= s("L'e-mail de confirmation de commande est le même pour toutes les commandes, quel que soit le moyen de paiement utilisé. Vous devez en tenir compte lorsque vous le rédigez. Pensez à utiliser la variable <i>@payment</i> pour rappeler à votre client le moyen de paiement qu'il a sélectionné. Notez que dans le cas où vous désactivez le choix du moyen de paiement, la variable <i>@payment</i> sera vide.");
				$h .= '</div>';

			}

			$e['template'] ??= self::getDefaultTemplate($e['type'], $eSaleExample);

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

			Customize::SALE_ORDER_FORM_PRIVATE => s("Personnaliser l'e-mail pour les devis destinés aux clients particuliers"),
			Customize::SALE_ORDER_FORM_PRO => s("Personnaliser l'e-mail pour les devis destinés aux clients professionnels"),
			Customize::SALE_DELIVERY_NOTE_PRIVATE => s("Personnaliser l'e-mail pour les bons de livraison destinés aux clients particuliers"),
			Customize::SALE_DELIVERY_NOTE_PRO => s("Personnaliser l'e-mail pour les bons de livraison destinés aux clients professionnels"),
			Customize::SALE_INVOICE_PRIVATE => s("Personnaliser l'e-mail pour les factures destinées aux clients particuliers"),
			Customize::SALE_INVOICE_PRO => s("Personnaliser l'e-mail pour les factures destinées aux clients professionnels"),
			Customize::SALE_REMINDER_PRIVATE => s("Personnaliser l'e-mail pour les relances destinées aux clients particuliers"),
			Customize::SALE_REMINDER_PRO => s("Personnaliser l'e-mail pour les relances destinées aux clients professionnels"),
			Customize::SHOP_CONFIRMED_NONE => s("Personnaliser l'e-mail de confirmation de commande"),
			Customize::SHOP_CONFIRMED_PLACE => s("Personnaliser l'e-mail de confirmation de commande pour les livraisons en point de retrait"),
			Customize::SHOP_CONFIRMED_HOME => s("Personnaliser l'e-mail de confirmation de commande pour les livraisons à domicile"),

		};

	}

	protected function getCreateVariables(Customize $e): array {

		return match($e['type']) {

			Customize::SALE_ORDER_FORM_PRIVATE, Customize::SALE_ORDER_FORM_PRO => [
				'number' => s("Numéro de devis"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'valid' => s("Date d'échéance du devis")
			],

			Customize::SALE_DELIVERY_NOTE_PRIVATE, Customize::SALE_DELIVERY_NOTE_PRO => [
				'number' => s("Numéro du bon de livraison"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'delivered' => s("Date de livraison")
			],

			Customize::SALE_INVOICE_PRIVATE, Customize::SALE_INVOICE_PRO,
			Customize::SALE_REMINDER_PRIVATE, Customize::SALE_REMINDER_PRO => [
				'number' => s("Numéro de facture"),
				'customer' => s("Nom du client"),
				'farm' => s("Nom de votre ferme"),
				'amount' => s("Montant de la facture"),
				'date' => s("Date de facturation"),
				'dueDate' => s("Date d'échéance"),
				'sales' => s("Ventes facturées")
			],

			Customize::SHOP_CONFIRMED_NONE, Customize::SHOP_CONFIRMED_PLACE, Customize::SHOP_CONFIRMED_HOME => [
				'number' => s("Numéro de vente"),
				'farm' => s("Nom de votre ferme"),
				'customer' => s("Nom du client"),
				'amount' => s("Montant de la vente"),
				'products' => s("Liste des produits commandés"),
				'link' => s("Lien vers la page de confirmation de commande"),
				'payment' => s("Moyen de paiement utilisé"),
				'delivery' => s("Date de livraison")
			] + (($e['type'] === Customize::SHOP_CONFIRMED_NONE) ? [] : [
				'address' => s("Adresse de livraison")
			]),

		};

	}

	public static function getExampleVariables(string $type, \farm\Farm $eFarm, \selling\Sale $eSaleExample): array {

		switch($type) {
			case Customize::SALE_ORDER_FORM_PRIVATE :
			case Customize::SALE_ORDER_FORM_PRO :
			case Customize::SALE_DELIVERY_NOTE_PRIVATE :
			case Customize::SALE_DELIVERY_NOTE_PRO :
				return self::getSaleVariables($type, $eFarm, $eSaleExample);

			case Customize::SALE_INVOICE_PRIVATE :
			case Customize::SALE_INVOICE_PRO :
			case Customize::SALE_REMINDER_PRIVATE :
			case Customize::SALE_REMINDER_PRO :
				return self::getSaleVariables($type, $eFarm, $eSaleExample['invoice'], new \Collection([$eSaleExample]));

			case Customize::SHOP_CONFIRMED_NONE :
			case Customize::SHOP_CONFIRMED_HOME :
			case Customize::SHOP_CONFIRMED_PLACE :

				if($type !== Customize::SHOP_CONFIRMED_NONE) {

					$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][match($type) {
						Customize::SHOP_CONFIRMED_HOME => \shop\Point::HOME,
						Customize::SHOP_CONFIRMED_PLACE => \shop\Point::PLACE
					}];

				} else {
					$eSaleExample['shopPoint'] = new \shop\Point();
				}

				$variables = self::getShopVariables($type, $eSaleExample, $eSaleExample['cItem']);

				if($eSaleExample['shop']['hasPayment'] === FALSE) {
					$variables['payment'] = '<i>'.s("Vide car le choix du moyen de paiement est désactivé sur la boutique").'</i>';
				} else {
					$variables['payment'] = \payment\MethodUi::getName($eSaleExample['cPayment']->first()['method']);
				}

				return $variables;

		};

	}

	public static function getSaleVariables(string $type, \farm\Farm $eFarm, ...$more): array {

		switch($type) {

			case Customize::SALE_ORDER_FORM_PRIVATE :
			case Customize::SALE_ORDER_FORM_PRO :

				[$eSale] = $more;

				return [
					'number' => $eSale->getOrderForm($eFarm),
					'customer' => encode($eSale['customer']->getLegalName()),
					'farm' => encode($eFarm['name']),
					'valid' => $eSale['orderFormValidUntil'] ? \util\DateUi::numeric($eSale['orderFormValidUntil']) : s("limite de validité inconnue"),
				];

			case Customize::SALE_DELIVERY_NOTE_PRIVATE :
			case Customize::SALE_DELIVERY_NOTE_PRO :

				[$eSale] = $more;

				return [
					'number' => $eSale->getDeliveryNote($eFarm),
					'customer' => encode($eSale['customer']->getLegalName()),
					'farm' => encode($eFarm['name']),
					'delivered' => \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE),
				];

			case Customize::SALE_INVOICE_PRIVATE :
			case Customize::SALE_INVOICE_PRO :
			case Customize::SALE_REMINDER_PRIVATE :
			case Customize::SALE_REMINDER_PRO :

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
					'number' => encode($eInvoice['number']),
					'customer' => encode($eInvoice['customer']->getLegalName()),
					'farm' => encode($eFarm['name']),
					'amount' => \util\TextUi::money($eInvoice['priceIncludingVat']).' '.($eInvoice['hasVat'] ? ' '.\selling\SaleUi::getTaxes(\selling\Sale::INCLUDING) : ''),
					'date' => \util\DateUi::textual($eInvoice['date']),
					'dueDate' => ($eInvoice['dueDate'] !== NULL) ? \util\DateUi::textual($eInvoice['dueDate']) : '?',
					'sales' => $sales
				];

		}

	}

	public static function getShopVariables(string $type, \selling\Sale $eSale, \Collection $cItem, bool $group = TRUE): array {

		switch($type) {

			case Customize::SHOP_CONFIRMED_HOME :
			case Customize::SHOP_CONFIRMED_PLACE :
			case Customize::SHOP_CONFIRMED_NONE :

				$ePoint = $eSale['shopPoint'];

				if($eSale['shop']['shared'] and $group) {

					$payment = NULL;
					$amount = NULL;
					$farm = NULL;
					$number = NULL;
					$customer = NULL;

					$products = '';

					foreach($cItem->reindex(['product', 'farm']) as $cItemFarm) {

						$products .= '<u>'.encode($cItemFarm->first()['farm']['name']).'</u>';
						$products .= "\n";
						$products .= self::getShopProducts($cItemFarm);
						$products .= "\n";

					}

				} else {

					if($eSale['shop']['hasPayment']) {

						if($eSale->hasSuccessfulPayment() === FALSE) {

							if($eSale['shop']['shared']) {
								$payment = s("Vous avez choisi de régler cette commande en direct avec vos producteurs.");
							} else {
								$payment = s("Vous avez choisi de régler cette commande en direct avec votre producteur.");
							}

							if($eSale['shop']['paymentOfflineHow']) {
								$payment .= "\n".encode($eSale['shop']['paymentOfflineHow']);
							}

						} else {

							$ePayment = $eSale->extractFirstValidPayment();
							switch($ePayment['method']['fqn']) {

								case \payment\MethodLib::TRANSFER :
									$payment = s("Vous avez choisi de régler cette commande par virement bancaire.");
									if($eSale['shop']['paymentTransferHow']) {
										$payment .= "\n".encode($eSale['shop']['paymentTransferHow']);
									}
									break;

								case \payment\MethodLib::CARD :
									$payment = s("Vous avez choisi de régler cette commande par carte bancaire.")."\n";
									break;

								case \payment\MethodLib::ONLINE_CARD :
									$payment = s("Vous avez choisi de régler cette commande par carte bancaire.")."\n";
									$payment .= s("Votre paiement a bien été accepté.");
									break;

								default :
									throw new \Exception('Not compatible');
							}
						}

					} else {
						$payment = '';
					}

					if($eSale['hasVat'] and $eSale['type'] === \selling\Sale::PRO) {
						$amount = \util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes();
					} else {
						$amount = \util\TextUi::money($eSale['priceIncludingVat']);
					}

					$products = self::getShopProducts($cItem);

					$farm = encode($eSale['farm']['name']);
					$number = $eSale['document'];
					$customer = encode($eSale['customer']->getName());

				}

				$products = rtrim($products);

				if(
					$eSale['shop']->isApproximate() and
					\selling\Item::containsApproximate($cItem)
				) {
					$products .= "\n\n".s("Certains produits de cette commande nécessitent une pesée, et le montant définitif pourra être légèrement différent.");
				}

				if($eSale['shop']->isPersonal() and ($ePayment['method']['fqn'] ?? NULL) === \payment\MethodLib::ONLINE_CARD) {
					$link = s("Vous pouvez consulter votre commande avec le lien suivant :")."\n";
				} else {
					$link = s("Vous pouvez consulter et modifier votre commande avec le lien suivant :")."\n";
				}
				$link .= \shop\ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate']);

				$variables = [
					'number' => $number,
					'farm' => $farm,
					'customer' => $customer,
					'amount' => $amount,
					'products' => $products,
					'payment' => $payment,
					'link' => $link,
				];

				if($eSale['shopDate']['deliveryDate'] !== NULL) {

					$variables += [
						'delivery' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate']),
					];

				}

				if($type !== Customize::SHOP_CONFIRMED_NONE) {

					if($ePoint->notEmpty()) {

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

					} else {
						$address = '';
					}

					$variables['address'] = $address;

				}

				return $variables;

		}

	}

	public static function getShopProducts(\Collection $cItem, string $mode = 'text'): string {

		$products = '';

		if($mode === 'html') {
			$products .= '<ul>';
		}

		foreach($cItem as $eItem) {

			if($eItem['packaging'] === NULL) {
				$number = \selling\UnitUi::getValue($eItem['number'], $eItem['unit']);
			} else {
				$number = p("{value} colis de {quantity}", "{value} colis de {quantity}", $eItem['number'], ['quantity' => \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'])]);
			}


			if($mode === 'html') {
				$products .= '<li>';
				if($eItem['product']->notEmpty()) {
					$products .= \selling\ProductUi::getVignette($eItem['product'], '1.5rem').'  ';
				}
			} else {
				$products .= '- ';
			}

			$products .= s("{name} : {number}", ['name' => encode($eItem['name']), 'number' => $number]);

			if($mode === 'html') {
				$products .= '</li>';
			} else {
				$products .= "\n";
			}

		}

		if($mode === 'html') {
			$products .= '</ul>';
		}

		return $products;

	}

	public static function convertTemplate(string $template, array $variables) {

		$template = encode($template);

		foreach($variables as $key => $value) {
			$template = str_ireplace('@'.$key, $value ?? '', $template);
		}

		return $template;

	}

	public static function getDefaultTemplate(string $type, ?\selling\Sale $eSale = NULL) {

		switch($type) {

			case Customize::SALE_ORDER_FORM_PRIVATE :
			case Customize::SALE_ORDER_FORM_PRO :
				return s("Bonjour,

Vous trouverez en pièce jointe notre proposition commerciale.

Cordialement,
@farm");

			case Customize::SALE_DELIVERY_NOTE_PRIVATE :
			case Customize::SALE_DELIVERY_NOTE_PRO :
				return s("Bonjour,

Vous trouverez en pièce jointe le bon de livraison pour la commande livrée le @delivered.

Cordialement,
@farm");

			case Customize::SALE_INVOICE_PRIVATE :
			case Customize::SALE_INVOICE_PRO :
				return s("Bonjour,

Vous trouverez en pièce jointe notre facture d'un montant de @amount.
@sales

Cordialement,
@farm");

			case Customize::SALE_REMINDER_PRIVATE :
			case Customize::SALE_REMINDER_PRO :
				return s("Bonjour,

Nous sommes toujours en attente de paiement pour la facture @number du @date d'un montant de @amount.
La date d'échéance de cette facture était fixée au @dueDate.

Nous vous prions de bien vouloir régulariser la situation.
Si vous avez déjà réglé cette facture, vous pouvez ignorer cette relance.

Cordialement,
@farm");

			case Customize::SHOP_CONFIRMED_NONE :

				if($eSale['shop']['shared']) {

					if($eSale['shopDate']['deliveryDate'] === NULL) {
						$delivery = s("Votre commande a bien été enregistrée.");
					} else {
						$delivery = s("Votre commande pour le @delivery a bien été enregistrée.");
					}

					return s("Bonjour,

{delivery}

Vous avez commandé auprès des producteurs suivants :

@products

@link

Merci et à bientôt,
Vos producteurs", ['delivery' => $delivery]);

				} else {

					if($eSale['shopDate']['deliveryDate'] === NULL) {
						$delivery = s("Votre commande n°@number d'un montant de @amount a bien été enregistrée.");
					} else {
						$delivery = s("Votre commande n°@number d'un montant de @amount pour le @delivery a bien été enregistrée.");
					}

					return s("Bonjour,

{delivery}

Vous avez commandé :
@products

@payment

@link

Merci et à bientôt,
@farm", ['delivery' => $delivery]);

				}

			case Customize::SHOP_CONFIRMED_PLACE :

				if($eSale['shopDate']['deliveryDate'] === NULL) {
					$delivery = s("Vous pourrez venir retirer votre commande au point de retrait suivant :");
				} else {
					$delivery = s("Vous pourrez venir retirer votre commande le @delivery au point de retrait suivant :");
				}

				if($eSale['shop']['shared']) {

					return s("Bonjour,

Votre commande a bien été enregistrée.

Vous avez commandé auprès des producteurs suivants :

@products

{delivery}
@address

@link

Merci et à bientôt,
Vos producteurs", ['delivery' => $delivery]);

				} else {

					return s("Bonjour,

Votre commande n°@number d'un montant de @amount a bien été enregistrée.

Vous avez commandé :
@products

@payment

{delivery}
@address

@link

Merci et à bientôt,
@farm", ['delivery' => $delivery]);

				}

			case Customize::SHOP_CONFIRMED_HOME :

				if($eSale['shopDate']['deliveryDate'] === NULL) {
					$delivery = s("Votre commande vous sera livrée à l'adresse suivante :");
				} else {
					$delivery = s("Votre commande vous sera livrée le @delivery à l'adresse suivante :");
				}

				if($eSale['shop']['shared']) {

					return s("Bonjour,

Votre commande a bien été enregistrée.

Vous avez commandé auprès des producteurs suivants :

@products

{delivery}
@address

@link

Merci et à bientôt,
Vos producteurs", ['delivery' => $delivery]);

				} else {

					return s("Bonjour,

Votre commande n°@number d'un montant de @amount a bien été enregistrée.

Vous avez commandé :
@products

@payment

{delivery}
@address

@link

Merci et à bientôt,
@farm", ['delivery' => $delivery]);

				}

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
				$d->labelAfter = fn(Customize $e) => \farm\FarmUi::getEmailInfo($e['farm']);
				$d->before = fn(\util\FormUi $form, Customize $e) => DesignUi::getBanner($e['farm']);
				$d->after = fn(\util\FormUi $form, Customize $e) => '<br/>'.DesignUi::getFooter($e['farm']);
				$d->attributes['style'] = 'height: 20rem';
				break;

		}

		return $d;

	}

}
?>
