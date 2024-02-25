<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	public static function getByProviderId(string $provider, string $providerId): Payment {

		return Payment::model()
			->select(Payment::getSelection())
			->whereProvider($provider)
			->whereProviderId($providerId)
			->get();

	}

	public static function hasSuccess(Sale $eSale): bool {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereStatus(Payment::SUCCESS)
			->exists();

	}

	public static function updateStatus(string $provider, string $providerId, string $newStatus): int {

		return Payment::model()
			->whereProvider($provider)
			->whereProviderId($providerId)
			->update([
				'status' => $newStatus
			]);

	}

	public static function createBySale(Sale $eSale, string $provider, ?string $providerId = NULL): Payment {

		$eSale->expects(['customer', 'farm']);


		$e = new Payment([
			'sale' => $eSale,
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'provider' => $provider,
			'providerId' => $providerId
		]);

		Payment::model()->insert($e);

		return $e;

	}

}
?>
