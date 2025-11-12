<?php
namespace account;

class ClassLib {

	public static function pad(string $account): string {

		return str_pad($account, 8, '0');

	}

	public static function isFromClass(string $account, string $class): bool {

		return mb_substr($account, 0, mb_strlen($class)) === $class;

	}

	public static function isAmortizationOrDepreciationClass(string $class): bool {

		return (
			// Immos
			(mb_substr($class, 1, 1) === '8' and (in_array(mb_substr($class, 0, 1), ['2']))) or
			// Stocks, tiers, financiers
			(mb_substr($class, 1, 1) === '9' and (in_array(mb_substr($class, 0, 1), ['3', '4', '5']))) or
			// Subventions
			mb_substr($class, 0, 3) === AccountSetting::GRANT_ASSET_AMORTIZATION_CLASS
		);

	}

	public static function getClassFromAmortizationOrDepreciationClass(string $class): string {

		if(self::isAmortizationOrDepreciationClass($class) === FALSE) {
			return '';
		}

		if(mb_substr($class, 0, 3) === AccountSetting::GRANT_ASSET_AMORTIZATION_CLASS) {
			return mb_substr($class, 0, 2).mb_substr($class, 3);
		}

		return mb_substr($class, 0, 1).mb_substr($class, 2);

	}


}
?>
