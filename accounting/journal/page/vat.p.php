<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

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

		$data->eFinancialYear['lastPeriod'] = \journal\VatDeclarationLib::calculateLastPeriod($data->eFinancialYear);

		$search->set('maxDate', $data->eFinancialYear['lastPeriod']['end'] ?? $data->eFinancialYear['startDate']);
		$data->vatDeclarationData = [
			'cVatDeclaration' => \journal\VatDeclarationLib::getByFinancialYear($data->eFinancialYear),
			'cOperationWaiting' => \journal\OperationLib::getAllForVatDeclaration($search),
		];

		throw new ViewAction($data);

	})
	->get('pdf', function($data) {

		$data->type = GET('type',  'string', 'buy');
		if(in_array($data->type, ['buy', 'sell']) === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of vat journal with no type');
		}

		$content = pdf\PdfLib::generate(
			$data->eFarm,
			$data->eFinancialYear,
			match($data->type) {
				'buy' => \pdf\PdfElement::JOURNAL_TVA_BUY,
				'sell' => \pdf\PdfElement::JOURNAL_TVA_SELL,
			},
		);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = journal\PdfUi::filenameVat($data->eFarm, $data->type).'.pdf';

		throw new PdfAction($content, $filename);

	});
