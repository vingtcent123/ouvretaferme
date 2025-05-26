<?php
namespace payment;

class MethodLib extends MethodCrud {

	const CARD = 'card';
	const ONLINE_CARD = 'online-card';
	const CASH = 'cash';
	const CHECK = 'check';
	const TRANSFER = 'transfer';

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function isSelectable(\farm\Farm $eFarm, Method $eMethod): bool {

		return Method::model()
			->select(Method::getSelection())
			->or(
				fn() => $this->whereFarm(NULL),
				fn() => $this->whereFarm($eFarm)
			)
			->whereOnline(FALSE)
			->get($eMethod);
	}

	public static function getByFarm(\farm\Farm $eFarm, ?bool $online, ?bool $onlyActive = TRUE): \Collection {

		return Method::model()
			->select(Method::getSelection())
			->or(
				fn() => $this->whereFarm($eFarm),
				fn() => $this->whereFarm(NULL)
			)
			->whereOnline($online, if: $online !== NULL)
			->whereStatus(Method::ACTIVE, if: $onlyActive !== NULL)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getOnline(): \Collection {

		return Method::model()
			->select(Method::getSelection())
			->whereOnline(TRUE)
			->getCollection();

	}

	public static function delete(Method $e): void {

		$e->expects(['id', 'farm']);

		if(
			\selling\Sale::model()
       ->whereFarm($e['farm'])
       ->wherePaymentMethod($e)
       ->exists() or
			\selling\Payment::model()
	      ->whereFarm($e['farm'])
	      ->whereMethod($e)
	      ->exists()
		) {
			Method::fail('deleteUsed');
			return;
		}

		parent::delete($e);

	}
}

