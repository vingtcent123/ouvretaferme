<?php
namespace cash;

class CashLib extends CashCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Cash $e) {

			return match($e['origin']) {

				Cash::BALANCE_INITIAL => ['date', 'amountIncludingVat']

			};

		};

	}

	public static function getPropertiesUpdate(): array {
		return [];
	}

	public static function create(Cash $e): void {

		Cash::model()->beginTransaction();

			match($e['origin']) {

				Cash::BALANCE_INITIAL => self::createBalanceInitial($e)

			};

			\securing\SignatureLib::signSale($e);

		Cash::model()->beginTransaction();

	}

	public static function createBalanceInitial(Cash $e): void {

		$e['type'] = Cash::CREDIT;
		$e['balance'] = $e['amountIncludingVat'];
		$e['amountExcludingVat'] = $e['amountIncludingVat'];
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['comment'] = CashUi::getInitial();
		$e['status'] = Cash::VALID;

	}

	public static function getByRegister(Register $eRegister, ?int $page = NULL, \Search $search = new \Search()): \Collection {

		$number = ($page === NULL) ? NULL : 100;
		$position = ($page === NULL) ? NULL : $page * $number;

		return Cash::model()
			->select(Cash::getSelection())
			->whereRegister($eRegister)
			->whereType($search->get('type'), if: $search->get('type'))
			->sort([
				'id' => SORT_DESC
			])
			->getCollection($position, $number);

	}

}
?>
