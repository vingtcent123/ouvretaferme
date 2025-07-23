<?php
Privilege::register('account', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('account', [

	'remoteKey' => 'toto',

	'dropbox' => [
		'appKey' => '',
		'appSecret' => '',
	],

	'assetClass' => 2,
	'grantAssetClass' => '13',
	'grantDepreciationClass' => '777', // Quote-part des subventions d'investissement virées au résultat de l'exercice

	'thirdAccountGeneralClass' => 4,
	'vatClass' => '445',
	'bankAccountGeneralClass' => 5,
	'chargeAccountClass' => 6,
	'productAccountClass' => 7,

	'bankAccountClass' => '512',
	'cashAccountClass' => '5310', // caisse
	'defaultBankAccountLabel' => '5121',

	'thirdAccountSupplierDebtClass' => '401',
	'thirdAccountClientReceivableClass' => '411',

	'nonDepreciableAssetClass' => '2125',

	'shippingChargeAccountClass' => '624',

	'disposalAssetValueClass' => '675', // Valeur comptable des éléments d'actifs cédés
	'productAssetValueClass' => '775', // Produits des cessions d'éléments d'actif

	'intangibleAssetsClass' => '20', // Immobilisations incorporelles
	'tangibleAssetsClasses' => ['21', '24'], // Immobilisations corporelles

	'grantsInIncomeStatement' => '139', // Subventions d'investissement inscrites au CdR
	'intangibleAssetsDepreciationChargeClass' => '68111', // Dotation aux amortissements sur immos incorporelles
	'tangibleAssetsDepreciationChargeClass' => '68112', // Dotation aux amortissements sur immos corporelles
	'exceptionalDepreciationChargeClass' => '6871', // Dotation aux amortissements exceptionnels

	'receivablesOnAssetDisposalClass' => '462', // Créances sur cessions d'immobilisations

	'summaryAccountingBalanceCategories' => account\AccountUi::getSummaryBalanceCategories(),
	'balanceActifCategories' => account\AccountUi::getActifBalanceCategories(),
	'balancePassifCategories' => account\AccountUi::getPassifBalanceCategories(),

	'vatBuyClassPrefix' => '4456', // TVA déductible (sur les ventes)
	'vatSellClassPrefix' => '4457', // TVA collectée (sur les achats)

	'collectedVatClass' => '44571', // TVA collectée
	'payableVatClass' => '44551', // TVA à décaisser

	'carriedVatClass' => '44567', // TVA à reporter

	'prepaidExpenseClass' => '486', // Charge constatée d'avance
	'accruedIncomeClass' => '487', // Produit à recevoir

	'stockVariationClasses' => [ // Compte de stock => Compte de variation correspondant
    '311' => '60311',
    '312' => '60312',
    '321' => '60321',
    '322' => '60322',
    '323' => '60323',
    '324' => '60324',
    '325' => '60325',
    '326' => '60326',
    '327' => '60327',
    '328' => '60328',
    '329' => '60329',
    '331' => '71331',
    '332' => '71332',
    '341' => '71341',
    '342' => '71342',
    '344' => '71344',
    '351' => '71351',
    '353' => '71353',
    '361' => '60361',
    '362' => '60362',
    '371' => '60371',
    '372' => '60372',
	],

	// Classement des classes par journal
	'classesByJournal' => [
		\journal\Operation::BAN => ['5'],
		\journal\Operation::ACH => ['60', '61', '62', '63', '64', '44566'],
		\journal\Operation::VEN => ['70', '71', '72', '74', '44571'],
		\journal\Operation::OD => ['28', '29', '65', '68', '69'],
	]

]);
?>
