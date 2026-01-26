<?php
namespace account;

/**
 * Vocabulaire :
 * Actif              Asset
 * Actif immobilisé   Fixed Asset
 * Actif circulant    Current Asset
 * Capitaux propres   Equity
 * Debts              Dettes
 * Passif             Liability
 * Immobilisation     Asset
 * Amortissement      Amortization
 * Dépréciation       Depreciation
 * Provision          Provision
 * Extourne           Reversal
 * FNP                Accrued Expense (Facture Non Parvenue)
 * FAE                Accrued Income (Facture À Établir)
 * CCA                Prepaid Expense (Charge Constatée d'Avance)
 * PCA                Prepaid Income (Produit Constaté d'Avance)
 * Report à nouveau   Retained Earnings
 * Subvention         Grant
 */
class AccountSetting extends \Settings {

	public static $remoteKey;

	const FIRST_CUSTOM_ID = 100000;

	const LOANS_CLASS = 16;
	const RESULT_CLASS = '121';
	const FARMER_S_ACCOUNT_CLASS = 108; // Compte de l'exploitant
	const PROFIT_RETAINED_CLASS = '110';
	const LOSS_RETAINED_CLASS = '119';
	const PROFIT_CLASS = '120';
	const LOSS_CLASS = '129';
	const GRANT_ASSET_CLASS = '13';
	const EQUIPMENT_GRANT_CLASS = '131'; // Subventions d'équipement octroyée

	// CLASSES GÉNÉRALES
	const CAPITAL_GENERAL_CLASS = 1;
	const ASSET_GENERAL_CLASS = 2;
	const STOCK_GENERAL_CLASS = 3;
	const THIRD_PARTY_GENERAL_CLASS = 4;
	const FINANCIAL_GENERAL_CLASS = 5;
	const CHARGE_ACCOUNT_CLASS = 6;
	const PRODUCT_ACCOUNT_CLASS = 7;
	const WAITING_ACCOUNT_CLASS = 47; // Comptes d'attente
	const WAITING_ACCOUNT_CLASSES = [470, 471, 472, 473]; // Comptes d'attente

	// IMMOBILISATIONS
	const INTANGIBLE_ASSETS_CLASS = 20; // Immobilisations incorporelles
	const TANGIBLE_ASSETS_CLASS = 21; // Immobilisations corporelles
	const IN_CONTRUCTION_ASSETS_CLASS = 213; // Immobilisations en cours de construction
	const IN_PROGRESS_ASSETS_CLASS = 23; // Immobilisations en cours
	const TANGIBLE_LIVING_ASSETS_CLASS = 24; // Immobilisations corporelles biens vivants

	// Immos non amortissables
	const ASSET_LEASEHOLD_RIGHTS_CLASS = 206; // Droit au bail
	const ASSET_GOODWILL_CLASS = 207; // Fonds commercial
	const ASSET_LANDS_CLASS = 211; // Terrains
	const NON_AMORTIZABLE_IMPROVEMENTS_CLASS = '2125';

	// AMORTISSEMENTS, DÉPRÉCIATIONS ET PROVISIONS
	const PROVISION_CLASS = 15;
	const EXCESS_AMORTIZATION_CLASS = 145;
	const INVESTMENT_GRANT_AMORTIZATION_CLASS = '139'; // Subventions d'investissement inscrites au CdR
	const INVESTMENT_GRANT_TO_RESULT_CLASS = '747'; // Quote-part des subventions d'investissement virées au résultat de l'exercice
	const ASSET_AMORTIZATION_GENERAL_CLASS = 28;
	const ASSET_AMORTIZATION_INTANGIBLE_CLASS = 280;
	const ASSET_AMORTIZATION_TANGIBLE_CLASS = 281;
	const ASSET_AMORTIZATION_TANGIBLE_LIVING_CLASS = 284;
	const ASSET_DEPRECIATION_CLASS = 29;
	const STOCK_DEPRECIATION_CLASS = 39;
	const THIRD_PARTY_DEPRECIATION_CLASS = 49;
	const FINANCIAL_DEPRECIATION_CLASS = 59;
	const ASSETS_AMORTIZATION_CHARGE_CLASS = '6811'; // Dotation aux amortissements sur immos corporelles et incorpo
	const ASSETS_AMORTIZATION__EXCEPTIONAL_CHARGE_CLASS = '687'; // Dotation aux amortissements exceptionnels
	const INTANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS = '68111'; // Dotation aux amortissements sur immos incorporelles
	const TANGIBLE_ASSETS_AMORTIZATION_CHARGE_CLASS = '68112'; // Dotation aux amortissements sur immos corporelles
	const RECEIVABLES_ON_ASSET_DISPOSAL_CLASS = '462'; // Créances sur cessions d'immobilisations
	const RECOVERY_NORMAL_ON_ASSET_DEPRECIATION = '7816'; // Reprises sur dépréciations des immobilisations
	const RECOVERY_EXCEPTIONAL_ON_ASSET_DEPRECIATION = '7876'; // Reprises sur dépréciations exceptionnelles

	// STOCKS
	const STOCK_RAW_MATERIALS_CLASS = '31'; // Stock de matières premières
	const STOCK_OTHER_SUPPLIES_CLASS = '32'; // Stock d'autres approvisionnements
	const STOCK_GOODS_IN_PRODUCTION_CLASS = '33'; // Stock d'encours de production de biens
	const STOCK_SERVICES_IN_PROGRESS_CLASS = '34'; // Stock d'encours de production de services
	const STOCK_PRODUCTS_CLASS = '35'; // Stock de produits finis
	const STOCK_GOODS_CLASS = '37'; // Stock de produits finis

	// CLASSES de TVA
	const VAT_CLASS = 445;
	const VAT_TO_PAY_INTRACOM_CLASS = '4452'; // TVA due intracommunautaire
	const VAT_DEBIT_CLASS = '44551'; // TVA à décaisser
	const VAT_DEDUCTIBLE_INTRACOM_CLASS = '445662'; // TVA déductible intracommunautaire
	const VAT_BUY_CLASS_PREFIX = '4456'; // TVA déductible
	const VAT_BUY_CLASS_ACCOUNT = '44566'; // TVA déductible s/ABS
	const VAT_TO_REGULATE_CLASS = '4458'; // TVA à régulariser
	const VAT_DEPOSIT_CLASS = '44581'; // Acompte de TVA (état)
	const VAT_CHARGES_TO_PAY_CLASS = '44586'; // TVA à payer (FNP)
	const VAT_CHARGES_TO_COLLECT_CLASS = '44587'; // TVA à payer (FAE
	const VAT_ASSET_CLASS = '44562'; // TVA déductible s/immo
	const VAT_CREDIT_CLASS = '44567'; // Crédit de TVA à reporter
	const VAT_SELL_CLASS_PREFIX = '4457'; // TVA collectée
	const VAT_SELL_CLASS_ACCOUNT = '44571'; // TVA collectée
	const TAXES_TO_PAY_CLASS = '438'; // État - charges à payer
	const TAXES_TO_GET_CLASS = '439'; // État - produits à recevoir

	// BANQUE
	const FINANCIAL_INTERNAL_TRANSFER_CLASS = 58; // Virements internes

	// CHARGES
	const SHIPPING_CHARGE_ACCOUNT_CLASS = '624';
	const CHARGES_OTHER_CLASS = '658'; // Pénalités et autres charges
	const CHARGE_FINANCIAL_ACCOUNT_CLASS = 66;
	const CHARGE_SHIPPING_ACCOUNT_CLASS = 624;
	const CHARGE_AMORTIZATION_OPERATING_ACCOUNT_CLASS = 681;
	const CHARGE_AMORTIZATION_EXCEPTIONAL_ACCOUNT_CLASS = 687;
	const CHARGE_AMORTIZATION_FINANCIAL_ACCOUNT_CLASS = 686;
	const CHARGE_ESCOMPTES_ACCOUNT_CLASS = 665;
	const CHARGE_EXCEPTIONAL_ACCOUNT_CLASS = 67;
	const CHARGE_ASSET_NET_VALUE_CLASS = 657; // VNC

	// PRODUITS
	const PRODUCT_SOLD_ACCOUNT_CLASS = 70;
	const PRODUCT_SHIPPING_ACCOUNT_CLASS = 7085;
	const PRODUCT_SUBVENTION_ACCOUNT_CLASS = 74;
	const PRODUCT_SELF_CONSUMPTION_ACCOUNT_CLASS = 73;
	const OLD_PRODUCT_SELF_CONSUMPTION_ACCOUNT_CLASS = 726;
	const PRODUCT_OTHER_ACCOUNT_CLASS = 75;
	const PRODUCT_OTHER_CLASS = '758'; // Indemnités et autres produits
	const PRODUCT_FINANCIAL_ACCOUNT_CLASS = 76;
	const PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS = 77;
	const PRODUCT_AMORTIZATION_OPERATING_ACCOUNT_CLASS = 781;
	const PRODUCT_AMORTIZATION_EXCEPTIONAL_ACCOUNT_CLASS = 787;
	const PRODUCT_AMORTIZATION_FINANCIAL_ACCOUNT_CLASS = 786;
	const PRODUCT_ASSET_VALUE_CLASS = '757'; // Produits des cessions d'éléments d'actif

	// FINANCE
	const BANK_ACCOUNT_CLASS = '512';
	const CASH_ACCOUNT_CLASS = '53'; // caisse
	const DEFAULT_BANK_ACCOUNT_LABEL = '5121';
	
	const INTERESTS_TO_PAY_CLASS = '1648';
	const INTERESTS_TO_GET_CLASS = '2768';

	// TIERS
	const THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS = '401';
	const THIRD_ACCOUNT_SUPPLIER_TO_PAY_CLASS = '408';
	const THIRD_ACCOUNT_SUPPLIER_DEPOSIT_CLASS = '409';
	const THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS = '411';
	const THIRD_ACCOUNT_RECEIVABLE_TO_GET_CLASS = '4181';
	const THIRD_ACCOUNT_RECEIVABLE_DEPOSIT_CLASS = '419';
	const EMPLOYEE_TO_PAY_CLASS = '428';
	const SOCIAL_TO_PAY_CLASS = '438';
	const SOCIAL_TO_GET_CLASS = '438';


	public static $summaryAccountingBalanceCategories;
	public static $balanceActifCategories;
	public static $balancePassifCategories;


	// Charges et produits constatés d'avance
	const PREPAID_EXPENSE_CLASS = '486';  // Charge constatée d'avance
	const ACCRUED_EXPENSE_CLASS = '487'; // Produit constaté d'avance

	// Compte de stock => Compte de variation correspondant
	const STOCK_VARIATION_CLASSES = [ // Réfléchir à supprimer cette const au profit de 3* et 603*
		'311' => '60311', // 6031 MP & fournitures
		'312' => '60312',
		'321' => '60321', // 6032 Autres appros
		'322' => '60322',
		'323' => '60323',
		'324' => '60324',
		'325' => '60325',
		'326' => '60326',
		'327' => '60327',
		'328' => '60328',
		'329' => '60329',
		'361' => '60361', // ?
		'362' => '60362', // ?
		'371' => '60371', // 6037 Marchandises
		'372' => '60372',
		'331' => '71331', // Produits en cours
		'332' => '71332', // ?
		'341' => '71341', // Études en cours
		'342' => '71342', // ?
		'344' => '71344', // ?
		'351' => '71351', // Produits intermédiaires
		'353' => '71353', // ?
	];

	const LOG_PER_PAGE = 50;
}

AccountSetting::$summaryAccountingBalanceCategories = AccountUi::getSummaryBalanceCategories();
AccountSetting::$balanceActifCategories = AccountUi::getActifBalanceCategories();
AccountSetting::$balancePassifCategories = AccountUi::getPassifBalanceCategories();

AccountSetting::$remoteKey = fn() => throw new \Exception('Undefined remote key');

?>
