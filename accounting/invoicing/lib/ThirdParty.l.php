<?php
namespace invoicing;

Class ThirdPartyLib extends ThirdPartyCrud {

	public static function getByElectronicAddress(string $electronicAddress): ThirdParty {

		return ThirdParty::model()
			->select(ThirdParty::getSelection())
			->whereElectronicAddress($electronicAddress)
			->get();

	}

}
