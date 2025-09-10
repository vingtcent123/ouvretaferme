<?php
namespace bank;

class OfxParserLib {

	public static function extractFile(string $filepath): \SimpleXMLElement {

		// Récupérer uniquement le contenu OFX
		preg_match("'<ofx>(.*?)</ofx>'si", file_get_contents($filepath), $match);

		// Fermer correctement les tags
		$xmlContent = preg_replace('/<([A-Za-z0-9.]+)>([^<\r\n]+)/', '<\1>\2</\1>', '<OFX>'.$match[1].'</OFX>');

		return simplexml_load_string($xmlContent);

	}

	protected static function extractDate(string $date): string {
		return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2).' '.substr($date, 8, 2).':'.substr($date, 10, 2).':'.substr($date, 12, 2);
	}

	public static function extractImport(\SimpleXMLElement $xmlElement): array {

		return [
			'startDate' => self::extractDate($xmlElement->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTSTART),
			'endDate' => self::extractDate($xmlElement->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTEND),
		];

	}

	public static function extractAccount(\SimpleXMLElement $xmlElement): BankAccount {

		$bankId = $xmlElement->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->BANKID;
		$accountId = $xmlElement->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->ACCTID;

		if(strlen($bankId) === 0 or strlen($accountId) === 0) {
			return new BankAccount();
		}

		return BankAccountLib::getFromOfx($bankId, $accountId);

	}

	public static function extractOperations(\SimpleXMLElement $xmlElement, BankAccount $eBankAccount, Import $eImport): array {

		$cashflows = [];

		foreach($xmlElement->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $operation) {

			$memo = ucfirst(strtolower(mb_strlen((string)$operation->MEMO) === 0 ? (string) $operation->NAME : (string) $operation->MEMO));

			$cashflows[] = [
				'date' => (string) $operation->DTPOSTED,
				'amount' => (float) $operation->TRNAMT,
				'type' => (string) $operation->TRNTYPE,
				'fitid' => (string) $operation->FITID,
				'name' => (string) $operation->NAME,
				'memo' => $memo,
				'account' => $eBankAccount,
				'import' => $eImport,
			];

		}

		return $cashflows;

	}
}
?>
