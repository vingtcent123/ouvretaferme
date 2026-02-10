<?php
namespace selling;

class PaymentPageLib {

	public static function updatePayment(): \Closure {

		return function($data) {

			$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], NULL);

			throw new \ViewAction($data);

		};

	}

	public static function doUpdatePayment(): \Closure {

		return function($data) {

			$fw = new \FailWatch();

			$cPayment = \selling\PaymentTransactionLib::prepare($data->e, $_POST);

			$fw->validate();

			\selling\PaymentTransactionLib::replace($data->e, $cPayment);

			[$package, $element] = explode('\\', $data->e->getModule());
			throw new \ReloadAction($package, $element.'::updatedPayment');

		};

	}

	public static function doUpdateNeverPaid(): \Closure {

		return function($data) {

			\selling\PaymentTransactionLib::updateNeverPaid($data->e);

			throw new \ReloadAction();

		};

	}

	public static function doDeletePayment(): \Closure {

		return function($data) {

			\selling\PaymentTransactionLib::delete($data->e);

			throw new \ReloadLayerAction();

		};

	}

	public static function doUpdatePaymentNotPaidCollection(): \Closure {

		return function($data) {

			$data->c->validate('canWrite', 'acceptUpdatePayment');

			$eMethod = \payment\MethodLib::getById(POST('paymentMethod'));

			if($eMethod->notEmpty()) {
				$eMethod->validate('canUse', 'acceptManualUpdate');
			}

			foreach($data->c as $e) {
				\selling\PaymentTransactionLib::updateNotPaidMethod($e, $eMethod);
			}

			[$package, $element] = explode('\\', $e->getModule());
			throw new \ReloadAction($package, $element.'::paymentMethodUpdated');

		};

	}

	public static function doUpdatePaymentStatusCollection(): \Closure {

		return function($data) {

			$data->c->validate('canWrite', 'acceptPayPayment');

			foreach($data->c as $e) {
				\selling\PaymentTransactionLib::updatePaid($e);
			}

			[$package, $element] = explode('\\', $e->getModule());
			throw new \ReloadAction($package, $element.'::paymentStatusUpdated');

		};

	}

}
?>
