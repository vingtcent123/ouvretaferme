<?php
namespace cash;

class Register extends RegisterElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'paymentMethod' => fn($e) => \payment\MethodLib::ask($e['method'], $e['farm']),
		];

	}

}
?>