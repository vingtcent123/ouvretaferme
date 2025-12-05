<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$search = new Search([
		'accountLabel' => GET('accountLabel'),
		'financialYear' => $data->eFinancialYear,
	], GET('sort'));

	$data->search = clone $search;

})
	->get('/journal/grand-livre', function($data) {

		$data->cOperation = \journal\OperationLib::getAllForBook($data->search);
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
