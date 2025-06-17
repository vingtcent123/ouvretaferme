<?php
namespace hr;

class AbsenceLib extends AbsenceCrud {

	public static function getPropertiesCreate(): array {
		return self::getPropertiesUpdate();
	}

	public static function getPropertiesUpdate(): array {
		return ['from', 'to', 'duration', 'type'];
	}

	public static function create(Absence $e): void {

		$e->expects(['farm', 'user']);

		parent::create($e);

	}

	public static function fillUsers(\farm\Farm $eFarm, \Collection $cUser): void {

		\user\User::model()
			->select([
				'cAbsence' => Absence::model()
					->select(Absence::getSelection())
					->whereFarm($eFarm)
					->sort([
						'from' => SORT_DESC
					])
					->delegateCollection('user')
			])
			->get($cUser);

	}

	public static function getByUser(\farm\Farm $eFarm, \user\User $eUser): \Collection {

		return Absence::model()
			->select(Absence::getSelection())
			->whereUser($eUser)
			->whereFarm($eFarm)
			->sort([
				'from' => SORT_DESC
			])
			->getCollection();

	}

}
?>