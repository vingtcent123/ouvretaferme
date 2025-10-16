<?php
namespace overview;

class SigLib {

	const CHIFFRE_AFFAIRES = 'chiffre-affaires';
	const VENTE_MARCHANDISES = 'vente-marchandises';
	const COUT_ACHAT_MARCHANDISES = 'cout-achat-marchandises';
	const MARGE_COMMERCIALE = 'marge-commerciale';
	const PRODUCTION_VENDUE = 'production-vendue';
	const PRODUCTION_STOCKEE = 'production-stockee';
	const PRODUCTION_IMMOBILISEE = 'production-immobilisee';
	const PRODUCTION_EXERCICE = 'production-exercice';
	const ACHAT_ANIMAUX = 'achat-animaux';
	const PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX = 'production-exercice-nette-achat-animaux';
	const MP_APPROVISIONNEMENTS_CONSOMMES = 'mp-approvisionnements-consommes';
	const SOUS_TRAITANCE_DIRECTE = 'sous-traitance-directe';
	const MARGE_BRUTE_PRODUCTION = 'marge-brute-production';
	const MARGE_TOTALE = 'marge-totale';
	const AUTRES_ACHATS = 'autres-achats';
	const CHARGES_EXTERNES = 'charges-externes';
	const VALEUR_AJOUTEE = 'valeur-ajoutee';
	const SUBVENTIONS_EXPLOITATION = 'subventions-exploitation';
	const IMPOTS_TAXES_VERSEMENTS = 'impots-taxes-versements';
	const REMUNERATIONS = 'remunerations';
	const EBE = 'ebe';
	const AUTRES_PRODUITS_EXPLOITATION = 'autres-produits-exploitation';
	const REPRISE_AMORTISSEMENTS_PROVISIONS_TRANSFERTS = 'reprise-amortissements-provisions-transferts';
	const DOTATIONS_AMORTISSEMENTS_PROVISIONS = 'dotations-amortissements-provisions';
	const AUTRES_CHARGES_GESTION_COURANTE = 'autres-charges-gestion-courante';
	const RESULTAT_EXPLOITATION = 'resultat-exploitation';
	const QP_RESULTAT_POSITIF = 'qp-resultat-positif';
	const PRODUITS_FINANCIERS = 'produits-financiers';
	const QP_RESULTAT_NEGATIF = 'qp-resultat-negatif';
	const CHARGES_FINANCIERES = 'charges-financieres';
	const RCAI = 'rcai';
	const PRODUITS_EXCEPTIONNELS = 'produits-exceptionnels';
	const CHARGES_EXCEPTIONNELLES = 'charges-exceptionnelles';
	const RESULTAT_EXCEPTIONNEL = 'resultat-exceptionnel';
	const PARTICIPATION_SALARIES = 'participation-salaries';
	const IMPOTS_BENEFICES = 'impots-benefices';
	const RESULTAT_NET = 'resultat-net';

	const ACCOUNTS_TITLES = [
		self::CHIFFRE_AFFAIRES, self::MARGE_COMMERCIALE,
		self::PRODUCTION_EXERCICE, self::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX,
		self::MARGE_BRUTE_PRODUCTION, self::MARGE_TOTALE,
		self::VALEUR_AJOUTEE,
		self::EBE,
		self::RESULTAT_EXPLOITATION,
		self::RCAI,
		self::RESULTAT_EXCEPTIONNEL,
		self::RESULTAT_NET,
	];

	const ACCOUNTS_ORDER = [
		self::CHIFFRE_AFFAIRES,
		self::VENTE_MARCHANDISES, self::COUT_ACHAT_MARCHANDISES, self::MARGE_COMMERCIALE,
		self::PRODUCTION_VENDUE, self::PRODUCTION_STOCKEE, self::PRODUCTION_IMMOBILISEE, self::PRODUCTION_EXERCICE,
		self::ACHAT_ANIMAUX, self::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX,
		self::MP_APPROVISIONNEMENTS_CONSOMMES, self::SOUS_TRAITANCE_DIRECTE, self::MARGE_BRUTE_PRODUCTION, self::MARGE_TOTALE,
		self::AUTRES_ACHATS, self::CHARGES_EXTERNES, self::VALEUR_AJOUTEE,
		self::SUBVENTIONS_EXPLOITATION, self::IMPOTS_TAXES_VERSEMENTS, self::REMUNERATIONS, self::EBE,
		self::AUTRES_PRODUITS_EXPLOITATION, self::REPRISE_AMORTISSEMENTS_PROVISIONS_TRANSFERTS, self::DOTATIONS_AMORTISSEMENTS_PROVISIONS, self::AUTRES_CHARGES_GESTION_COURANTE, self::RESULTAT_EXPLOITATION,
		self::QP_RESULTAT_POSITIF, self::PRODUITS_FINANCIERS, self::QP_RESULTAT_NEGATIF, self::CHARGES_FINANCIERES, self::RCAI,
		self::PRODUITS_EXCEPTIONNELS, self::CHARGES_EXCEPTIONNELLES, self::RESULTAT_EXCEPTIONNEL,
		self::PARTICIPATION_SALARIES, self::IMPOTS_BENEFICES, self::RESULTAT_NET,
	];

 	const ACCOUNTS = [
		self::VENTE_MARCHANDISES => ['707', '7097'],
	  self::COUT_ACHAT_MARCHANDISES => ['607', '6087', '609!', '6097', '6037'],
	  self::PRODUCTION_VENDUE => ['701', '702', '703', '709!', '7091', '7092', '7093','704', '705', '706', '708', '7094', '7095', '7096', '7098'],
	  self::PRODUCTION_STOCKEE => ['713'],
	  self::PRODUCTION_IMMOBILISEE => ['72', '73'],
	  self::ACHAT_ANIMAUX => ['60117'],
	  self::MP_APPROVISIONNEMENTS_CONSOMMES => ['601', '602', '6081', '6082', '6091', '6092', '603!', '6030', '6031', '6032', '6033', '6035'],
	  self::SOUS_TRAITANCE_DIRECTE => ['604'],
	  self::AUTRES_ACHATS => ['605', '606', '6084', '6085', '6086', '6094', '6095', '6096', '6098', '611'],
	  self::CHARGES_EXTERNES => ['61', '-611', '62'],
	  self::SUBVENTIONS_EXPLOITATION => ['74'],
	  self::IMPOTS_TAXES_VERSEMENTS => ['63'],
	  self::REMUNERATIONS => ['641', '643', '644', '648', '645', '646', '647', '649'],
	  self::AUTRES_PRODUITS_EXPLOITATION => ['751', '752', '753', '754', '756', '758', '757', '786', '796'],
	  self::REPRISE_AMORTISSEMENTS_PROVISIONS_TRANSFERTS => ['781', '791'],
	  self::DOTATIONS_AMORTISSEMENTS_PROVISIONS => ['681!', '6811', '6812', '6816', '6817'],
	  self::AUTRES_CHARGES_GESTION_COURANTE => ['65', '-655'],
	  self::QP_RESULTAT_POSITIF => ['755'],
	  self::PRODUITS_FINANCIERS => ['76'],
	  self::QP_RESULTAT_NEGATIF => ['655'],
	  self::CHARGES_FINANCIERES => ['66'],
	  self::PRODUITS_EXCEPTIONNELS => ['77'],
	  self::CHARGES_EXCEPTIONNELLES => ['67'],
	  self::PARTICIPATION_SALARIES => ['691'],
	  self::IMPOTS_BENEFICES => ['689', '695', '696', '697', '698', '699', '-789'],
	];

	 public static function isCharge(string $account): bool {
		 return mb_substr($account, 0, 1) === \account\AccountSetting::CHARGE_ACCOUNT_CLASS;
	 }

	 public static function getAmountSql(string $account): string {

		 if(self::isCharge($account)) {
			 return 'IF(type = "'.\journal\Operation::DEBIT.'", amount, -1 * amount)';
		 }

		 return 'IF(type = "'.\journal\Operation::CREDIT.'", amount, -1 * amount)';

	 }
	public static function compute(\account\FinancialYear $eFinancialYear): array {

		// Si CHARGE_ACCOUNT_CLASS : debit - credit / Sinon : credit - debit
		$indexes = [];
		$select = [];
		$index = 0;
		foreach(self::ACCOUNTS as $category => $accounts) {
			$indexes[$index] = $category;
			$accountLabel = [];
			foreach($accounts as $account) {
				if(mb_substr($account, -1) === '!') {
					$accountLabel[] = 'SUM(IF(accountLabel LIKE "'.$account.'0%", '.self::getAmountSql(mb_substr($account, 0, mb_strlen($account) - 1)).', 0))';
				} else if(mb_substr($account, 0, 1) === '-') {
					$accountLabel[] = 'SUM(IF(accountLabel LIKE "'.mb_substr($account, 1).'%", -1 * '.self::getAmountSql(mb_substr($account, 1)).', 0))';
				} else {
					$accountLabel[] = 'SUM(IF(accountLabel LIKE "'.$account.'%", '.self::getAmountSql($account).', 0))';
				}
			}
			$select[] = new \Sql(join(' + ', $accountLabel), $index);
			$index++;
		}

		$eOperation = \journal\Operation::model()
			->select($select)
			->where(new \Sql('date BETWEEN '.\journal\Operation::model()->format($eFinancialYear['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYear['endDate'])))
			->get();

		// Formattage du tableau des valeurs
		$values = [];
		foreach($indexes as $index => $category) {
			$values[$category] = round($eOperation[$index], 2);
		}

		$values[self::CHIFFRE_AFFAIRES] = round($values[self::VENTE_MARCHANDISES] + $values[self::PRODUCTION_VENDUE], 2);
		$values[self::MARGE_COMMERCIALE] = round($values[self::VENTE_MARCHANDISES] - $values[self::COUT_ACHAT_MARCHANDISES], 2);
		$values[self::PRODUCTION_EXERCICE] = round($values[self::PRODUCTION_VENDUE] + $values[self::PRODUCTION_STOCKEE] + $values[self::PRODUCTION_IMMOBILISEE]);
		$values[self::PRODUCTION_EXERCICE_NET_ACHAT_ANIMAUX] = round($values[self::PRODUCTION_EXERCICE] - $values[self::ACHAT_ANIMAUX]);
		$values[self::MARGE_BRUTE_PRODUCTION] = round($values[self::PRODUCTION_EXERCICE] - $values[self::MP_APPROVISIONNEMENTS_CONSOMMES] - $values[self::SOUS_TRAITANCE_DIRECTE]);
		$values[self::MARGE_TOTALE] = round($values[self::MARGE_COMMERCIALE] + $values[self::MARGE_BRUTE_PRODUCTION]);
		$values[self::VALEUR_AJOUTEE] = round($values[self::MARGE_TOTALE] - $values[self::AUTRES_ACHATS] - $values[self::CHARGES_EXTERNES]);
		$values[self::EBE] = round($values[self::VALEUR_AJOUTEE] + $values[self::SUBVENTIONS_EXPLOITATION] - $values[self::IMPOTS_TAXES_VERSEMENTS] - $values[self::REMUNERATIONS]);
		$values[self::RESULTAT_EXPLOITATION] = round($values[self::EBE] + $values[self::AUTRES_PRODUITS_EXPLOITATION] + $values[self::REPRISE_AMORTISSEMENTS_PROVISIONS_TRANSFERTS] - $values[self::DOTATIONS_AMORTISSEMENTS_PROVISIONS] - $values[self::AUTRES_CHARGES_GESTION_COURANTE]);
		$values[self::RCAI] = round($values[self::RESULTAT_EXPLOITATION] + $values[self::QP_RESULTAT_POSITIF] + $values[self::PRODUITS_FINANCIERS] - $values[self::QP_RESULTAT_NEGATIF] - $values[self::CHARGES_FINANCIERES]);
		$values[self::RESULTAT_EXCEPTIONNEL] = round($values[self::PRODUITS_EXCEPTIONNELS] - $values[self::CHARGES_EXCEPTIONNELLES]);
		$values[self::RESULTAT_NET] = round($values[self::RCAI] + $values[self::RESULTAT_EXCEPTIONNEL] - $values[self::PARTICIPATION_SALARIES] - $values[self::IMPOTS_BENEFICES]);

		return $values;

	}

}
?>
