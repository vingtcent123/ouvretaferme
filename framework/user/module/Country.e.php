<?php
namespace user;

class Country extends CountryElement {

	public static function form(): mixed {
		return \user\CountryLib::getForForm();
	}

	public static function ask(Country $e): mixed {
		return \user\CountryLib::ask($e);
	}

}
?>