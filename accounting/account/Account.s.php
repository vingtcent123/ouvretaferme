<?php
namespace account;

class AccountSetting extends \Settings {

	public static $remoteKey;

	public static $dropbox = [
		'appKey' => '',
		'appSecret' => '',
		'redirectDomain' => '',
	];

	const ASSET_CLASS = 2;
	const GRANT_ASSET_CLASS = '13';
	const GRANT_DEPRECIATION_CLASS = '777'; // Quote-part des subventions d'investissement virées au résultat de l'exercice

	const THIRD_ACCOUNT_GENERAL_CLASS = 4;
	const VAT_CLASS = 445;
	const BANK_ACCOUNT_GENERAL_CLASS = 5;
	const CHARGE_ACCOUNT_CLASS = 6;
	const PRODUCT_ACCOUNT_CLASS = 7;

	const BANK_ACCOUNT_CLASS = '512';
	const CASH_ACCOUNT_CLASS = '5310'; // caisse
	const DEFAULT_BANK_ACCOUNT_LABEL = '5121';

	const THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS = '401';
	const THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS = '401';


	const NON_DEPRECIABLE_ASSET_CLASS = '2125';

	const SHIPPING_CHARGE_ACCOUNT_CLASS = '624';

	const DISPOSAL_ASSET_VALUE_CLASS = '675'; // Valeur comptable des éléments d'actifs cédés
	const PRODUCT_ASSET_VALUE_CLASS = '775'; // Produits des cessions d'éléments d'actif

	const INTANGIBLE_ASSETS_CLASS = 20; // Immobilisations incorporelles
	const TANGIBLE_ASSETS_CLASS = 21; // Immobilisations corporelles

	const GRANTS_IN_INCOME_STATEMENT = '139'; // Subventions d'investissement inscrites au CdR
	const INTANGIBLE_ASSETS_DEPRECIATION_CHARGE_CLASS = '68111'; // Dotation aux amortissements sur immos incorporelles
	const TANGIBLE_ASSETS_DEPRECIATION_CHARGE_CLASS = '68112'; // Dotation aux amortissements sur immos corporelles

	const RECEIVABLES_ON_ASSET_DISPOSAL_CLASS = '462'; // Créances sur cessions d'immobilisations

	public static $summaryAccountingBalanceCategories;
	public static $balanceActifCategories;
	public static $balancePassifCategories;

	const VAT_BUY_CLASS_PREFIX = '4456'; // TVA déductible (sur les ventes)
	const VAT_SELL_CLASS_PREFIX = '4457'; // TVA collectée (sur les achats)

	const COLLECTED_VAT_CLASS = '44571'; // TVA collectée
	const PAYABLE_VAT_CLASS = '44571'; // TVA à décaisser

	const CARRIED_VAT_CLASS = '44567'; // TVA à reporter

	// Charges et produits constatés d'avance
	const PREPAID_EXPENSE_CLASS = '486';  // Charge constatée d'avance
	const ACCRUED_EXPENSE_CLASS = '486'; // Produit constaté d'avance

	// Compte de stock => Compte de variation correspondant
	const STOCK_VARIATION_CLASSES = [
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
	];

	const CLASSES_BY_JOURNAL = [
		\journal\Operation::BAN => ['5'],
		\journal\Operation::ACH => ['60', '61', '62', '63', '64', '4456'],
		\journal\Operation::VEN => ['70', '71', '72', '74', '4452', '4457'],
		\journal\Operation::OD => ['28', '29', '65', '68', '69'],
	];
}

AccountSetting::$summaryAccountingBalanceCategories = AccountUi::getSummaryBalanceCategories();
AccountSetting::$balanceActifCategories = AccountUi::getActifBalanceCategories();
AccountSetting::$balancePassifCategories = AccountUi::getPassifBalanceCategories();

AccountSetting::$remoteKey = fn() => throw new \Exception('Undefined remote key');

?>
