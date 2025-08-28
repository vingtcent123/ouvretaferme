<?php
namespace association;

class HistoryLib extends HistoryCrud {

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereFarm($eFarm)
			->sort(['paidAt' => SORT_DESC, 'createdAt' => SORT_DESC])
			->getCollection();

	}

	public static function updateByPaymentIntentId(string $paymentIntentId, array $values): void {

		History::model()
			->wherePaymentIntentId($paymentIntentId)
			->update($values);
	}

	public static function associatePaymentIntentId(string $id): void {

		$eStripeFarm = MembershipLib::getAssociationStripeFarm();

		try {
			$checkout = \payment\StripeLib::getStripeCheckoutSessionFromPaymentIntent($eStripeFarm, $id);
		}
		catch(\Exception $e) {
			trigger_error("Stripe: ".$e->getMessage());
			return;
		}

		if($checkout['data'] === []) {
			return;
		}

		History::model()
			->whereCheckoutId($checkout['data'][0]['id'])
			->update([
				'paymentIntentId' => $id
			]);

	}

	public static function getByPaymentIntentId(string $id): History {

		$eHistory = new History();

		History::model()
			->select(History::getSelection())
			->wherePaymentIntentId($id)
			->get($eHistory);

		if($eHistory->notEmpty()) {
			return $eHistory;
		}

		self::associatePaymentIntentId($id);

		History::model()
			->select(History::getSelection())
			->wherePaymentIntentId($id)
			->get($eHistory);

		return $eHistory;

	}

}
?>
