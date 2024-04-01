<?php
namespace shop;

class MailUi {

	public static function getSaleUpdated(\selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint' => ['type'],
			'shop' => ['paymentOfflineHow', 'paymentTransferHow']
		]);

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('updated', $eSale, $cItem, $template),
			Point::PLACE => self::getSalePlace('updated', $eSale, $cItem, $template)
		};

	}

	public static function getSaleConfirmed(\selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint' => ['type'],
			'shop' => ['paymentOfflineHow', 'paymentTransferHow']
		]);

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('confirmed', $eSale, $cItem, $template),
			Point::PLACE => self::getSalePlace('confirmed', $eSale, $cItem, $template)
		};

	}

	protected static function getSaleHome(string $type, \selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_HOME);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_HOME, $eSale, $cItem);

		$title = match($type) {
			'confirmed' => s("Commande n°{id} validée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande n°{id} modifiée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	protected static function getSalePlace(string $type, \selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_PLACE);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_PLACE, $eSale, $cItem);

		$title = match($type) {
			'confirmed' => s("Commande n°{id} validée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande n°{id} modifiée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getSaleCanceled(\selling\Sale $eSale): array {

		switch($eSale['paymentMethod']) {

			case \selling\Sale::TRANSFER :
				$payment = s("Vous ne serez donc pas facturé du montant de cette commande.")."\n";
				break;

			case \selling\Sale::OFFLINE :
				$payment = '';
				break;

			default :
				$payment = '';
				break;

		}

		$title = s("Commande n°{id} annulée", ['id' => $eSale['document']]);

		$content = s("Bonjour,

Votre commande n°{id} d'un montant de {amount} a bien été annulée.
{payment}
À bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => encode($eSale['farm']['name']),
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'payment' => $payment,
			]);


		return \mail\DesignUi::format($eSale['farm'], $title, $content);

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
