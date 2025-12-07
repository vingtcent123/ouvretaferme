<?php
namespace user;

class Country extends CountryElement {

	public static function all(): mixed {
		return \user\CountryLib::getForSignUp();
	}

}
?>