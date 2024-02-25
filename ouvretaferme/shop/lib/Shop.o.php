<?php
namespace shop;

class ShopObserverLib {

	public static function saleConfirmed(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$eConfiguration = \selling\ConfigurationLib::getByFarm($eSale['farm']);
		$replyTo = $eSale['shop']['email'] ?? $eConfiguration['legalEmail'];

		(new \mail\MailLib())
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, $cProduct))
			->send('shop');

	}

	public static function saleUpdated(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$eConfiguration = \selling\ConfigurationLib::getByFarm($eSale['farm']);
		$replyTo = $eSale['shop']['email'] ?? $eConfiguration['legalEmail'];

		(new \mail\MailLib())
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleUpdated($eSale, $cItem, $cProduct))
			->send('shop');

	}

	public static function salePaid(\selling\Sale $eSale, \Collection $cItem, \Collection $cProduct): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user'],
			'shopPoint'
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);
		$eSale['shopPoint'] = PointLib::getById($eSale['shopPoint']);

		$eConfiguration = \selling\ConfigurationLib::getByFarm($eSale['farm']);
		$replyTo = $eSale['shop']['email'] ?? $eConfiguration['legalEmail'];

		(new \mail\MailLib())
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleConfirmed($eSale, $cItem, $cProduct))
			->send('shop');

	}

	public static function saleFailed(\selling\Sale $eSale): void {

		$eSale->expects([
			'shop' => ['email'],
			'farm' => ['name'],
			'customer' => ['user']
		]);

		$eUser = \user\UserLib::getById($eSale['customer']['user']);

		$eConfiguration = \selling\ConfigurationLib::getByFarm($eSale['farm']);
		$replyTo = $eSale['shop']['email'] ?? $eConfiguration['legalEmail'];

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_CARD :
				(new \mail\MailLib())
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

		$eConfiguration = \selling\ConfigurationLib::getByFarm($eSale['farm']);
		$replyTo = $eSale['shop']['email'] ?? $eConfiguration['legalEmail'];

		(new \mail\MailLib())
			->setReplyTo($replyTo)
			->setFromName($eSale['farm']['name'])
			->addTo($eUser['email'])
			->setContent(...MailUi::getSaleCanceled($eSale))
			->send('shop');

	}

}
?>
