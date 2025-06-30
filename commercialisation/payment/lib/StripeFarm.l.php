<?php
namespace payment;

class StripeFarmLib extends StripeFarmCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'apiSecretKey'];
	}

	public static function create(StripeFarm $e): void {

		StripeFarm::model()->beginTransaction();

			parent::create($e);

			StripeLib::createWebhook($e);

		StripeFarm::model()->commit();

	}

	public static function delete(StripeFarm $e): void {

		$e->expects(['farm']);

		StripeFarm::model()->beginTransaction();

		\shop\Shop::model()
			->whereFarm($e['farm'])
			->update([
				'paymentCard' => FALSE
			]);

		\shop\Point::model()
			->whereFarm($e['farm'])
			->update([
				'paymentCard' => NULL
			]);

		parent::delete($e);

		StripeFarm::model()->commit();

	}

}
?>
