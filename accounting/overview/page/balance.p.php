<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
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

	$content = \pdf\PdfLib::generate($data->eCompany, $data->eFinancialYear, $type);

	if($content === NULL) {
		throw new NotExistsAction();
	}

	$filename = \overview\PdfUi::filenameBalance($data->eCompany).'.pdf';

	throw new PdfAction($content, $filename);
});
?>
