<?php
new Page(function($data) {

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

	\Setting::set('main\viewJournal', 'vat');

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

	$data->search = clone $search;

})
	->get('index', function($data) {

		$hasSort = get_exists('sort') === TRUE;
		// Ne pas ouvrir le bloc de recherche
		$search = clone $data->search;
		$search->set('financialYear', $data->eFinancialYear);
		$data->operations = [
			'buy' => \journal\OperationLib::getAllForVatJournal('buy', $search, $hasSort),
			'sell' => \journal\OperationLib::getAllForVatJournal('sell', $search, $hasSort),
		];

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$data->type = GET('type',  'string', 'buy');
		if(in_array($data->type, ['buy', 'sell']) === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of vat journal with no type');
		}

		$content = pdf\PdfLib::generate(
			$data->eCompany,
			$data->eFinancialYear,
			match($data->type) {
				'buy' => \pdf\PdfElement::JOURNAL_TVA_BUY,
				'sell' => \pdf\PdfElement::JOURNAL_TVA_SELL,
			},
		);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameVat($data->eCompany, $data->type).'.pdf';

		throw new PdfAction($content, $filename);
	});
