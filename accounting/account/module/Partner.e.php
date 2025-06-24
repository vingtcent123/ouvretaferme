<?php
namespace account;

class Partner extends PartnerElement {


	public static function getSelection(): array {

		return parent::getSelection() + [
				'updatedBy' => ['id', 'firstName', 'lastName'],
			];

	}

	public function isValid() {
		return $this->notEmpty() and $this['expiresAt'] > date('Y-m-d H:i:s');
	}
}
?>
