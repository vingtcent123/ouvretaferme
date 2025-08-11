<?php
namespace shop;

class MailUi {

	public static function getNewFarmSale(string $type, \selling\Sale $eSale, \Collection $cItem): array {

		$title = match($type) {
			'confirmed' => s("Commande de {customer} reçue pour une livraison le {date}", ['customer' => $eSale['customer']->getName(), 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande de {customer} modifiée pour une livraison le {date}", ['customer' => $eSale['customer']->getName(), 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};

		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_NONE, $eSale, $cItem, FALSE);

		if($eSale['shopComment'] !== NULL) {
			$comment = s("Commentaire du client :

{value}

", $eSale['shopComment']);
		} else {
			$comment = '';
		}

		$template = match($type) {
			'confirmed' => s("Bonjour,

Vous avez reçu une commande de @customer."),
			'updated' => s("Bonjour,

Vous avez reçu une modification de commande de @customer.")
		};
		$template .= "\n\n";
		$template .= s("- Boutique : {shop}
- Date de livraison : @delivery
- Montant de la commande : @amount

{comment}Contenu de la commande :

@products

Bonne réception,
L'équipe {siteName}", ['shop' => encode($eSale['shop']['name']), 'comment' => $comment]);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content, encapsulate: FALSE);

	}

	public static function getCancelFarmSale(\selling\Sale $eSale): array {

		$arguments = [
			'shop' => encode($eSale['shop']['name']),
			'customer' => $eSale['customer']->getName(),
			'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])
		];

		$title = s("Commande annulée de {customer} pour une livraison le {date}", $arguments);

		$content = s("Bonjour,

Votre client {customer} a annulé une commande.

- Boutique : {shop}
- Date de livraison : {date}

Bonne réception,
L'équipe {siteName}", $arguments);

		return \mail\DesignUi::format($eSale['farm'], $title, $content, encapsulate: FALSE);

	}

	public static function getSaleUpdated(\selling\Sale $eSale, \Collection $cItem, bool $group, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint',
			'shop' => ['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']
		]);

		if($eSale['shopPoint']->empty()) {
			return self::getSaleNone('updated', $eSale, $cItem, $group, $template);
		}

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('updated', $eSale, $cItem, $group, $template),
			Point::PLACE => self::getSalePlace('updated', $eSale, $cItem, $group, $template)
		};

	}

	public static function getSaleConfirmed(\selling\Sale $eSale, \Collection $cItem, bool $group, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint',
			'shop' => ['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']
		]);

		if($eSale['shopPoint']->empty()) {
			return self::getSaleNone('confirmed', $eSale, $cItem, $group, $template);
		}

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('confirmed', $eSale, $cItem, $group, $template),
			Point::PLACE => self::getSalePlace('confirmed', $eSale, $cItem, $group, $template)
		};

	}

	protected static function getSaleNone(string $type, \selling\Sale $eSale, \Collection $cItem, bool $group, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_NONE, $eSale);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_NONE, $eSale, $cItem, $group);

		$arguments = $group ? ['date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])] : ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])];

		$title = match($type) {
			'confirmed' => $group ? s("Commande validée pour le {date}", $arguments) : s("Commande n°{id} validée pour le {date}", $arguments),
			'updated' => $group ? s("Commande modifiée pour le {date}", $arguments) : s("Commande n°{id} modifiée pour le {date}", $arguments),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['shop']['farm'], $title, $content);

	}

	protected static function getSaleHome(string $type, \selling\Sale $eSale, \Collection $cItem, bool $group, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_HOME, $eSale);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_HOME, $eSale, $cItem, $group);


		$arguments = $group ? ['date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])] : ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])];

		$title = match($type) {
			'confirmed' => $group ? s("Commande validée pour une livraison le {date}", $arguments) : s("Commande n°{id} validée pour une livraison le {date}", $arguments),
			'updated' => $group ? s("Commande modifiée pour une livraison le {date}", $arguments) : s("Commande n°{id} modifiée pour une livraison le {date}", $arguments),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['shop']['farm'], $title, $content);

	}

	protected static function getSalePlace(string $type, \selling\Sale $eSale, \Collection $cItem, bool $group, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_PLACE, $eSale);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_PLACE, $eSale, $cItem, $group);

		$arguments = $group ? ['date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])] : ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])];

		$title = match($type) {
			'confirmed' => $group ? s("Commande validée pour un retrait le {date}", $arguments) : s("Commande n°{id} validée pour un retrait le {date}", $arguments),
			'updated' => $group ? s("Commande modifiée pour un retrait le {date}", $arguments) : s("Commande n°{id} modifiée pour un retrait le {date}", $arguments),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['shop']['farm'], $title, $content);

	}

	public static function getSaleCanceled(\selling\Sale $eSale): array {

		if($eSale['paymentMethod']->exists() and $eSale['paymentMethod']['fqn'] === \payment\MethodLib::TRANSFER) {
				$payment = s("Vous ne serez donc pas facturé du montant de cette commande.")."\n";
		} else {
				$payment = '';
		}

		if($eSale['shop']->isPersonal()) {

		$title = s("Commande n°{id} annulée", ['id' => $eSale['document']]);

		$content = s("Bonjour,

Votre commande n°{id} d'un montant de {amount} a bien été annulée.
{payment}
À bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => encode($eSale['shop']['farm']['name']),
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'payment' => $payment,
			]);

		} else {

		$title = s("Commande annulée");

		$content = s("Bonjour,

Votre commande pour la livraison du {date} a bien été annulée.

À bientôt,
Vos producteurs", [
			'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate']),
			]);

		}


		return \mail\DesignUi::format($eSale['shop']['farm'], $title, $content);

	}

	public static function getSaleMarketTicket(\selling\Sale $eSale): array {

		$products = '<h3>'.s("Produits").'</h3>';

		foreach($eSale['cItem'] as $eItem) {

			if($eItem['packaging'] === NULL) {
				$number = \selling\UnitUi::getValue($eItem['number'], $eItem['unit']);
			} else {
				$number = p("{value} colis de {quantity}", "{value} colis de {quantity}", $eItem['number'], ['quantity' => \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'])]);
			}


			if($eItem['unit']) {
				$unit = \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
			} else {
				$unit = '';
			}
			$unitPrice = \util\TextUi::money($eItem['unitPrice']).$unit;

			$products .= encode($eItem['name'])."\t: ".$number.' x '.$unitPrice;

		}

		$products .= '<h3>'.s("Totaux").'</h3>';
		$products .= s("Total HT\t: {price}", ['price' => str_pad(\util\TextUi::money($eSale['priceExcludingVat']), 10, ' ', STR_PAD_LEFT)])."\n";

		foreach($eSale['vatByRate'] as $vatByRate) {

			$products .= s("TVA à {vatRate}%\t: {amount}", ['vatRate' => $vatByRate['vatRate'], 'amount' => str_pad(\util\TextUi::money($vatByRate['vat']), 10, ' ', STR_PAD_LEFT)])."\n";

		}

		$products .= s("Total TTC\t: {price}", ['price' => str_pad(\util\TextUi::money($eSale['priceIncludingVat']), 10, ' ', STR_PAD_LEFT)]);

		$title = s("Votre achat du {date}", ['date' => \util\DateUi::numeric($eSale['statusAt'], \util\DateUi::DATE)]);

		$content = s("Bonjour,

Voici le reçu de votre achat réalisé le {date} d'un montant de {amount} :

{products}

Merci pour votre visite et à bientôt,
{farm}", [
			'date' => \util\DateUi::numeric($eSale['statusAt'], \util\DateUi::DATE_HOUR_MINUTE),
			'farm' => encode($eSale['farm']['name']),
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'products' => '<pre>'.$products.'</pre>',
			]);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getCardSaleFailed(\selling\Sale $eSale, bool $test = FALSE): array {

		$title = s("Le paiement de la commande n°{id} a échoué", ['id' => $eSale['document']]);

		if($test) {
			$link = LIME_URL;
		} else {
			$link = \shop\ShopUi::paymentUrl($eSale['shop'], $eSale['shopDate']);
		}

		$get = fn($payment) => s("Bonjour,

Votre paiement par carte bancaire d'un montant de {amount} pour la commande n°{id} a échoué.
Votre commande n'a pas donc pas été validée et votre compte n'a pas été débité.

{payment}

Merci et à bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => $eSale['farm']['name'],
			'payment' => $payment,
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'link' => $link
			]);

		$text = $get(s("Vous pouvez tenter de payer à nouveau cette commande en cliquant sur ce lien :")."\n".$link);
		$html = nl2br($get(\mail\DesignUi::getButton($link, s("Retenter un paiement")))."\n");

		return [
			$title,
			\mail\DesignUi::encapsulateText($eSale['farm'], $text),
			\mail\DesignUi::encapsulateHtml($eSale['farm'], $html)
		];
	}

	public static function getOrderEnd(Date $eDate, array $sales, \Collection $cItem): array {

		$hasVat = $eDate['farm']->getSelling('hasVat');

		if($sales['number'] === 0) {

			$text = s("Vous n'avez pas reçu de commande cette fois-ci.

Bonne réception,
{siteName}");

			return [
				s("Pas de commande pour le {date} sur {shop}", [
					'shop' => $eDate['shop']['name'],
					'date' => \util\DateUi::numeric($eDate['deliveryDate']),
				]),
				\mail\DesignUi::encapsulateText($eDate['farm'], $text),
				\mail\DesignUi::encapsulateHtml($eDate['farm'], nl2br($text))
			];

		}

		$price = \util\TextUi::money($eDate['type'] === Date::PRO ? $sales['priceExcludingVat'] : $sales['priceIncludingVat']);

		if($hasVat and $eDate['type'] === Date::PRO) {
			$price .= ' '.\selling\CustomerUi::getTaxes(Date::PRO);
		}

		$title = p("✅ {value} commande pour le {date} sur {shop}", "✅ {value} commandes pour le {date} sur {shop}", $sales['number'], ['shop' => $eDate['shop']['name'], 'date' => \util\DateUi::numeric($eDate['deliveryDate'])]);

		$items = '';

		foreach($cItem as $eItem) {

			if($eItem['packaging'] === NULL) {
				$number = \selling\UnitUi::getValue($eItem['number'], $eItem['unit']);
			} else {
				$number = p("{value} colis de {quantity}", "{value} colis de {quantity}", $eItem['number'], ['quantity' => \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'])]);
			}

			$items .= '- '.s("{item} : {number}", ['item' => $eItem['name'], 'number' => $number])."\n";

		}

		$intro = p("Vous avez reçu {value} commande d'un montant total de {price} pour la livraison du {date} sur votre boutique {shop}.", "Vous avez reçu {value} commandes d'un montant total de {price} pour la livraison du {date} sur votre boutique {shop}.", $sales['number'], [
			'shop' => encode($eDate['shop']['name']),
			'date' => \util\DateUi::numeric($eDate['deliveryDate']),
			'price' => $price
		])."\n\n";
		$products = s("Vos clients ont commandé :

{items}

Bonne réception,
{siteName}", ['items' => $items]);

		$content = $intro;
		$content .= \mail\DesignUi::getButton(\Lime::getUrl().ShopUi::adminDateUrl($eDate['farm'], $eDate).'/', s("Voir la vente"))."\n\n";
		$content .= $products;

		return [
			$title,
			\mail\DesignUi::encapsulateText($eDate['farm'], $content),
			\mail\DesignUi::encapsulateHtml($eDate['farm'], nl2br($content))
		];
	}

}
?>
