<?php
new \preaccounting\SuggestionPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
	->doUpdateProperties('doUpdatePaymentMethod', ['paymentMethod'], fn($data) => throw new ViewAction($data))
	->write('doIgnore', function($data) {

		$data->e->validate('acceptIgnore');

		preaccounting\SuggestionLib::ignore($data->e);

		throw new ReloadAction('preaccounting', 'Reconciliation::ignored');

	})
	->write('doReconciliate', function($data) {

		$data->e->validate('acceptReconciliate');

		$cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
		preaccounting\ReconciliateLib::reconciliateSuggestion($data->eFarm, $data->e, $cPaymentMethod);

		throw new ReloadAction('preaccounting', 'Reconciliation::reconciliate');

	})
	->writeCollection('doReconciliateCollection', function($data) {

		\preaccounting\Suggestion::validateBatchReconciliate($data->c);

		preaccounting\ReconciliateLib::reconciliateSuggestionCollection($data->eFarm, $data->c);

		throw new ReloadAction('preaccounting', 'Reconciliation::reconciliateSeveral');

	})
	->writeCollection('doIgnoreCollection', function($data) {

		\preaccounting\Suggestion::validateBatch($data->c);

		preaccounting\SuggestionLib::ignoreCollection($data->c);

		throw new ReloadAction('preaccounting', 'Reconciliation::ignoredSeveral');

	});
