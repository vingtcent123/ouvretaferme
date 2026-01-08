<?php
new \journal\OperationPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->read('set', function($data) {

			throw new ViewAction($data);

	}, validate: ['acceptDeferral'])
	->write('doSet', function($data) {

			$success = \journal\DeferralLib::createDeferral($data->e, $_POST);

			throw new RedirectAction(\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:close?id='.$data->eFarm['eFinancialYear']['id'].'&success=journal:'.$success);

	}, validate: ['acceptDeferral'])
;

new \journal\DeferralPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}
})
	->doDelete(function($data) {

		\account\LogLib::save('delete', 'Deferral', ['id' => $data->e['id']]);

		throw new ReloadAction('journal', 'Deferral::'.$data->e['type'].'.deleted');

	})
;
