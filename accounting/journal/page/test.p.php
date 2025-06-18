<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = REQUEST('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eCompany);
		if(LIME_ENV !== 'dev') {
			throw new NotExistsAction();
		}
	})
	->get('index', function($data) {

		$eFinancialYear = \accounting\FinancialYearLib::getLastClosedFinancialYear();
		pdf\PdfLib::generate($data->eCompany, $eFinancialYear, 'overview-balance-summary');
		throw new ViewAction($data);

	});

?>
