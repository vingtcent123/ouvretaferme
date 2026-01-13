<?php
namespace account;

new \account\FinancialYearPage(function($data) {
	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new \RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->applyElement(function($data, \account\FinancialYear $e) {

		$e['nOperation'] = \journal\OperationLib::countByFinancialYear($e);

	})
	->post('generate', function($data) {

		$data->type = GET('type');
		if(in_array($data->type, Pdf::model()->getPropertyEnum('type')) === FALSE) {
			throw new \NotExistsAction();
		}

		$data->eFinancialYear = FinancialYearLib::getById(POST('id'));

		FinancialYearLib::regenerate($data->eFarm, $data->eFinancialYear, $data->type);

		throw new \ViewAction($data);

	})
	->get('download', function($data) {

		$type = GET('type');
		$eFinancialYear = FinancialYearLib::getById(GET('id'));
		if($eFinancialYear->empty() or in_array($type, Pdf::model()->getPropertyEnum('type')) === FALSE) {
			throw new \NotExistsAction();
		}

		switch($type) {
			case Pdf::FINANCIAL_YEAR_OPENING:
				$eFinancialYear->validate('acceptDownloadOpen');
				$field = 'openContent';
				break;
			case Pdf::FINANCIAL_YEAR_CLOSING:
				$eFinancialYear->validate('acceptDownloadClose');
				$field = 'closeContent';
				break;
		}

		$ePdfContent = PdfContentLib::getById($eFinancialYear[$field]['id']);
		$content = \overview\PdfLib::getContentByPdf($ePdfContent);

		$filename = new \account\PdfUi()->getFilename($eFinancialYear, $type);

		throw new \PdfAction($content, $filename);

	})
;

