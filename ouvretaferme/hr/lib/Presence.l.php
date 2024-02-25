<?php
namespace hr;

class PresenceLib extends PresenceCrud {

	public static function getPropertiesCreate(): array {
		return array_merge(['user'], self::getPropertiesUpdate());
	}

	public static function getPropertiesUpdate(): array {
		return ['from', 'to'];
	}

	public static function create(Presence $e): void {

		$e->expects(['farm', 'user']);

		parent::create($e);

	}

	public static function fillUsers(\farm\Farm $eFarm, \Collection $cUser): void {

		\user\User::model()
			->select([
				'cPresence' => Presence::model()
					->select(Presence::getSelection())
					->whereFarm($eFarm)
					->sort([
						'from' => SORT_ASC
					])
					->delegateCollection('user')
			])
			->get($cUser);

	}

	public static function getByUser(\farm\Farm $eFarm, \user\User $eUser): \Collection {

		return Presence::model()
			->select(Presence::getSelection())
			->whereUser($eUser)
			->whereFarm($eFarm)
			->sort([
				'from' => SORT_DESC
			])
			->getCollection();

	}

	public static function isPresentBetween(\farm\Farm $eFarm, \user\User $eUser, string $start, ?string $stop): bool {

		return Presence::model()
			->or(
				fn() => $this
					->whereFrom('<=', $start)
					->whereTo(NULL),
				fn() => $this
					->whereFrom('<=', $start)
					->whereTo('>=', $start),
				fn() => $this
					->whereFrom('>', $start)
					->whereTo(NULL),
				fn() => $this
					->whereFrom('>', $start)
					->whereFrom('<=', $stop, if: $stop !== NULL)
			)
			->whereFarm($eFarm)
			->whereUser($eUser)
			->exists();

	}

	public static function getBetween(\farm\Farm $eFarm, string $start, string $stop): \Collection {

		return Presence::model()
			->select(Presence::getSelection())
			->join(\user\User::model(), 'm1.user = m2.id')
			->whereFrom('<=', $stop)
			->or(
				fn() => $this->whereTo('>=', $start),
				fn() => $this->whereTo(NULL)
			)
			->whereFarm($eFarm)
			->where('m2.status', \user\User::ACTIVE)
			->sort([
				'from' => SORT_DESC
			])
			->getCollection(NULL, NULL, ['user', NULL]);

	}

}
?>