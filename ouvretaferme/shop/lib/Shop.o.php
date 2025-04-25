<?php
namespace shop;

class ShopObserverLib {

	public static function saleConfirmed(\selling\Sale $eSale, \Collection $cItem): void {

		$eSale->expects([
			'shop' => ['shared', 'email', 'emailNewSale'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		self::newSend($eSale)
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, self::getTemplate($eSale)))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
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

		self::newSend($eSale)
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleUpdated($eSale, $cItem, self::getTemplate($eSale)))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
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

		self::newSend($eSale)
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

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_CARD :
				self::newSend($eSale)
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

		self::newSend($eSale)
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleCanceled($eSale))
			->send('shop');

		if($eSale['shop']['emailNewSale']) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
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

		ProductLib::addAvailable($eSale, $cItem);

	}

	private static function newSend(\selling\Sale $eSale): \mail\MailLib {

		$eSale->expects([
			'farm'
		]);

		return new \mail\MailLib()
			->setReplyTo(self::getEmail($eSale))
			->setFromName($eSale['farm']['name']);

	}

	private static function getEmail(\selling\Sale $eSale): ?string {

		$eSale->expects([
			'shop' => ['shared', 'email'],
			'farm'
		]);

		if($eSale['shop']['shared']) {
			return $eSale['shop']['email'];
		} else {
			return $eSale['shop']['email'] ?? $eSale['farm']->selling()['legalEmail'];
		}

	}

}
?>
