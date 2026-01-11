<?php
new \asset\AssetPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->get('index', function($data) {

		if(get_exists('created')) {
			\asset\CsvLib::reset($data->eFarm);
		}
		throw new ViewAction($data);

	})
	->post('doImportAssets', function($data) {

		$fw = new FailWatch();

		\asset\CsvLib::uploadAssets($data->eFarm);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::urlAsset($data->eFarm).'/csv:importAssets?id='.$data->eFarm['id']);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlAsset($data->eFarm).'/csv:importAssets?id='.$data->eFarm['id'].'&error='.$fw->getLast());
		}

	})
	->get('importAssets', function($data) {

		if(get_exists('reset')) {
			\asset\CsvLib::reset($data->eFarm);
		}

		$data->data = \asset\CsvLib::getAssets($data->eFarm);

		throw new ViewAction($data, $data->data ? ':importFile' : NULL);

	})
	->post('doCreateAssets', function($data) {

		$data->data = \asset\CsvLib::getAssets($data->eFarm);

		if(
			$data->data === NULL or
			$data->data['errorsCount'] > 0
		) {
			throw new RedirectAction(\company\CompanyUi::urlAsset($data->eFarm).'/csv:importAssets?id='.$data->e['id']);
		}

		$fw = new FailWatch();

		\asset\CsvLib::importAssets($data->eFarm, $data->data['import']);

		$fw->validate();

		throw new RedirectAction(\company\CompanyUi::urlAsset($data->eFarm).'/csv?id='.$data->eFarm['id'].'&created');

	})
;
