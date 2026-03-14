<?php
namespace selling;

class PaymentLinkLib extends PaymentLinkCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'customer', 'validUntil', 'source', 'invoice', 'sale', 'amountIncludingVat'];
	}

	// Désactive les liens de paiement en attente de règlement
	public static function deactivate(Invoice|Sale $eElement): void {

		$eElement->expects(['farm']);

		$ePaymentLink = PaymentLink::model()
			->select(PaymentLink::getSelection())
			->whereInvoice($eElement, if: $eElement instanceof Invoice)
			->whereSale($eElement, if: $eElement instanceof Sale)
			->whereStatus(PaymentLink::ACTIVE)
			->get();

		if($ePaymentLink->empty()) {
			return;
		}

		PaymentLink::model()->beginTransaction();

			PaymentLink::model()->update($ePaymentLink, ['status' => PaymentLink::INACTIVE]);

			$eStripeFarm = \payment\StripeLib::getByFarm($eElement['farm']);
			\payment\StripeLinkLib::toggleActivation($eStripeFarm, $ePaymentLink, FALSE);

		PaymentLink::model()->commit();

	}

	public static function create(PaymentLink $e): void {

		if($e['source'] === PaymentLink::INVOICE) {
			$e['invoice']->validate('acceptStripeLink');
		} else if($e['source'] === PaymentLink::SALE) {
			$e['sale']->validate('acceptStripeLink');
		} else {
			return;
		}

		PaymentLink::model()->beginTransaction();

			parent::create($e);

			$eStripeFarm = \payment\StripeLib::getByFarm($e['farm']);

			$paymentLinkStripe = \payment\StripeLinkLib::create($eStripeFarm, $e);

			if($paymentLinkStripe) {
				PaymentLink::model()->update($e, ['paymentLinkId' => $paymentLinkStripe['id'], 'url' => $paymentLinkStripe['url']]);
			}

		PaymentLink::model()->commit();

	}

	public static function getValidByElement(Invoice|Sale $eElement): \Collection {

		return PaymentLink::model()
			->select(PaymentLink::getSelection())
			->whereFarm($eElement['farm'])
			->whereSale($eElement, if: $eElement instanceof Sale)
			->whereInvoice($eElement, if: $eElement instanceof Invoice)
			->whereStatus(PaymentLink::ACTIVE)
			->where(new \Sql('validUntil > CURDATE()'))
			->getCollection();

	}

	public static function paymentSucceed(PaymentLink $ePaymentLink, array $event) {

		$object = $event['data']['object'];

		PaymentLink::model()->beginTransaction();

			$eElement = $ePaymentLink->getElement();

			$ePayment = new \selling\Payment([
				'method' => \payment\MethodLib::getByFqn($eElement['farm'], \payment\MethodLib::ONLINE_CARD),
				'amountIncludingVat' => $ePaymentLink['amountIncludingVat'],
				'status' => \selling\Payment::PAID,
				'paidAt' => currentDate(),
				'onlineCheckoutId' => $object['id'],
				'onlinePaymentIntentId' => $object['payment_intent'],
			]);

			\selling\PaymentTransactionLib::add($eElement, new \Collection([$ePayment]));

			\selling\PaymentLink::model()->update($ePaymentLink, ['status' => \selling\PaymentLink::PAID, 'paidAt' => currentDate()]);

			if($eElement instanceof Sale) {
				\selling\HistoryLib::createBySale($eElement, 'shop-payment-link-succeeded', 'Stripe event #'.$object['payment_intent']);
			}

		PaymentLink::model()->commit();

		new \mail\SendLib()
			->setTo($eElement['farm']['legalEmail'])
			->setContent(...PaymentLinkUi::getPaymentEmail($ePaymentLink))
			->send();

	}

}
