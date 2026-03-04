<?php
namespace vat;

/**
 * Calculs du bloc "TVA brute"
 */
class RawLib {

	public static function exportations(\Search $search, int $precision): float {

		return round(
			\journal\OperationLib::applySearch($search)
				->select([
				'amount' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float'),
				])
				->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%')
				->whereVatRule(\journal\Operation::VAT_0_EXPORT)
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

	public static function intracom(\Search $search, int $precision): float {

		return round(
			\journal\OperationLib::applySearch($search)
				->select([
				'amount' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float'),
				])
				->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%')
				->whereVatRule(\journal\Operation::VAT_0_INTRACOM)
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

	public static function otherNonTaxable(\Search $search, int $precision): float {

		return round(
			\journal\OperationLib::applySearch($search)
				->select([
				'amount' => new \Sql('SUM(IF(type = "credit", amount, -1 * amount))', 'float'),
				])
				->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS.'%')
				->whereVatRule(\journal\Operation::VAT_0)
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

	public static function dutyFreePurchase(\Search $search, int $precision): float {

		return round(
			\journal\OperationLib::applySearch($search)
				->select([
				'amount' => new \Sql('SUM(IF(type = "debit", amount, -1 * amount))', 'float'),
				])
				->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_BUY_ACCOUNT_CLASS.'%')
				->whereVatRule(\journal\Operation::VAT_0)
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

	public static function assetDisposal(\Search $search, int $precision): float {

		return round(
			\journal\OperationLib::applySearch($search)
				->select([
				'amount' => new \Sql('SUM(IF(type = "debit", amount, -1 * amount))', 'float'),
				])
				->or(
					fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_OTHER_ACCOUNT_CLASS.'%'),
					fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS.'%'),
				)
				->whereVatRule('IN', [\journal\Operation::VAT_STD, \journal\Operation::VAT_STD_COLLECTED])
				->whereAsset('!=', NULL)
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

	public static function assetDisposalTax(\Search $search, int $precision): float {

		return round(
			\journal\Operation::model()
				->select([
					'amount' => new \Sql('SUM(IF(m1.type = "debit", m1.amount, -1 * m1.amount))', 'float'),
				])
				->where('m1.operation IS NOT NULL')
				->join(\journal\Operation::model(), 'm1.operation = m2.id')
				->or(
					fn() => $this->where('m2.accountLabel LIKE "'.\account\AccountSetting::PRODUCT_OTHER_ACCOUNT_CLASS.'%"'),
					fn() => $this->where('m2.accountLabel LIKE "'.\account\AccountSetting::PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS.'%"'),
				)
				->where('m2.vatRule IN ("'. join('", "', [\journal\Operation::VAT_STD, \journal\Operation::VAT_STD_COLLECTED]).'")')
				->where('m2.asset IS NOT NULL')
				->where('m1.date >= "'.$search->get('minDate').'"')
				->where('m1.date <= "'.$search->get('maxDate').'"')
				->get()['amount'] ?? 0.0,
			$precision
		);

	}

}
