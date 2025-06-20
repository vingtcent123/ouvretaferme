<?php
new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
	// TODO Récupérer et sauvegarder dynamiquement
	$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get('index', function($data) {

		\Setting::set('main\viewJournal', 'book');

		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->cOperation = \journal\OperationLib::getAllForBook($search);
		$data->cAccount = \account\AccountLib::getAll();

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$content = pdf\PdfLib::generate($data->eFarm, $data->eFinancialYear, \pdf\PdfElement::JOURNAL_BOOK);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameBook($data->eFarm).'.pdf';

		throw new PdfAction($content, $filename);
	});
?>
