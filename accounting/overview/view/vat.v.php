<?php


new AdaptativeView('/etats-financiers/declaration-de-tva/operations', function($data, PanelTemplate $t) {

	return new \overview\VatUi()->showSuggestedOperations(
		$data->eFarm, $data->eFarm['eFinancialYear'], $data->eVatDeclaration, $data->cOperation, $data->cerfaCalculated, $data->cerfaDeclared,
	);

});
