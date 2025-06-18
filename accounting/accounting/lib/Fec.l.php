<?php
namespace accounting;

/**
 * https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775
 * https://rfcomptable.grouperf.com/webinaire/FEC_ERP/fec.pdf
 */
class FecLib  {

	/**
	 * TODO : faire une notice justificative (?)
	 * @param FinancialYear $eFinancialYear
	 * @return string
	 */
	public static function generate(FinancialYear $eFinancialYear): string {

		$headers = [
			'JournalCode', // peut être vide
			'JournalLib', // peut être vide
			'EcritureNum',
			'EcritureDate',
			'CompteNum', // peut être vide
			'CompteLib',
			'CompAuxNum', // peut être vide
			'CompAuxLib', // peut être vide
			'PieceRef',
			'PieceDate', // Date sur la pièce ou date d'enregistrement
			'EcritureLib',
			'Debit',
			'Credit',
			'EcritureLet',// peut être vide
			'DateLet', // peut être vide
			'ValidDate',
			'Montantdevise', // peut être vide
			'Idevise', // peut être vide
			'DateRglt', // Utilisé en BA en compta de trésorerie uniquement
			'ModeRglt', // Utilisé en BA en compta de trésorerie uniquement
			'NatOp', // peut être vide, Utilisé en BA en compta de trésorerie uniquement
		];
		$fecData = [
			join('|', $headers),
		];

		$search = new \Search(['financialYear' => $eFinancialYear]);
		$cOperation = \journal\OperationLib::getAllForJournal($search);

		foreach($cOperation as $eOperation) {

			$operationData = [
				'',
				'',
				str_pad($eOperation['number'], 6, '0', STR_PAD_LEFT),
				date('Ymd', strtotime($eOperation['date'])),
				$eOperation['accountLabel'],
				$eOperation['account']['description'],
				'',
				'',
				$eOperation['document'],
				$eOperation['documentDate'] !== NULL ? date('Ymd', strtotime($eOperation['documentDate'])) : NULL,
				$eOperation['description'],
				$eOperation['type'] === \journal\OperationElement::DEBIT ? $eOperation['amount'] : 0,
				$eOperation['type'] === \journal\OperationElement::CREDIT ? $eOperation['amount'] : 0,
				'',
				'',
				date('Ymd', strtotime($eFinancialYear['closeDate'])),
				'',
				'',
				$eOperation['paymentDate'] !== NULL ? date('Ymd', strtotime($eOperation['paymentDate'])) : NULL,
				$eOperation['paymentMode'],
				'',
			];
			$fecData[] = join('|', $operationData);

		}

		return join("\n", $fecData);

	}

}
