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
	'subventionAssetClass' => '13',
	'subventionDepreciationAssetClass' => '777',

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

	'subventionAssetsDepreciationChargeClass' => '139', // Subventions d'investissement inscrites au CdR
	'intangibleAssetsDepreciationChargeClass' => '68111', // Dotation aux amortissements sur immos incorporelles
	'tangibleAssetsDepreciationChargeClass' => '68112', // Dotation aux amortissements sur immos corporelles
	'exceptionalDepreciationChargeClass' => '6871', // Dotation aux amortissements exceptionnels

	'receivablesOnAssetDisposalClass' => '462', // Créances sur cessions d'immobilisations

	'summaryAccountingBalanceCategories' => account\AccountUi::getSummaryBalanceCategories(),
	'balanceAssetCategories' => account\AccountUi::getAssetBalanceCategories(),
	'balanceLiabilityCategories' => account\AccountUi::getLiabilityBalanceCategories(),

	'vatBuyVatClasses' => ['44562', '44566'],
	'vatBuyClassPrefix' => '4456',
	'vatSellVatClasses' => ['44571'],
	'vatSellClassPrefix' => '4457',

	// Classement des classes par journal
	'classesByJournal' => [
		\journal\Operation::BAN => ['5'],
		\journal\Operation::ACH => ['60', '61', '62', '63', '64', '44566'],
		\journal\Operation::VEN => ['70', '71', '72', '74', '44571'],
		\journal\Operation::OD => ['28', '29', '65', '68', '69'],
	]

]);
?>
