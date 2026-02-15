<?php
namespace payment;

class MethodLib extends MethodCrud {

	use \ModuleDeferred;

	const CARD = 'card';
	const ONLINE_CARD = 'online-card';
	const CASH = 'cash';
	const CHECK = 'check';
	const TRANSFER = 'transfer';
	const DIRECT_DEBIT = 'direct-debit';

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function duplicateForFarm(\farm\Farm $eFarm): void {

		$cMethod = Method::model()
			->select(Method::getSelection())
			->whereFarm(NULL)
			->getCollection();

		$cMethod->map(function(Method $eMethod) use($eFarm) {
			$eMethod['id'] = NULL;
			$eMethod['farm'] = $eFarm;
		});

		Method::model()->insert($cMethod);

	}

	public static function isSelectable(\farm\Farm $eFarm, Method $eMethod): bool|Method {

		return Method::model()
			->select(Method::getSelection())
			->whereFarm($eFarm)
			->whereOnline(FALSE)
			->get($eMethod);
	}

	public static function deferred(\farm\Farm $eFarm): \Collection {

		$callback = fn() => Method::model()
			->select(Method::getSelection())
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC])
			->getCollection(index: 'id');

		return self::getCache($eFarm['id'], $callback);

	}

	public static function getByFarm(\farm\Farm $eFarm, ?bool $online, bool $onlyActive = TRUE, ?int $use = Method::SELLING): \Collection {

		return self::askCallback(fn(Method $e) => (
			($online === NULL or $e['online'] === $online) and
			($onlyActive === FALSE or $e['status'] === Method::ACTIVE) and
			($use === NULL or $e['use']->get() & $use)
		), $eFarm);

	}

	public static function getForCash(\farm\Farm $eFarm): \Collection {

		return self::askCallback(fn(Method $e) => in_array($e['fqn'], [MethodLib::CHECK, MethodLib::CASH]) or $e['fqn'] === NULL, $eFarm);

	}

	public static function getByFqn(\farm\Farm $eFarm, string $fqn): Method {

		$cMethod = self::askCallback(fn(Method $e) => $e['fqn'] === $fqn, $eFarm);

		if($cMethod->notEmpty()) {
			return $cMethod->first();
		} else {
			return new Method();
		}

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

