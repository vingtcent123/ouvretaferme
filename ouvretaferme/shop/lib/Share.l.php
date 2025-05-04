<?php
namespace shop;

class ShareLib extends ShareCrud {

	private static array $cacheList = [];
	private static array $cacheMatch = [];

	public static function getPropertiesUpdate(): \Closure {

		return function(Share $e) {

			if($e->isSelf()) {
				return ['paymentMethod'];
			} else {
				return ['label'];
			}

		};
	}

	public static function getByShop(Shop $eShop): \Collection {

		self::$cacheList[$eShop['id']] ??= Share::model()
			->select(Share::getSelection())
			->whereShop($eShop)
			->sort(['position' => SORT_ASC])
			->getCollection(index: 'farm');

		return self::$cacheList[$eShop['id']];

	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Share::model()
			->select(Share::getSelection())
			->whereFarm($eFarm)
			->getCollection();

	}

	public static function match(Shop $eShop, \farm\Farm $eFarm): bool {

		self::$cacheMatch[$eShop['id']][$eFarm['id']] ??= Share::model()
			->whereShop($eShop)
			->whereFarm($eFarm)
			->exists();

		return self::$cacheMatch[$eShop['id']][$eFarm['id']];

	}

	public static function create(Share $e): void {

		Share::model()->beginTransaction();

		$shares = Share::model()
			->whereShop($e['shop'])
			->count();

		$e['position'] = $shares + 1;

		parent::create($e);

		Share::model()->commit();

	}

	public static function incrementPosition(Share $e, int $increment): void {

		$e->expects(['shop']);

		if($increment !== -1 and $increment !== 1) {
			return;
		}

		$eShop = $e['shop'];

		Share::model()->beginTransaction();

		$position = Share::model()
			->whereId($e)
			->getValue('position');

		switch($increment) {

			case -1 :

				Share::model()
					->whereShop($eShop)
					->wherePosition($position - 1)
					->update([
						'position' => $position
					]);

				Share::model()->update($e, [
						'position' => $position - 1
					]);

				break;

			case 1 :

				Share::model()
					->whereShop($eShop)
					->wherePosition($position + 1)
					->update([
						'position' => $position
					]);

				Share::model()->update($e, [
						'position' => $position + 1
					]);

				break;

		}

		Share::model()->commit();

	}

	public static function delete(Share $e): void {

		Share::model()->beginTransaction();

			$eShop = $e['shop'];
			$eFarm = $e['farm'];

			$cRange = Range::model()
				->select(RangeElement::getSelection())
				->whereShop($eShop)
				->whereFarm($eFarm)
				->getCollection();

			foreach($cRange as $eRange) {
				RangeLib::dissociate($eRange, TRUE);
			}

			parent::delete($e);

			self::reorder($eShop);

		Share::model()->commit();

	}

	public static function reorder(\shop\Shop $eShop): void {

		$cShare = self::getByShop($eShop);

		$position = 1;

		foreach($cShare as $eShare) {

			Share::model()->update($eShare, [
				'position' => $position++
			]);

		}

	}

}
