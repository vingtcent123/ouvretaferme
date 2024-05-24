<?php
namespace shop;

class ShopLib extends ShopCrud {

	public static function getPropertiesCreate(): array {
		return ['fqn', 'name', 'email', 'description', 'frequency'];
	}

	public static function getPropertiesUpdate(): array {
		return ['fqn', 'name', 'email', 'description', 'frequency', 'orderMin', 'shipping', 'shippingUntil'];
	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Shop::model()
			->select(Shop::getSelection())
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getForList(\farm\Farm $eFarm): \Collection {

		return Shop::model()
			->select(Shop::getSelection() + [
				'eDate' => fn(\shop\Shop $eShop) => \shop\DateLib::getMostRelevantByShop($eShop, one: TRUE, withSales: TRUE)
			])
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByCustomers(\Collection $cCustomer, string $last = '1 YEAR'): \Collection {

		return \selling\Sale::model()
			->select([
				'shop' => Shop::getSelection() + [
					'eDate' => fn(Shop $eShop) => \shop\DateLib::getMostRelevantByShop($eShop, one: TRUE)
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
					$eShop['eDate']->notEmpty()
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

		$e->expects(['farm']);

		Shop::model()->beginTransaction();

		try {

			Shop::model()->insert($e);

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

	public static function getAroundByFarm(\farm\Farm $eFarm, string $period = '1 MONTH'): \Collection {

		$cShop = self::getByFarm($eFarm);

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

			PointLib::deleteByShop($e);

			Redirect::model()
				->whereShop($e)
				->delete();

			Shop::model()->delete($e);

		Shop::model()->commit();

	}

}
