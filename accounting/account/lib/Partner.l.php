<?php
namespace account;

class PartnerLib extends PartnerCrud {

	public static function getByPartner(string $partner): Partner {

		return Partner::model()
			->select(Partner::getSelection())
			->wherePartner($partner)
			->get();
	}

}
?>
