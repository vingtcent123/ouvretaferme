<?php
new Page(function($data) {

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

})
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();
		\Setting::set('main\viewJournal', 'journal');

		$data->eThirdParty = get_exists('thirdParty')
			? \journal\ThirdPartyLib::getById(GET('thirdParty', 'int'))
			: NULL;

		$search = new Search([
			'date' => GET('date'),
			'accountLabel' => GET('accountLabel'),
			'description' => GET('description'),
			'type' => GET('type'),
			'document' => GET('document'),
			'thirdParty' => GET('thirdParty'),
			'asset' => GET('asset'),
		], GET('sort'));

		$search->set('cashflowFilter', GET('cashflowFilter', 'bool'));

		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;
		// Ne pas ouvrir le bloc de recherche
		$search->set('financialYear', $data->eFinancialYear);

		$data->eCashflow = \bank\CashflowLib::getById(GET('cashflow'));
		if($data->eCashflow->exists() === TRUE) {
			$search->set('cashflow', GET('cashflow'));
		}

		if($data->eCompany->isAccrualAccounting()) {

			$code = GET('code');
			if(in_array($code, \journal\Operation::model()->getProperty('journalCode')) === FALSE) {
				$code = NULL;
			}
			$search->set('journalCode', $code);

		}

		$data->cOperation = \journal\OperationLib::getAllForJournal($search, $hasSort);
		$data->cAccount = \accounting\AccountLib::getAll();

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$content = pdf\PdfLib::generate($data->eCompany, $data->eFinancialYear, \pdf\PdfElement::JOURNAL_INDEX);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameJournal($data->eCompany).'.pdf';

		throw new PdfAction($content, $filename);
	});
?>
