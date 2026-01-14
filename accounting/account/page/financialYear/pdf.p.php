<?php
namespace account;

new \account\FinancialYearPage(function($data) {
	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new \RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->post('generate', function($data) {

		$data->type = GET('type');
		if(in_array($data->type, FinancialYearDocumentLib::getTypes()) === FALSE) {
			throw new \NotExistsAction();
		}

		$data->eFinancialYear = FinancialYearLib::getById(POST('id'));

		FinancialYearLib::regenerate($data->eFarm, $data->eFinancialYear, $data->type);
		\company\CompanyCronLib::addConfiguration($data->eFarm, \company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_DOCUMENT, \company\CompanyCron::WAITING, $data->eFinancialYear['id']);

		throw new \ReloadLayerAction('account', 'FinancialYear::pdf.generationStacked', [
			'type' => $data->type,
		]);

	})
	->post('check', function($data) {

		if(FinancialYearDocumentLib::countWaiting($data->eFarm['eFinancialYear']) === 0) {
			throw new \ReloadLayerAction();
		}

		throw new \JsonAction([
			 'result' => 'not-finished',
		]);

	})
	->get('download', function($data) {

		$type = GET('type');
		$eFinancialYear = FinancialYearLib::getById(GET('id'));
		if($eFinancialYear->empty() or in_array($type, FinancialYearDocumentLib::getTypes()) === FALSE) {
			throw new \NotExistsAction();
		}

		switch($type) {
			case FinancialYearDocumentLib::OPENING:
				$eFinancialYear->validate('acceptDownloadOpen');
				break;
			case FinancialYearDocumentLib::CLOSING:
				$eFinancialYear->validate('acceptDownloadClose');
				break;
		}

		$content = FinancialYearDocumentLib::getContent($eFinancialYear, $type);

		if($content === NULL) {
			throw new \VoidAction();
		}

		$filename = new \account\PdfUi()->getFilename($eFinancialYear, $type);

		throw new \PdfAction($content, $filename);

	})
;

