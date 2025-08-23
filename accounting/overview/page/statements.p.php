<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	if(strpos(LIME_PAGE_REQUESTED, ':') !== FALSE) {
		$currentPage = substr(LIME_PAGE_REQUESTED, strpos(LIME_PAGE_REQUESTED, ':') + 1);
	} else {
		$currentPage = 'index';
	}

	if($currentPage === 'index') {

		$data->selectedView = $data->eFarm->getView('viewAccountingStatements');

	} else if(in_array($currentPage, ['bilans', 'balances'])) {

		$data->selectedView = match($currentPage) {
			'bilans' => \farm\Farmer::BALANCE_SHEET,
			'balances' => \farm\Farmer::TRIAL_BALANCE,
		};

		\farm\FarmerLib::setView('viewAccountingStatements', $data->eFarm, $data->selectedView);
	}
})
	->get(['index', 'bilans', 'balances'], function($data) {

		switch($data->selectedView) {

			case \farm\Farmer::BALANCE_SHEET:

				$data->balanceOpening = \overview\BalanceLib::getOpeningBalance($data->eFinancialYear);
				$data->balanceSummarized = \overview\BalanceLib::getSummarizedBalance($data->eFinancialYear);
				$data->balanceDetailed = \overview\BalanceLib::getDetailedBalance($data->eFinancialYear);

				throw new ViewAction($data, ':'.\farm\Farmer::BALANCE_SHEET);

			case \farm\Farmer::TRIAL_BALANCE:

				$data->accountingBalanceSheet = \overview\AccountingLib::getAccountingBalanceSheet($data->eFinancialYear);
				$data->summaryAccountingBalance = \overview\AccountingLib::getSummaryAccountingBalance($data->accountingBalanceSheet);

				throw new ViewAction($data, ':'.\farm\Farmer::TRIAL_BALANCE);

		}

	})
	->get('pdfBalances', function($data) {

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
