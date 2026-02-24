<?php
namespace company;

class CompanySetting extends \Settings {

	const BETA = (LIME_ENV === 'prod');
	const ACCOUNTING_FARM_BETA = [
		1, // Pour la démo
		7, 1608,
		311,
		62, 86, 92, 266, 359, 913, 962, 972, 1160, 1298, 1340, 1396, 1597, 1612, 1954, 2468, 2607, 2736, // 06 janvier
		70, 322, 527, 1041, 1283, 1756, 1890, 2756, // 08 janvier
		2154, 1269, 348, 2103, // 09 janvier
		1664, 2177, 720, 1198, 634, // 10 janvier
		1309, 917, // 11 janvier
		1068, 2399, 373, 1700, // 14 janver
		541, 2263, // 15 janvier
		628, 222, // 16 janvier
		2966, 1398, // 17 janvier
		2198, 505, // 18 janvier
		977, 717, 71, 1558, // 23 janvier
		333, 3104, 3094, 604, 448, 2832, 1609, 2905, // 25 janvier
		2992, // 26 janvier
		1842, 390, // 27 janvier
		211, // 29 janvier
		381, 2705, // 30 janvier
		1046, 1605, // 31 janvier
		2949, 30, // 2 février
		1019, 1969, 356, // 3 février
		3303, // 4 février
		1190, 368, 148, // 6 février
		3051, 478, 3188, // 7 février
		3369, // 10 février
		3312, 2988, 1754, // 12 février
		3427, 2305, // 14 février
		3485, 2583, // 15 février
		2035, // 16 février
		2433, 594, 3026, // 17 février
		3501, // 18 février
		17, 3493, // 19 février
		3520, //20 février
		209, 3378, // 22 février
		3198, // 23 février
		376, // 24 février
		3601, // 25 février
	];

	const CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL = 1000;
	const CATEGORIE_JURIDIQUE_SOCIETE_ANONYME = ['from' => 5410, 'to' => 5710];
	const CATEGORIE_GAEC = 6533;

	// Est-ce qu'on exclut les autoconso des bilans et CdR (pour le micro BA) ?
	const FEATURE_SELF_CONSUMPTION = FALSE;

}
?>
