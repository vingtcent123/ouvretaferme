<?php
new \journal\VatDeclarationPage(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
	->create(action: function($data) {

		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->eFinancialYear['lastPeriod'] = \journal\VatDeclarationLib::calculateLastPeriod($data->eFinancialYear);

		$data->cOperationWaiting = \journal\OperationLib::getAllForVatDeclaration($search);
		$data->vatByType = \journal\VatDeclarationLib::sumByVatType($data->cOperationWaiting);

		throw new ViewAction($data);
	})
	->doCreate(function($data) {

		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/vat?success=journal:VatDeclaration:created');

	})
	->get('pdf', function($data) {

		$data->eVatDeclaration = \journal\VatDeclarationLib::getById(GET('id'));

		$content = pdf\PdfLib::generate(
			$data->eFarm,
			$data->eFinancialYear,
			\pdf\PdfElement::VAT_STATEMENT,
			$data->eVatDeclaration['id']
		);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameVatStatement($data->eFarm).'.pdf';

		throw new PdfAction($content, $filename);

	});
