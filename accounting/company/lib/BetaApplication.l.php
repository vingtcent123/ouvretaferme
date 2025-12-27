<?php
namespace company;

class BetaApplicationLib extends BetaApplicationCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'accountingType', 'taxSystem', 'hasVat', 'vatFrequency', 'discord', 'accountingHelped', 'helpComment', 'accountingLevel', 'comment', 'hasSoftware'];
	}

	public static function getApplicationByFarm(\farm\Farm $eFarm): BetaApplication {

		return BetaApplication::model()
			->select(BetaApplication::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	public static function create(BetaApplication $e): void {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

		$e['farm'] = $eFarm;

		parent::create($e);

	}
}
?>
