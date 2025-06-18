<?php
new Page(function($data) {

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

})
	->get('index', function($data) {

		\Setting::set('main\viewJournal', 'book');

		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->cOperation = \journal\OperationLib::getAllForBook($search);
		$data->cAccount = \accounting\AccountLib::getAll();

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$content = pdf\PdfLib::generate($data->eCompany, $data->eFinancialYear, \pdf\PdfElement::JOURNAL_BOOK);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameBook($data->eCompany).'.pdf';

		throw new PdfAction($content, $filename);
	});
?>
