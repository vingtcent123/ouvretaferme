<?php
namespace user;

class Country extends CountryElement {

	public static function form(): mixed {
		return \user\CountryLib::getForForm();
	}

	public static function ask(Country $e): mixed {
		return \user\CountryLib::ask($e);
	}

	public function isFR(): bool {

		return (
			$this->exists() and
			$this['id'] === \user\UserSetting::FR
		);

	}

	public function isBE(): bool {

		return (
			$this->exists() and
			$this['id'] === \user\UserSetting::BE
		);

	}

}
?>