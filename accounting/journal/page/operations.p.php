<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eThirdParty = get_exists('thirdParty')
			? account\ThirdPartyLib::getById(GET('thirdParty', 'int'))
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

		if($data->eFarm['company']->isAccrualAccounting()) {

			$code = GET('code');
			if(in_array($code, \journal\Operation::model()->getProperty('journalCode')) === FALSE) {
				$code = NULL;
			}
			$search->set('journalCode', $code);

		}

		$data->cOperation = \journal\OperationLib::getAllForJournal($search, $hasSort);
		$data->cAccount = \account\AccountLib::getAll();

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$content = pdf\PdfLib::generate($data->eFarm, $data->eFinancialYear, \pdf\PdfElement::JOURNAL_INDEX);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameJournal($data->eFarm).'.pdf';

		throw new PdfAction($content, $filename);
	});
?>
