<?php
new \Page()
	->get('reconciliate', function($data) {

		$data->cSuggestion = \preaccounting\SuggestionLib::getByIds(GET('ids', 'array'));

		throw new ViewAction($data);

	})
	->post('cancel', function($data) {

		$eCashflow = \bank\CashflowLib::getById(POST('cashflow'))->validate('acceptCancelReconciliation');

		\preaccounting\ReconciliateLib::cancelReconciliation($data->eFarm, $eCashflow);

		throw new ReloadAction('preaccounting', 'Reconciliation::cancelled');

	})
;
new \preaccounting\SuggestionPage()
	->doUpdateProperties('doUpdatePaymentMethod', ['paymentMethod'], fn($data) => throw new ViewAction($data))
	->write('doIgnore', function($data) {

		$data->e->validate('acceptIgnore');

		preaccounting\SuggestionLib::ignore($data->e);

		throw new ReloadAction('preaccounting', 'Reconciliation::ignored');

	})
	->write('doReconciliate', function($data) {

		$data->e->validate('acceptReconciliate');

		preaccounting\ReconciliateLib::reconciliateSuggestion($data->eFarm, $data->e);

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
