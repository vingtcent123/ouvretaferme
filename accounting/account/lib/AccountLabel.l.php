<?php
namespace account;

class AccountLabelLib {

	const ACCOUNT_LABEL_SIZE = 8;

	public static function pad(string $account): string {

		return str_pad(mb_substr($account, 0, self::ACCOUNT_LABEL_SIZE), self::ACCOUNT_LABEL_SIZE, '0');

	}

	public static function isDeposit(string $accountLabel): bool {
		return (\account\AccountLabelLib::isFromClass($accountLabel, \account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEPOSIT_CLASS) or
				\account\AccountLabelLib::isFromClass($accountLabel, \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEPOSIT_CLASS));
	}

	public static function isFromClasses(string $accountLabel, array $classes): bool {
		foreach($classes as $class) {
			if(self::isFromClass($accountLabel, $class)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public static function isFromClass(string $accountLabel, string $class): bool {
		return str_starts_with($accountLabel, $class);
	}

	public static function isChargeClass(string $account): bool {

		return str_starts_with($account, (string)\account\AccountSetting::CHARGE_ACCOUNT_CLASS);

	}

	public static function isProductClass(string $account): bool {

		return str_starts_with($account, (string)\account\AccountSetting::PRODUCT_ACCOUNT_CLASS);

	}

	public static function isSelfConsumption(string $account): bool {

		return (
			str_starts_with($account, AccountSetting::OLD_PRODUCT_SELF_CONSUMPTION_ACCOUNT_CLASS) or
			str_starts_with($account, AccountSetting::PRODUCT_SELF_CONSUMPTION_ACCOUNT_CLASS)
		);

	}

	public static function isAmortizationOrDepreciationClass(string $class): bool {

		return (
			str_starts_with($class, (string)AccountSetting::ASSET_AMORTIZATION_GENERAL_CLASS) or // Immos : amortissement
			str_starts_with($class, (string)AccountSetting::ASSET_DEPRECIATION_CLASS) or // Immos : dépréciations
			str_starts_with($class, (string)AccountSetting::STOCK_DEPRECIATION_CLASS) or // Stocks : dépréciations
			str_starts_with($class, (string)AccountSetting::THIRD_PARTY_DEPRECIATION_CLASS) or // Tiers : dépréciations
			str_starts_with($class, (string)AccountSetting::FINANCIAL_DEPRECIATION_CLASS) or // Finance : dépréciations
			str_starts_with($class, (string)AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS) // Subvention
		);

	}

	public static function getClassFromAmortizationOrDepreciationClass(string $class): string {

		if(self::isAmortizationOrDepreciationClass($class) === FALSE) {
			return '';
		}

		if(str_starts_with($class, (string)AccountSetting::INVESTMENT_GRANT_AMORTIZATION_CLASS)) {
			return AccountSetting::EQUIPMENT_GRANT_CLASS;
		}

		return mb_substr($class, 0, 1).mb_substr($class, 2);

	}

	public static function getAmortizationClassFromClass(string $class): ?string {

		if(
			str_starts_with($class, (string)AccountSetting::INTANGIBLE_ASSETS_CLASS) === FALSE and
			str_starts_with($class, (string)AccountSetting::TANGIBLE_ASSETS_CLASS) === FALSE and
			str_starts_with($class, (string)AccountSetting::TANGIBLE_LIVING_ASSETS_CLASS) === FALSE
		) {
			return NULL;
		}

		return mb_substr($class, 0, 1).'8'.mb_substr($class, 1);

	}

	public static function geDepreciationClassFromClass(string $class): ?string {

		if(
			str_starts_with($class, (string)AccountSetting::ASSET_GENERAL_CLASS) === FALSE and
			str_starts_with($class, (string)AccountSetting::STOCK_GENERAL_CLASS) === FALSE and
			str_starts_with($class, (string)AccountSetting::THIRD_PARTY_GENERAL_CLASS) === FALSE
		) {
			return NULL;
		}
		return mb_substr($class, 0, 1).'9'.mb_substr($class, 1);

	}

}
?>
