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
			'paymentMethod' => GET('paymentMethod'),
			'hasDocument' => GET('hasDocument'),
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

		$code = GET('code');
		if(in_array($code, \journal\Operation::model()->getPropertyEnum('journalCode')) === FALSE) {
			$code = NULL;
		}
		$search->set('journalCode', $code);

		$data->cOperation = \journal\OperationLib::getAllForJournal($search, $hasSort);
		$data->cAccount = \account\AccountLib::getAll();

		// Journaux de TVA
		if($data->eFinancialYear['hasVat']) {
			$data->operationsVat = [
				'buy' => \journal\OperationLib::getAllForVatJournal('buy', $search, $hasSort),
				'sell' => \journal\OperationLib::getAllForVatJournal('sell', $search, $hasSort),
			];
		}

		// Payment methods
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);

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
