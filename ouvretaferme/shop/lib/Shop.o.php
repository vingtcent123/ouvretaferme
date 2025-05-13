<?php
namespace shop;

class ShopObserverLib {

	public static function saleConfirmed(\selling\Sale $eSale, \user\User $eUser, \Collection $cItem, bool $group): void {

		$eSale->expects([
			'shop' => ['shared', 'email', 'emailNewSale'],
		]);

		if(
			$eSale['shop']->isPersonal() or
			($eSale['shop']->isShared() and $group)
		) {

			$eUser = \user\UserLib::getById($eUser);

			self::newSend($eSale)
				->addTo($eUser['email'])
				->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, $group, self::getTemplate($eSale)))
				->send('shop');

		}

		if(
			$eSale['shop']['emailNewSale'] and
			$group === FALSE
		) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
				->setContent(...MailUi::getNewFarmSale('confirmed', $eSale, $cItem))
				->send('shop');

		}

	}

	public static function saleUpdated(\selling\Sale $eSale, \user\User $eUser, \Collection $cItem, bool $group): void {

		$eSale->expects([
			'shop' => ['email', 'shared'],
		]);

		if(
			$eSale['shop']->isPersonal() or
			($eSale['shop']->isShared() and $group)
		) {

			$eUser = \user\UserLib::getById($eUser);

			self::newSend($eSale)
				->addTo($eUser['email'])
				->setContent(...MailUi::getSaleUpdated($eSale, $cItem, $group, self::getTemplate($eSale)))
				->send('shop');

		}

		if(
			$eSale['shop']['emailNewSale'] and
			$group === FALSE
		) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
				->setContent(...MailUi::getNewFarmSale('updated', $eSale, $cItem))
				->send('shop');

		}

	}

	public static function salePaid(\selling\Sale $eSale, \user\User $eUser, \Collection $cItem): void {

		$eSale->expects([
			'shop' => [
				'email',
				'farm' => ['name']
			],
			'farm' => ['name'],
			'customer' => ['user'],
			'shopPoint'
		]);

		$eUser = \user\UserLib::getById($eUser);
		$eSale['shopPoint'] = PointLib::getById($eSale['shopPoint']);

		self::newSend($eSale)
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, FALSE, self::getTemplate($eSale)))
			->send('shop');

	}

	private static function getTemplate(\selling\Sale $eSale): ?string {

		return \mail\CustomizeLib::getTemplateByShop($eSale['shop'], $eSale['shopPoint']->notEmpty() ? match($eSale['shopPoint']['type']) {
			Point::PLACE => \mail\Customize::SHOP_CONFIRMED_PLACE,
			Point::HOME => \mail\Customize::SHOP_CONFIRMED_HOME,
		} : \mail\Customize::SHOP_CONFIRMED_PLACE);

	}

	public static function saleFailed(\selling\Sale $eSale, \user\User $eUser): void {

		$eSale->expects([
			'shop' => [
				'email',
				'farm' => ['name']
			],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eUser);

		if($eSale->isPaymentOnline()) {

				self::newSend($eSale)
					->addTo($eUser['email'])
					->setContent(...MailUi::getCardSaleFailed($eSale))
					->send('shop');

		}

	}

	public static function saleCanceled(\selling\Sale $eSale, bool $group): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		if(
			$eSale['shop']->isPersonal() or
			($eSale['shop']->isShared() and $group)
		) {

			self::newSend($eSale)
				->addTo($eUser['email'])
				->setContent(...MailUi::getSaleCanceled($eSale))
				->send('shop');

		}

		if(
			$eSale['shop']['emailNewSale'] and
			$group === FALSE
		) {

			new \mail\MailLib()
				->addTo(self::getEmail($eSale))
				->setContent(...MailUi::getCancelFarmSale($eSale))
				->send('shop');

		}

		// On remet en circuit les produits en stock
		if($group === FALSE) {

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

	}

	private static function newSend(\selling\Sale $eSale): \mail\MailLib {

		$eShop = $eSale['shop'];
		$eFarm = $eSale['shop']['farm'];

		return new \mail\MailLib()
			->setReplyTo(self::getReplyTo($eFarm, $eShop))
			->setFromName($eFarm['name']);

	}

	private static function getEmail(\selling\Sale $eSale): ?string {

		$eSale->expects([
			'shop' => ['shared', 'email'],
			'farm'
		]);

		if($eSale['shop']['shared']) {
			return $eSale['farm']->selling()['legalEmail'];
		} else {
			return $eSale['shop']['email'] ?? $eSale['farm']->selling()['legalEmail'];
		}

	}

	private static function getReplyTo(\farm\Farm $eFarm, Shop $eShop): ?string {

		$eShop->expects(['shared', 'email']);

		if($eShop['shared']) {
			return $eShop['email'];
		} else {
			return $eShop['email'] ?? $eFarm->selling()['legalEmail'];
		}

	}

}
?>
