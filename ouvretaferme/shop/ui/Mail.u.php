<?php
namespace shop;

class MailUi {

	public static function getSaleUpdated(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): array {

		$eSale->expects([
			'shopPoint' => ['type'],
			'shop' => ['paymentOfflineHow']
		]);

		$title = match($eSale['shopPoint']['type']) {
			Point::HOME => s("Commande n°{id} modifiée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			Point::PLACE => s("Commande n°{id} modifiée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])])
		};

		return [
			$title,
			...self::getSaleContent($eSale, $cItem, $cProduct)
		];

	}

	public static function getSaleConfirmed(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): array {

		$eSale->expects([
			'shopPoint' => ['type'],
			'shop' => ['paymentOfflineHow']
		]);

		$title = match($eSale['shopPoint']['type']) {
			Point::HOME => s("Commande n°{id} validée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			Point::PLACE => s("Commande n°{id} validée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])])
		};

		return [
			$title,
			...self::getSaleContent($eSale, $cItem, $cProduct)
		];
	}

	protected static function getSaleContent(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): array {

		$content = function(array $variables) {

			return s("Bonjour,

Votre commande n°{id} d'un montant de {amount} a bien été enregistrée.

Vous avez commandé :
{products}

{payment}

{delivery}

Merci et à bientôt,
{farm}", $variables);

		};

		$text = $content(self::getSaleVariables('text', $eSale, $cItem, $cProduct));

		$html = \mail\DesignUi::getBanner($eSale['farm']);
		$html .= nl2br($content(self::getSaleVariables('html', $eSale, $cItem, $cProduct)));

		return [
			$text,
			$html
		];

	}

	protected static function getSaleVariables(string $mode, \selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): array {

		$encode = fn($value) => ($mode === 'html') ? encode($value) : $value;

		$ePoint = $eSale['shopPoint'];

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_SEPA :
				$payment = s("Vous avez choisi de régler cette commande par prélèvement bancaire.");
				break;

			case \selling\Sale::ONLINE_CARD :
				$payment = s("Vous avez choisi de régler cette commande par carte bancaire.");
				break;

			case \selling\Sale::OFFLINE :
				$payment = s("Vous avez choisi de régler cette commande en direct avec votre producteur.");
				if($eSale['shop']['paymentOfflineHow']) {
					$payment .= "\n".$eSale['shop']['paymentOfflineHow'];
				}
				break;

			default :
				throw new \Exception('Not compatible');

		}

		$products = ($mode === 'html') ? '<ul>' : '';

		foreach($cItem as $eItem) {

			$eProduct = $cProduct[$eItem['product']['id']]['product'];

			$products .= ($mode === 'html') ? '<li>' : '- ';
			$products .= $encode($eProduct->getName()).' : '.\main\UnitUi::getValue($eItem['number'], $eProduct['unit'], noWrap: FALSE);
			$products .= ($mode === 'html') ? '</li>' : "\n";


		}

		$products .= ($mode === 'html') ? '</ul>' : '';

		$delivery = '';

		switch($ePoint['type']) {

			case Point::HOME :
				$delivery .= s("Votre commande vous sera livrée le {date} à l'adresse suivante :", ['date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])])."\n\n";
				$delivery .= ($mode === 'html') ? '<div style="padding-left: 1rem; border-left: 3px solid #888888">' : '';
					$delivery .= $encode($eSale->getDeliveryAddress());
				$delivery .= ($mode === 'html') ? '</div>' : '';
				break;

			case Point::PLACE :
				$delivery = s("Vous pourrez venir retirer votre commande le {date} au point de retrait suivant :", ['date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])])."\n\n";
				$delivery .= ($mode === 'html') ? '<div style="padding-left: 1rem; border-left: 3px solid #888888">' : '';
				$delivery .= $encode($ePoint['name'])."\n";
				if($ePoint['description']) {
					$delivery .= $encode($ePoint['description'])."\n\n";
				}
				$delivery .= $encode($ePoint['address'])."\n";
				$delivery .= $encode($ePoint['place']);
				$delivery .= ($mode === 'html') ? '</div>' : '';
				break;

		};

		return [
			'id' => $eSale['document'],
			'farm' => $eSale['farm']['name'],
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'products' => $products,
			'payment' => $payment,
			'delivery' => $delivery,
		];

	}

	public static function getSaleCanceled(\selling\Sale $eSale): array {

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_SEPA :
				$payment = s("Vous ne serez donc pas prélevé du montant de cette commande.")."\n";
				break;

			case \selling\Sale::OFFLINE :
				$payment = '';
				break;

			default :
				$payment = '';
				break;

		}

		$title = s("Commande n°{id} annulée", ['id' => $eSale['document']]);

		$text = s("Bonjour,

Votre commande n°{id} d'un montant de {amount} a bien été annulée.
{payment}
À bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => $eSale['farm']['name'],
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'payment' => $payment,
			]);

		$html = \mail\DesignUi::getBanner($eSale['farm']);
		$html .= nl2br(encode($text));

		return [
			$title,
			$text,
			$html
		];
	}

	public static function getCardSaleFailed(\selling\Sale $eSale, bool $test = FALSE): array {

		$title = s("Le paiement de la commande n°{id} a échoué", ['id' => $eSale['document']]);

		if($test) {
			$link = LIME_URL;
		} else {
			$link = \shop\ShopUi::dateUrl($eSale['shop'], $eSale['shopDate'], 'paiement', showDomain: TRUE);
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

		$html = \mail\DesignUi::getBanner($eSale['farm']);
		$html .= nl2br($get(\mail\DesignUi::getButton($link, s("Retenter un paiement")))."\n");

		return [
			$title,
			$text,
			$html
		];
	}

}
?>
