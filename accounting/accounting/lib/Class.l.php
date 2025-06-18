<?php
namespace accounting;

class ClassLib {

	public static function pad(string $account): string {

		return str_pad($account, 8, '0');

	}

	public static function isFromClass(string $account, string $class): bool {

		return mb_substr($account, 0, strlen($class)) === $class;

	}

}
?>
