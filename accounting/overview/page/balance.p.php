<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'))->validate('canManage');
	// TODO Récupérer et sauvegarder dynamiquement
	$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	\Setting::set('main\viewOverview', 'balance');

})
->get('index', function($data) {

	$data->balanceOpening = \overview\BalanceLib::getOpeningBalance($data->eFinancialYear);
	$data->balanceSummarized = \overview\BalanceLib::getSummarizedBalance($data->eFinancialYear);
	$data->balanceDetailed = \overview\BalanceLib::getDetailedBalance($data->eFinancialYear);

	throw new \ViewAction($data);
})
->get('pdf', function($data) {

	$type = match(GET('type')) {
		'opening' => \pdf\PdfElement::OVERVIEW_BALANCE_OPENING,
		'summary' => \pdf\PdfElement::OVERVIEW_BALANCE_SUMMARY,
		default => throw new NotExpectedAction('Unknown type of balance PDF.'),
	};

	$content = \pdf\PdfLib::generate($data->eFarm, $data->eFinancialYear, $type);

	if($content === NULL) {
		throw new NotExistsAction();
	}

	$filename = \overview\PdfUi::filenameBalance($data->eFarm).'.pdf';

	throw new PdfAction($content, $filename);
});
?>
