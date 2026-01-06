<?php
namespace company;

class CompanySetting extends \Settings {

	const BETA = TRUE;
	const ACCOUNTING_FARM_BETA = [
		62, 86, 92, 266, 359, 913, 962, 972, 1160, 1298, 1340, 1396, 1597, 1612, 1954, 2468, 2607, 2736,
	];

	const CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL = 1000;
	const CATEGORIE_JURIDIQUE_SOCIETE_ANONYME = ['from' => 5410, 'to' => 5710];
	const CATEGORIE_GAEC = 6533;

}
?>
