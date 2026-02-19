<?php
namespace account;

/**
 * https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775
 * https://rfcomptable.grouperf.com/webinaire/FEC_ERP/fec.pdf
 *
 * I. – Les copies mentionnées au I de l'article L. 47 A sont transmises, sous forme de fichiers à plat, à organisation séquentielle et structure zonée remplissant les critères suivants :
 *
 * 1° Les enregistrements sont séparés par le caractère de contrôle Retour chariot et/ ou Fin de ligne ;
 *
 * 2° Ils peuvent être de type mono ou multistructures ;
 *
 * 3° La longueur des enregistrements peut être fixe ou variable, avec ou sans séparateur de zone ;
 *
 * 4° Le caractère séparateur de zone éventuellement utilisé est unique et non équivoque dans chaque fichier.
 *
 * II. – Chaque fichier remis est obligatoirement accompagné d'une description, qui précise :
 *
 * 1° Le nom, la nature et la signification de chaque zone ;
 *
 * 2° La signification des codes utilisés comme valeurs de zone ;
 *
 * 3° Toutes les informations techniques nécessaires au traitement des fichiers, et notamment le jeu de caractères utilisé, le type de structure, la longueur des enregistrements, les caractères séparateur de zone et séparateur d'enregistrement.
 *
 * III. – Le codage des informations doit être conforme aux spécifications suivantes :
 *
 * 1° Les caractères utilisés appartiennent à l'un des jeux de caractères ASCII, norme ISO 8859-15 ou EBCDIC ;
 *
 * 2° Les valeurs numériques sont exprimées en mode caractère et en base décimale, cadrées à droite et complétées à gauche par des zéros pour les zones de longueur fixe. Le signe est indiqué par le premier caractère à partir de la gauche. La virgule sépare la fraction entière de la partie décimale. Aucun séparateur de millier n'est accepté ;
 *
 * 3° Les zones alphanumériques sont cadrées à gauche et complétées à droite par des espaces ;
 *
 * 4° Les dates sont exprimées au format AAAAMMJJ sans séparateur. Les heures sont exprimées au format HH : MM : SS.
 *
 * IV. – En accord avec le service vérificateur, d'autres solutions d'échange peuvent être retenues dans la mesure où elles sont de nature à faciliter le traitement des données transmises.
 *
 * V. – Les copies de fichiers sont remises sur des disques optiques de type CD ou DVD non réinscriptibles, clôturés de telle sorte qu'ils ne puissent plus recevoir de données et utilisant le système de fichiers UDF et/ ou ISO 9660.
 *
 * En accord avec le service vérificateur, d'autre supports pourront être utilisés.
 *
 * VI. – Les copies des fichiers mentionnées au I de l'article L. 47 A sont transmises, au choix du contribuable sous forme de :
 *
 * 1° Fichiers à plat, à organisation séquentielle et structure zonée remplissant les critères suivants :
 *
 * a. Les enregistrements sont séparés par le caractère de contrôle Retour chariot et/ ou Fin de ligne ;
 *
 * b. La longueur des enregistrements peut être fixe ou variable ;
 *
 * c. Les zones sont obligatoirement séparées par une tabulation ou le caractère " | " ;
 *
 * 2° Fichiers structurés, codés en XML, respectant la structure du fichier XSD dont les spécifications sont consultables sur internet sur le site public http :// www. impots. gouv. fr/.
 * Date : AAAAMMJJ
 * Alphanumérique : 2° Les valeurs numériques sont exprimées en mode caractère et en base décimale, cadrées à droite et complétées à gauche par des zéros pour les zones de longueur fixe. Le signe est indiqué par le premier caractère à partir de la gauche. La virgule sépare la fraction entière de la partie décimale. Aucun séparateur de millier n'est accepté ;
 *
 * 3° Les zones alphanumériques sont cadrées à gauche et complétées à droite par des espaces ;
 *
 *
 * Prérequis :
 *  - toutes les opérations doivent être dans un journal
 *  - toutes les opérations doivent avoir un numéro séquentiel
 *  - tous les journaux doivent être équilibrés
 *  - toutes les opérations doivent avoir une pièce justificative
 */
class FecLib  {

	const HEADER_MODEL_DEBIT_CREDIT = 'debit-credit';
	const HEADER_MODEL_MONTANT_SENS = 'montant-sens';

	const HEADER_COL_MONTANT = 'montant';
	const HEADER_COL_SENS = 'sens';

	public static function checkDataForFec(\farm\Farm $eFarm, FinancialYear $eFinancialYear): array {

		$eOperation = \journal\Operation::model()
			->select([
				'noJournal' => new \Sql('SUM(IF(journalCode IS NULL, 1, 0))', 'int'),
				'hasJournal' => new \Sql('SUM(IF(journalCode IS NOT NULL, 1, 0))', 'int'),
				'noDocument' => new \Sql('SUM(IF(document IS NULL, 1, 0))', 'int'),
				'hasDocument' => new \Sql('SUM(IF(document IS NOT NULL, 1, 0))', 'int'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->get();

		$eOperationByJournal = \journal\Operation::model()
			->select([
				'credit' => new \Sql('SUM(IF(type = "credit", amount, 0))', 'float'),
				'debit' => new \Sql('SUM(IF(type = "debit", amount, 0))', 'float'),
				'isBalanced' => new \Sql('SUM(IF(type = "debit", amount, 0)) = SUM(IF(type = "credit", amount, 0))', 'bool'),
				'journalCode'
			])
			->group('journalCode')
			->getCollection();

		return [
			'hasSiren' => $eFarm['siret'] !== NULL,
			'hasJournal' => $eOperation['hasJournal'],
			'noJournal' => $eOperation['noJournal'],
			'hasDocument' => $eOperation['hasDocument'],
			'noDocument' => $eOperation['noDocument'],
			'journalBalance' => $eOperationByJournal->getArrayCopy(),
		];

	}

	public static function getFilename(\farm\Farm $eFarm, FinancialYear $eFinancialYear): string {

		$filename = '';

		if($eFarm['siret'] !== NULL) {
			$filename .= mb_substr($eFarm['siret'], 0, 9);
		}

		$filename .= 'FEC';

		if($eFinancialYear->notEmpty() and $eFinancialYear->isClosed()) {
			$filename .= date('Ymd', strtotime($eFinancialYear['endDate']));
		} else {
			$filename .= date('YmdHis');
		}

		return $filename;

	}

	public static function getHeader(bool $isCashAccounting = TRUE): array {

		$headers = [
			'JournalCode', // peut être vide Alphanumérique
			'JournalLib', // peut être vide Alphanumérique
			'EcritureNum', // Alphanumérique
			'EcritureDate', // Date
			'CompteNum', // peut être vide Alphanumérique
			'CompteLib', // Alphanumérique
			'CompAuxNum', // peut être vide Alphanumérique
			'CompAuxLib', // peut être vide Alphanumérique
			'PieceRef', // Alphanumérique
			'PieceDate', // Date sur la pièce ou date d'enregistrement Date
			'EcritureLib', // Alphanumérique
			'Debit', // Numérique
			'Credit', // Numérique
			'EcritureLet',// peut être vide Alphanumérique
			'DateLet', // peut être vide Date
			'ValidDate', // Date
			'MontantDevise', // peut être vide Alphanumérique
			'IDevise', // peut être vide Alphanumérique
		];

		if($isCashAccounting) {
			$headers[] = 'DateRglt'; // Utilisé en BA en compta de trésorerie uniquement Date
			$headers[] = 'ModeRglt'; // Utilisé en BA en compta de trésorerie uniquement Alphanumérique
			$headers[] = 'NatOp'; // peut être vide, Utilisé en BA en compta de trésorerie uniquement Alphanumérique
		}

		return $headers;

	}

	public static function getOtherHeader(bool $isCashAccounting = TRUE): array {

		$headers = [
			'JournalCode', // peut être vide Alphanumérique
			'JournalLib', // peut être vide Alphanumérique
			'EcritureNum', // Alphanumérique
			'EcritureDate', // Date
			'CompteNum', // peut être vide Alphanumérique
			'CompteLib', // Alphanumérique
			'CompAuxNum', // peut être vide Alphanumérique
			'CompAuxLib', // peut être vide Alphanumérique
			'PieceRef', // Alphanumérique
			'PieceDate', // Date sur la pièce ou date d'enregistrement Date
			'EcritureLib', // Alphanumérique
			'Montant', // Numérique
			'Sens', // C/D
			'EcritureLet',// peut être vide Alphanumérique
			'DateLet', // peut être vide Date
			'ValidDate', // Date
			'MontantDevise', // peut être vide Alphanumérique
			'IDevise', // peut être vide Alphanumérique
		];

		if($isCashAccounting) {
			$headers[] = 'DateRglt'; // Utilisé en BA en compta de trésorerie uniquement Date
			$headers[] = 'ModeRglt'; // Utilisé en BA en compta de trésorerie uniquement Alphanumérique
			$headers[] = 'NatOp'; // peut être vide, Utilisé en BA en compta de trésorerie uniquement Alphanumérique
		}

		return $headers;

	}

	public static function formatFecData(array $operations): string {

		$flattenData = [];
		foreach($operations as $operation) {
			$flattenData[] = join('|', $operation);
		}
		return join("\n", $flattenData);

	}

	/**
	 * @param FinancialYear $eFinancialYear
	 * @return string
	 */
	public static function generate(FinancialYear $eFinancialYear): array {

		$headers = self::getHeader($eFinancialYear->isCashAccounting());
		$fecData = [
			$headers,
		];

		$search = new \Search(['financialYear' => $eFinancialYear]);
		list($cOperation, , ) = \journal\OperationLib::getAllForJournal(page: NULL, search: $search);

		$number = 1;
		$journals = [];

		foreach($cOperation as $eOperation) {

			if($eOperation['amount'] === 0.0) {
				continue;
			}

			$eJournal = self::extractJournal($cOperation, $eOperation, $journals);

			$operationData = [
				$eJournal['code'] ?? 'ODJ',
				$eJournal['name'] ?? 'Opérations Diverses Journal',
				str_pad($number++, 6, '0', STR_PAD_LEFT),
				date('Ymd', strtotime($eOperation['date'])),
				$eOperation['accountLabel'],
				$eOperation['account']['description'],
				'',
				'',
				empty($eOperation['document']) ? $eOperation['id'] : $eOperation['document'],
				$eOperation['documentDate'] !== NULL ? date('Ymd', strtotime($eOperation['documentDate'])) : date('Ymd', strtotime($eOperation['date'])),
				$eOperation['description'],
				$eOperation['type'] === \journal\OperationElement::DEBIT ? number_format($eOperation['amount'], 2, ',', '') : '',
				$eOperation['type'] === \journal\OperationElement::CREDIT ? number_format($eOperation['amount'], 2, ',', '') : '',
				'',
				'',
				$eOperation['validatedAt'] !== NULL ? date('Ymd', strtotime($eOperation['validatedAt'])) : date('Ymd', strtotime($eOperation['date'])),
				number_format($eOperation['type'] === \journal\OperationElement::DEBIT ? $eOperation['amount'] : -$eOperation['amount'], 2, ',', ''),
				'EUR',
			];
			if($eFinancialYear['accountingType'] === FinancialYear::CASH) {
				$operationData[] = $eOperation['paymentDate'] !== NULL ? date('Ymd', strtotime($eOperation['paymentDate'])) : NULL;
				$operationData[] = $eOperation['paymentMethod']['name'] ?? '';
				$operationData[] = '';
			}
			$fecData[] = $operationData;

		}

		return $fecData;

	}

	private static function extractJournal(\Collection $cOperation, \journal\Operation $eOperation, array &$journals): \journal\JournalCode {

		// On va utiliser le même journal pour toutes les opérations du même hash :
		// utile dans l'import sur d'autres logiciels de compta pour l'équilibre des écritures
		if(isset($journals[$eOperation['hash']]) === FALSE) {
			if($eOperation['journalCode']->notEmpty()) {
				$journals[$eOperation['hash']] = $eOperation['journalCode'];
			} else {
				$cOperationHash = $cOperation->find(function($e) use($eOperation) {
					return $e['hash'] === $eOperation['hash'] and $e['journalCode']->notEmpty();
				});
				if($cOperationHash->notEmpty()) {
					$journals[$eOperation['hash']] = $cOperationHash->first()['journalCode'];
				}
			}
		}

		return $journals[$eOperation['hash']] ?? new \journal\JournalCode();

	}

	public static function generateByFinancialYear(\account\FinancialYear $eFinancialYear): array {

		$cOperation = \journal\Operation::model()
			->select([
				'id', 'hash', 'description', 'number', 'date', 'accountLabel', 'document', 'type', 'amount',
				'account' => ['description'],
				'journalCode' => ['code', 'name'],
				'paymentMethod' => ['name'],
			])
			->whereFinancialYear($eFinancialYear)
			->sort(['date' => SORT_ASC, 'hash' => SORT_ASC])
			->getCollection();

		$journals = [];
		return $cOperation->makeArray(function($eOperation) use (&$journals, $cOperation) {

			$eJournal = self::extractJournal($cOperation, $eOperation, $journals);

			return [
				$eJournal['code'] ?? 'ODJ',
				$eJournal['name'] ?? 'Opérations Diverses Journal',
				$eOperation['number'] ?? '',
				date('Ymd', strtotime($eOperation['date'])),
				$eOperation['accountLabel'],
				$eOperation['account']['description'],
				'',
				'',
				empty($eOperation['document']) ? $eOperation['id'] : $eOperation['document'],
				str_replace('-', '', $eOperation['documentDate'] ?? $eOperation['date']),
				$eOperation['description'],
				$eOperation['type'] === \journal\Operation::DEBIT ? number_format($eOperation['amount'], 2, ',', '') : '',
				$eOperation['type'] === \journal\Operation::CREDIT ? number_format($eOperation['amount'], 2, ',', '') : '',
				'',
				date('Ymd', strtotime($eOperation['date'])),
				date('Ymd', strtotime($eOperation['date'])),
				number_format($eOperation['type'] === \journal\Operation::DEBIT ? $eOperation['amount'] : -1 * $eOperation['amount'], 2, ',', ''),
				'',
				date('Ymd', strtotime($eOperation['date'])),
				$eOperation['paymentMethod']['name'] ?? '',
				''
			];
		});
	}

}
