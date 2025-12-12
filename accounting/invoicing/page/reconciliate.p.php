<?php
new Page(function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

	})
	->get('/ventes/rapprocher', function($data) {

		$from = $data->eFinancialYear['startDate'];
		$to = $data->eFinancialYear['endDate'];
		$search = new Search(['from' => $from, 'to' => $to]);

		$data->counts = \invoicing\InvoiceLib::counts($data->eFarm, $from, $to, $search);

		$data->ccSuggestion = \invoicing\SuggestionLib::getAllWaiting();

		throw new ViewAction($data);

	});

new \invoicing\SuggestionPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
	->write('doIgnore', function($data) {

		$data->e->validate('acceptAction');

		\invoicing\SuggestionLib::ignore($data->e);

		throw new ReloadAction('invoicing', 'Reconciliation::ignored');

	})
	->write('doReconciliate', function($data) {

		$data->e->validate('acceptAction');

		\invoicing\ReconciliateLib::reconciliateSuggestion($data->e);

		throw new ReloadAction('invoicing', 'Reconciliation::reconciliate');

	})
	->writeCollection('doReconciliateCollection', function($data) {

		\invoicing\Suggestion::validateBatch($data->c);

		\invoicing\ReconciliateLib::reconciliateSuggestionCollection($data->c);

		throw new ReloadAction('invoicing', 'Reconciliation::reconciliateSeveral');

	})
	->writeCollection('doIgnoreCollection', function($data) {

		\invoicing\Suggestion::validateBatch($data->c);

		\invoicing\SuggestionLib::ignoreCollection($data->c);

		throw new ReloadAction('invoicing', 'Reconciliation::ignoredSeveral');

	});
