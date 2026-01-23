<?php
namespace bank;

class OfxParserLib {

	public static function extractFile(string $filepath): \DOMDocument {

		$dom = new \DOMDocument();

		$rawContent = file_get_contents($filepath);

		if(stripos($rawContent, '<?xml')) {

			$dom->loadXML(file_get_contents($filepath));

		} else {

			$rawContent = preg_replace('/<([A-Za-z0-9.]+)>([^<\r\n]+)/', '<\1>\2</\1>', $rawContent);

			$dom->loadHtml(
				'<?xml version="1.0" encoding="UTF-8"?>'.'<main>'.$rawContent.'</main>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOXMLDECL | LIBXML_NOBLANKS
			);

		}

		return $dom;

	}

	protected static function extractDate(string $date): string {
		return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2).' '.substr($date, 8, 2).':'.substr($date, 10, 2).':'.substr($date, 12, 2);
	}

	public static function extractImport(\DOMDocument $ofx): array {

		return [
			'startDate' => new \DateTime(mb_substr($ofx->getElementsByTagName('dtstart')->item(0)->nodeValue, 0, 8))->format('Y-m-d'),
			'endDate' => new \DateTime(mb_substr($ofx->getElementsByTagName('dtend')->item(0)->nodeValue, 0, 8))->format('Y-m-d'),
		];

	}

	public static function extractAccount(\DOMDocument $ofx): BankAccount {

		$bankId = $ofx->getElementsByTagName('bankid')->item(0)->nodeValue;
		$accountId = $ofx->getElementsByTagName('acctid')->item(0)->nodeValue;

		if(strlen($bankId) === 0 or strlen($accountId) === 0) {
			return new BankAccount();
		}

		return BankAccountLib::getFromOfx($bankId, $accountId);

	}

	public static function extractOperations(\DOMDocument $ofx, BankAccount $eBankAccount, Import $eImport): \Collection {

		$cCashflow = new \Collection();

		$transactions = $ofx->getElementsByTagName('stmttrn');

    foreach($transactions as $node) {

			$date = new \DateTime(mb_substr($node->getElementsByTagName('dtposted')->item(0)->nodeValue, 0, 8))->format('Y-m-d');
			$fitid = $node->getElementsByTagName('fitid')->item(0)->nodeValue;
			$amount = floatval(str_replace(',', '.', $node->getElementsByTagName('trnamt')->item(0)->nodeValue));
			$name = $node->getElementsByTagName('name')->item(0)->nodeValue;
			if(is_null($node->getElementsByTagName('memo')->item(0))) {
				$memo = $name;
			} else {
				$memo = $node->getElementsByTagName('memo')->item(0)->nodeValue;
			}
			$memo = ucfirst(strtolower($memo));

			if($amount >= 0) {
				$type = Cashflow::CREDIT;
			} else {
				$type = Cashflow::DEBIT;
			}

			$cCashflow->append(new Cashflow([
				'date' => $date,
				'type' => strtolower($type),
				'amount' => $amount,
				'name' => $name,
				'memo' => $memo,
				'fitid' => $fitid,
				'account' => $eBankAccount,
				'import' => $eImport,
			]));
    }

		return $cCashflow;

	}
}
?>
