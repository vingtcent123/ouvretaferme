<?php
namespace farm;

class SurveyLib extends SurveyCrud {

	public static function getPropertiesCreate(): array {
		return array_diff(Survey::model()->getProperties(), ['id', 'farm', 'createdAt']);
	}

	public static function create(Survey $e): void {

		try {
			Survey::model()->insert($e);
		} catch(\DuplicateException) {
			Survey::fail('farm.duplicate');
		}

	}

	public static function existsByFarm(Farm $eFarm): bool {

		return Survey::model()
			->whereFarm($eFarm)
			->exists();

	}

}
?>
