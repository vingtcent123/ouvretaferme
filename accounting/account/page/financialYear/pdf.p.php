<?php
namespace account;

new \account\FinancialYearPage(function($data) {
	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new \RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

})
	->post('check', function($data) {

		if(FinancialYearDocumentLib::countWaiting($data->eFarm['eFinancialYear']) === 0) {
			throw new \ReloadLayerAction();
		}

		throw new \JsonAction([
			 'result' => 'not-finished',
		]);

	})
	->read('download', function($data) {

		$type = GET('type');
		if($data->e->empty() or in_array($type, FinancialYearDocumentLib::getTypes()) === FALSE) {
			throw new \NotExistsAction();
		}

		if(in_array($type, [FinancialYearDocumentLib::OPENING, FinancialYearDocumentLib::OPENING_DETAILED])) {
			$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->e);
			$hasPrevious = $data->eFinancialYearPrevious->notEmpty();
			$eFinancialYearDocument = FinancialYearDocumentLib::getDocument($data->e, $type);
			$hasContent = ($hasPrevious and $data->e->isOpen() and in_array($type, [FinancialYearDocumentLib::OPENING, FinancialYearDocumentLib::OPENING_DETAILED]) and $eFinancialYearDocument->notEmpty() and $eFinancialYearDocument['status'] === FinancialYearDocument::SUCCESS);
		} else {
			$hasContent = FALSE;
		}

		if($data->e->isClosed() and $hasContent) {

			$content = FinancialYearDocumentLib::getContent($data->e, $type);

		} else {

			// Génération du document à la volée
			$content = \overview\PdfLib::generateDocument($data->eFarm, $data->e, $type);

		}

		if($content === NULL) {
			throw new \VoidAction();
		}

		$filename = new \account\PdfUi()->getFilename($data->e, $type);

		throw new \PdfAction($content, $filename);

	})
;

