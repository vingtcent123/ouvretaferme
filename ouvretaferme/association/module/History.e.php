<?php
namespace association;

class History extends HistoryElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\Farm::getSelection(),
			'sale' => ['id', 'priceIncludingVat', 'cPayment' => \selling\PaymentLib::delegateBySale()],
		];

	}

	public function canReadDocument(): bool {

		$this->expects(['farm']);

		return $this['paymentStatus'] === History::SUCCESS and $this['farm']->canManage();

	}


	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('amount.check', function(?int $amount) use($p): bool {

				if($p->isBuilt('type') === FALSE or $this['type'] === self::DONATION or post_exists('fromAdmin')) {
					return TRUE;
				}

				if($amount === NULL or $amount < AssociationSetting::MEMBERSHIP_FEE) {
					return FALSE;
				}

				return TRUE;

			})
			->setCallback('amount.checkDonation', function(?int $amount) use($p): bool {

				if($p->isBuilt('type') === FALSE or $this['type'] === self::MEMBERSHIP or post_exists('fromAdmin')) {
					return TRUE;
				}

				if($amount === NULL or $amount === 0) {
					return FALSE;
				}

				return TRUE;
			})
			->setCallback('terms.check', function(?string $terms) use($p): bool {

				if($p->isBuilt('type') === FALSE or $this['type'] === self::DONATION) {
					return TRUE;
				}

				return $terms !== NULL;

			})
			->setCallback('membership.check', function(?int $membership) use($p): bool {

				if($p->isBuilt('type') === FALSE or $this['type'] === self::DONATION) {
					return TRUE;
				}

				if($membership === NULL) {
					return FALSE;
				}

				return History::model()
					->whereStatus(History::VALID)
					->whereMembership($membership)
					->count() === 0;

			});

		parent::build($properties, $input, $p);

	}

}
?>
