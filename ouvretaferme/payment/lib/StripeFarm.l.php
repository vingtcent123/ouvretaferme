<?php
namespace payment;

class StripeFarmLib extends StripeFarmCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'apiSecretKey', 'webhookSecretKey'];
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
				'paymentCard' => FALSE
			]);

		parent::delete($e);

		StripeFarm::model()->commit();

	}

}
?>
