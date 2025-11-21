<?php
namespace account;

class AccountLabelLib {

	public static function pad(string $account): string {

		return str_pad(mb_substr($account, 0, 8), 8, '0');

	}

	public static function isFromClass(string $account, string $class): bool {

		return mb_substr($account, 0, mb_strlen($class)) === $class;

	}

	public static function isChargeClass(string $account): bool {

		return substr($account, 0, mb_strlen(\account\AccountSetting::CHARGE_ACCOUNT_CLASS)) === (string)\account\AccountSetting::CHARGE_ACCOUNT_CLASS;

	}

	public static function isProductClass(string $account): bool {

		return substr($account, 0, mb_strlen(\account\AccountSetting::CHARGE_ACCOUNT_CLASS)) === (string)\account\AccountSetting::PRODUCT_ACCOUNT_CLASS;

	}

	public static function isAmortizationOrDepreciationClass(string $class): bool {

		return (
			in_array(
				mb_substr($class, 0, 2), [
					(string)AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS, // Immos : amortissement
					(string)AccountSetting::ASSET_DEPRECIATION_CLASS, // Immos : dépréciations
					(string)AccountSetting::STOCK_DEPRECIATION_CLASS, // Stocks : dépréciations
					(string)AccountSetting::THIRD_PARTY_DEPRECIATION_CLASS, // Tiers : dépréciations
					(string)AccountSetting::FINANCIAL_DEPRECIATION_CLASS, // Finance : dépréciations
				]) or
			mb_substr($class, 0, 3) === AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS // Subvention
		);

	}

	public static function getClassFromAmortizationOrDepreciationClass(string $class): string {

		if(self::isAmortizationOrDepreciationClass($class) === FALSE) {
			return '';
		}

		if(mb_substr($class, 0, 3) === AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS) {
			return mb_substr($class, 0, 2).mb_substr($class, 3);
		}

		return mb_substr($class, 0, 1).mb_substr($class, 2);

	}

	public static function getAmortizationClassFromClass(string $class): ?string {

		if(in_array(mb_substr($class, 0, 2), [AccountSetting::INTANGIBLE_ASSETS_CLASS, AccountSetting::TANGIBLE_ASSETS_CLASS, AccountSetting::TANGIBLE_LIVING_ASSETS_CLASS]) === FALSE) {
			return NULL;
		}
		return mb_substr($class, 0, 1).'8'.mb_substr($class, 1);

	}

	public static function geDepreciationClassFromClass(string $class): ?string {

		if(in_array(mb_substr($class, 0, 1), [AccountSetting::ASSET_GENERAL_CLASS, AccountSetting::STOCK_GENERAL_CLASS, AccountSetting::THIRD_PARTY_GENERAL_CLASS, AccountSetting::FINANCIAL_GENERAL_CLASS]) === FALSE) {
			return NULL;
		}
		return mb_substr($class, 0, 1).'9'.mb_substr($class, 1);

	}

}
?>
