<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \journal\AccruedIncomeUi()->create($data->eFarm, $data->e, $data->eFinancialYear);

});
