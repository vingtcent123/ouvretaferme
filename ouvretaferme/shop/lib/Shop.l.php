<?php
namespace shop;

class ShopLib extends ShopCrud {

	public static function getPropertiesCreate(): array {
		return ['fqn', 'name', 'type', 'email', 'description', 'frequency', 'shared'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function($eShop) {

			$properties = ['fqn', 'name', 'type', 'email', 'description', 'frequency', 'orderMin', 'shipping', 'shippingUntil', 'limitCustomers', 'hasPoint', 'comment', 'commentCaption'];

			if($eShop['hasPayment'] === FALSE) {
				$properties[] = 'approximate';
			}

			if($eShop['shared']) {
				$properties[] = 'sharedGroup';
				array_delete($properties, 'shipping');
				array_delete($properties, 'shippingUntil');
			}

			return $properties;

		};
	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL): \Collection {

		return Shop::model()
			->select(Shop::getSelection())
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getList(\farm\Farm $eFarm): \Collection {

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

	public static function getEmails(Shop $e): array {

        $cCustomer = \selling\Sale::model()
            ->select(['customer'])
            ->whereShop($e)
            ->wherePreparationStatus(\selling\Sale::DELIVERED)
            ->group('customer')
            ->getColumn('customer');

        $emails = \selling\Customer::model()
            ->select([
                'user' => ['email', 'status']
            ])
            ->whereId('IN', $cCustomer)
            ->whereUser('!=', NULL)
            ->or(
                fn() => $this->whereEmailOptIn(TRUE),
                fn() => $this->whereEmailOptIn(NULL)
            )
            ->whereEmailOptOut(TRUE)
            ->getColumn('user')
            ->filter(fn($eUser) => $eUser['status'] === \user\User::ACTIVE)
            ->getColumn('email');

        return $emails;

	}

	public static function create(Shop $e): void {

		$e->expects(['farm', 'shared']);

		Shop::model()->beginTransaction();

		try {

			if($e['shared']) {

				$e['sharedGroup'] = Shop::PRODUCT;
				$e['hasPayment'] = FALSE;
				$e['paymentOffline'] = FALSE;
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

	public static function getAroundByFarm(\farm\Farm $eFarm, ?string $type = NULL, string $period = '1 MONTH'): \Collection {

		$cShop = self::getByFarm($eFarm, $type);

		Shop::model()
			->select([
				'id', 'name',
				'cDate' => Date::model()
					->select(Date::getSelection())
					->where('deliveryDate BETWEEN NOW() - INTERVAL '.$period.' AND NOW() + INTERVAL '.$period)
					->sort(['deliveryDate' => SORT_ASC])
					->delegateCollection('shop')
			])
			->sort('name')
			->get($cShop);

		return $cShop;

	}

	public static function delete(Shop $e): void {

		$e->expects(['farm']);

		if(Date::model()
			->whereShop($e)
			->exists()) {
			throw new \NotExpectedAction('Existing dates.');
		}

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

			Shop::model()->delete($e);

			self::updateHasShop($e['farm']);

		Shop::model()->commit();

	}

	protected static function updateHasShop(\farm\Farm $eFarm, ?bool $newValue = NULL): void {

		\farm\Farm::model()->update($eFarm, [
			'hasShops' => $newValue ?? (
				Shop::model()
					->whereFarm($eFarm)
					->exists() or
				Share::model()
					->whereFarm($eFarm)
					->exists()
				)
		]);

	}

}
