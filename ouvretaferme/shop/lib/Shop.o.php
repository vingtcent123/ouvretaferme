<?php
namespace shop;

class ShopObserverLib {

	public static function saleConfirmed(\selling\Sale $eSale, \Collection $cItem): void {

		$eSale->expects([
			'shop' => ['email', 'emailNewSale'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$replyTo = $eSale['shop']['email'];

		new \mail\MailLib()
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, self::getTemplate($eSale)))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo($replyTo)
				->setContent(...MailUi::getNewFarmSale('confirmed', $eSale, $cItem))
				->send('shop');

		}

	}

	public static function saleUpdated(\selling\Sale $eSale, \Collection $cItem): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$replyTo = $eSale['shop']['email'];

		new \mail\MailLib()
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleUpdated($eSale, $cItem, self::getTemplate($eSale)))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo($replyTo)
				->setContent(...MailUi::getNewFarmSale('updated', $eSale, $cItem))
				->send('shop');

		}

	}

	public static function salePaid(\selling\Sale $eSale, \Collection $cItem): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user'],
			'shopPoint'
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);
		$eSale['shopPoint'] = PointLib::getById($eSale['shopPoint']);

		$replyTo = $eSale['shop']['email'];

		new \mail\MailLib()
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, self::getTemplate($eSale)))
			->send('shop');

	}

	private static function getTemplate(\selling\Sale $eSale): ?string {

		return \mail\CustomizeLib::getTemplate($eSale['farm'], $eSale['shopPoint']->notEmpty() ? match($eSale['shopPoint']['type']) {
			Point::PLACE => \mail\Customize::SHOP_CONFIRMED_PLACE,
			Point::HOME => \mail\Customize::SHOP_CONFIRMED_HOME,
		} : \mail\Customize::SHOP_CONFIRMED_PLACE, $eSale['shop']);

	}

	public static function saleFailed(\selling\Sale $eSale): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$replyTo = $eSale['shop']['email'];

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_CARD :
				new \mail\MailLib()
					->setReplyTo($replyTo)
					->setFromName($eSale['farm']['name'])
					->addTo($eUser['email'])
					->setContent(...MailUi::getCardSaleFailed($eSale))
					->send('shop');
				break;

		}

	}

	public static function saleCanceled(\selling\Sale $eSale): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$replyTo = $eSale['shop']['email'];

		new \mail\MailLib()
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleCanceled($eSale))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo($replyTo)
				->setContent(...MailUi::getCancelFarmSale($eSale))
				->send('shop');

		}

		// On remet en circuit les produits en stock
		$cItem = \selling\Item::model()
			->select([
				'shopProduct',
				'number'
			])
			->whereSale($eSale)
			->whereIngredientOf(NULL)
			->getCollection();

		ProductLib::addAvailable($cItem);

	}

}
?>
