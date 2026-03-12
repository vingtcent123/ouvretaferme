<?php
namespace association;

class ShopObserverLib {

	public static function salePaid(?string $paymentType, \selling\Sale $eSale): void {

		if($paymentType !== 'association' and $paymentType !== 'membership') { // TODO clean (supprimer $source !== 'membership' le 31 mars 2026)
			return;
		}

		MembershipLib::paymentSucceed($eSale);

	}


	public static function saleFailed(?string $paymentType, \selling\Sale $eSale): void {

		if($paymentType !== 'association' and $paymentType !== 'membership') { // TODO clean (supprimer $source !== 'membership' le 31 mars 2026)
			return;
		}

		MembershipLib::paymentFailed($eSale);

	}
}
