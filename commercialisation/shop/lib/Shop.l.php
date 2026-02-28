<?php
namespace shop;

class ShopLib extends ShopCrud {

	public static function getPropertiesCreate(): array {
		return ['fqn', 'name', 'type', 'email', 'description', 'opening', 'openingDelivery', 'openingFrequency', 'shared'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function($eShop) {

			$properties = ['fqn', 'name', 'type', 'email', 'description', 'opening', 'openingDelivery', 'openingFrequency', 'orderMin', 'shipping', 'shippingUntil', 'limitCustomers', 'limitGroups', 'hasPoint', 'comment', 'commentCaption'];

			if($eShop['paymentCard'] === FALSE) {
				$properties[] = 'approximate';
			}

			$properties[] = 'outOfStock';
			$properties[] = 'productInput';

			if($eShop['shared']) {
				$properties[] = 'sharedGroup';
				$properties[] = 'sharedCategory';
				$properties[] = 'sharedExport';
				array_delete($properties, 'shipping');
				array_delete($properties, 'shippingUntil');
			}

			return $properties;

		};
	}

	public static function getPropertiesCustomize(Shop $eShop): array {

		$properties = ['customBackground', 'customColor', 'customFont', 'customTitleFont', 'customDesign'];

		if($eShop->acceptCustomTabs()) {
			$properties[] = 'customTabs';
		}

		return $properties;

	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL): \Collection {

		return Shop::model()
			->select(Shop::getSelection())
			->whereFarm($eFarm)
			->whereStatus('!=', Shop::DELETED)
			->whereType($type, if: $type !== NULL)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getList(\farm\Farm $eFarm, bool $withDeleted = FALSE): \Collection {

		$cShare = Share::model()
			->select(Share::getSelection())
			->whereFarm($eFarm)
			->getCollection(index: 'shop');

		$cShop = Shop::model()
			->select(ShopElement::getSelection() + [
				'farm' => ['name'],
				'date' => fn(Shop $eShop) => \shop\DateLib::getMostRelevantByShop($eShop, one: TRUE, withSales: TRUE),
				'share' => fn(Shop $eShop) => $cShare[$eShop['id']] ?? new Share()
			])
			->or(
				fn() => $this->whereFarm($eFarm),
				fn() => $this->whereId('IN', $cShare->getColumnCollection('shop')),
			)
			->whereStatus('!=', Shop::DELETED, if: $withDeleted === FALSE)
			->sort(['name' => SORT_ASC])
			->getCollection(index: 'id');

		$ccShop = new \Collection([
			'selling' => new \Collection(),
			'admin' => new \Collection(),
		])->setDepth(2);

		foreach($cShop as $eShop) {

			if($eShop['farm']['id'] === $eFarm['id'] and $eShop['shared']) {
				$ccShop['admin'][$eShop['id']] = $eShop;
			}

			if($eShop['share']->notEmpty() or $eShop['shared'] === FALSE) {
				$ccShop['selling'][$eShop['id']] = $eShop;
			}

		}

		return $ccShop;

	}

	public static function getByKey(string $key): Shop {

		if(str_contains($key, '-') === FALSE) {
			return new Shop();
		}

		[$id, $hash] = explode('-', $key, 2);

		return Shop::model()
			->select(Shop::getSelection())
			->whereId((int)$id)
			->whereStatus('!=', Shop::DELETED)
			->whereShared(TRUE)
			->whereSharedHash($hash)
			->whereSharedHashExpiresAt('>=', new \Sql('CURDATE()'))
			->get();

	}

	public static function getByCustomers(\Collection $cCustomer, string $last = '1 YEAR'): \Collection {

		return \selling\Sale::model()
			->select([
				'shop' => Shop::getSelection() + [
					'date' => fn(Shop $eShop) => \shop\DateLib::getMostRelevantByShop($eShop, one: TRUE),
					'farm' => ['name', 'vignette', 'url']
				]
			])
			->whereCustomer('IN', $cCustomer)
			->whereShop('!=', NULL)
			->where('createdAt > NOW() - INTERVAL '.$last)
			->group('shop')
			->getColumn('shop')
			->filter(function($eShop) {
				return (
					$eShop['status'] === Shop::OPEN and
					$eShop['date']->notEmpty()
				);
			})
			->sort('name');

	}

	public static function applySharedCatalogs(Shop $eShop, Date $eDate): void {

		$eDate['catalogs'] = [];
		$eShop['ccRange']->map(function(Range $eShopRange) use ($eDate) {
			if($eShopRange['status'] === Range::AUTO) {
				$eDate['catalogs'][] = $eShopRange['catalog']['id'];
			}
		}, depth: 2);

	}

	public static function create(Shop $e): void {

		$e->expects(['farm', 'shared']);

		Shop::model()->beginTransaction();

		try {

			if($e['shared']) {

				$e['sharedGroup'] = Shop::PRODUCT;
				$e['sharedCategory'] = FALSE;
				$e['hasPayment'] = FALSE;
				$e['paymentOffline'] = FALSE;

				$e['customTabs'] = TRUE;

			} else {

				$e['customTabs'] = FALSE;

			}

			switch($e['type']) {

				case Shop::PRIVATE :
					$e['customDesign'] = Shop::GRID;
					$e['productInput'] = Shop::PLUS_MINUS;
					break;

				case Shop::PRO :
					$e['customDesign'] = Shop::LINE;
					$e['productInput'] = Shop::TEXT;
					break;

			}

			Redirect::model()
				->whereFqn($e['fqn'])
				->delete();

			Shop::model()->insert($e);

			if($e['farm']['hasShops'] === FALSE) {

				self::updateHasShop($e['farm'], TRUE);

			}

			Shop::model()->commit();

		} catch(\DuplicateException $e) {

			Shop::model()->rollBack();

			$duplicate = $e->getInfo()['duplicate'];

			if($duplicate === ['fqn']) {
				Shop::fail('fqn.duplicate');
			} else {
				Shop::fail('name.duplicate');
			}

		}


	}

	public static function joinShared(Shop $e, \farm\Farm $eFarm): void {

		$eShare = new Share([
			'shop' => $e,
			'farm' => $eFarm
		]);

		try {

			ShareLib::create($eShare);

			self::updateHasShop($eFarm, TRUE);

		} catch(\DuplicateException) {
		}

	}

	public static function regenerateSharedHash(Shop $e): void {

		Shop::model()
			->whereShared(TRUE)
			->update($e, [
				'sharedHash' => $e->getNewSharedHash(),
				'sharedHashExpiresAt' => new \Sql('CURDATE() + INTERVAL 7 DAY')
			]);

	}

	public static function update(Shop $e, array $properties): void {

		Shop::model()->beginTransaction();

		if(in_array('fqn', $properties)) {

			$e->expects(['oldFqn']);

			if($e['fqn'] !== $e['oldFqn']) {

				Redirect::model()
					->whereFqn($e['fqn'])
					->delete();

				// Ajoute l'ancienne adresse à la liste des redirections
				$eRedirect = new Redirect([
					'shop' => $e,
					'fqn' => $e['oldFqn']
				]);

				Redirect::model()
					->option('add-replace')
					->insert($eRedirect);

			}

		}

		if(in_array('hasPayment', $properties)) {

			if($e['hasPayment']) {

				$e['paymentMethod'] = new \payment\Method();
				$properties[] = 'paymentMethod';

			}

		}

		try {

			parent::update($e, $properties);

			Shop::model()->commit();

		} catch(\DuplicateException $e) {

			Shop::model()->rollBack();

			$duplicate = $e->getInfo()['duplicate'];

			if($duplicate === ['fqn']) {
				Shop::fail('fqn.duplicate');
			} else {
				Shop::fail('name.duplicate');
			}

		}

	}

	/**
	 * Récupère le rôle du client.
	 *
	 */
	public static function getRoleForSignUp(): \user\Role {

		return \user\Role::model()
			->select(['id', 'fqn', 'name', 'emoji'])
			->whereFqn('customer')
			->get();

	}

	public static function getAroundByFarm(\farm\Farm $eFarm, string $type, string $period = '1 MONTH'): \Collection {

		$cShop = self::getList($eFarm)['selling'];

		Shop::model()
			->select([
				'id', 'name',
				'cDate' => Date::model()
					->select(Date::getSelection())
					->where('deliveryDate IS NULL OR deliveryDate > NOW() - INTERVAL '.$period)
					->whereType($type)
					->sort(['deliveryDate' => SORT_ASC])
					->delegateCollection('shop')
			])
			->whereType($type)
			->sort('name')
			->get($cShop);

		return $cShop;

	}

	public static function delete(Shop $e): void {

		$e->expects(['farm']);

		Shop::model()->beginTransaction();

			Redirect::model()
				->whereShop($e)
				->delete();

			Range::model()
				->whereShop($e)
				->delete();

			Share::model()
				->whereShop($e)
				->delete();

			Department::model()
				->whereShop($e)
				->delete();


			if(Date::model()
				->whereShop($e)
				->exists()) {

				$e['status'] = Shop::DELETED;

				Shop::model()
					->select('status')
					->update($e);

				$cDate = Date::model()
					->select(Date::getSelection() + [
						'shop' => ['shared']
					])
					->whereShop($e)
					->whereStatus('!=', Date::CLOSED)
					->getCollection();

				foreach($cDate as $eDate) {
					DateLib::close($eDate);
				}

			} else {
				Shop::model()->delete($e);
			}

			self::updateHasShop($e['farm']);

		Shop::model()->commit();

	}

	protected static function updateHasShop(\farm\Farm $eFarm, ?bool $newValue = NULL): void {

		\farm\Farm::model()->update($eFarm, [
			'hasShops' => $newValue ?? (
				Shop::model()
					->whereFarm($eFarm)
					->whereStatus('!=', Shop::DELETED)
					->exists() or
				Share::model()
					->whereFarm($eFarm)
					->exists()
				)
		]);

	}

	public static function getPayments(Shop $eShop, Point $ePoint, \selling\Customer $eCustomer): array {

		$payments = [];
		$isOffline = FALSE;

		if($ePoint->empty()) {

			if($eShop['paymentCard']) {
				$payments[] = \payment\MethodLib::ONLINE_CARD;
			}
			if($eShop['paymentTransfer']) {
				$payments[] = \payment\MethodLib::TRANSFER;
			}
			if($eShop['paymentOffline']) {
				$isOffline = TRUE;
			}

		} else {

			if(
				$ePoint['paymentCard'] or
				($ePoint['paymentCard'] === NULL and $eShop['paymentCard'])
			) {
				$payments[] = \payment\MethodLib::ONLINE_CARD;
			}
			if(
				$ePoint['paymentTransfer'] or
				($ePoint['paymentTransfer'] === NULL and $eShop['paymentTransfer'])
			) {
				$payments[] = \payment\MethodLib::TRANSFER;
			}
			if(
				$ePoint['paymentOffline'] or
				($ePoint['paymentOffline'] === NULL and $eShop['paymentOffline'])
			) {
				$isOffline = TRUE;
			}

		}

		foreach($payments as $key => $payment) {

			if(
				$payment === \payment\MethodLib::ONLINE_CARD and
				\payment\StripeLib::getByFarm($eShop['farm'])->empty()
			) {
				unset($payments[$key]);
			}

		}

		if($payments) {

			$payments = \payment\Method::model()
				->whereFarm($eShop['farm'])
				->whereFqn('IN', $payments)
				->or(
					fn() => $this->where(fn() => 'JSON_LENGTH(limitCustomers) = 0 AND JSON_LENGTH(limitGroups) = 0'),
					fn() => $this->where(fn() => 'JSON_CONTAINS(limitCustomers, \''.$eCustomer['id'].'\')'),
					fn() => $this->where(fn() => 'JSON_OVERLAPS(limitGroups, "['.implode(', ', $eCustomer['groups']).']")')
				)
				->where(fn() => 'JSON_LENGTH(excludeCustomers) = 0 OR JSON_CONTAINS(excludeCustomers, \''.$eCustomer['id'].'\') = 0')
				->where(fn() => 'JSON_LENGTH(excludeGroups) = 0 OR JSON_OVERLAPS(excludeGroups, "['.implode(', ', $eCustomer['groups']).']") = 0')
				->getColumn('fqn');

		}

		if($isOffline) {
			$payments[] = NULL;
		}

		return $payments;

	}

}
