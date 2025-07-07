<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \journal\VatDeclarationUi()->create($data->eFarm, $data->eFinancialYear, $data->cOperationWaiting, $data->vatByType);

});
