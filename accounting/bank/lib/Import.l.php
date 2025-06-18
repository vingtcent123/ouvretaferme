<?php
namespace bank;

class ImportLib extends ImportCrud {

	public static function formatCurrentFinancialYearImports(\accounting\FinancialYear $eFinancialYear): array {

		$cImport = self::getAll($eFinancialYear);
		$startDate = $eFinancialYear['startDate'];

		if($eFinancialYear['endDate'] !== NULL) {
			$endDate = $eFinancialYear['endDate'];
		} else {
			$endDate = date('Y-m-d');
		}

		$imports = [];

		$startPeriod = NULL;
		$endPeriod = NULL;
		$currentPeriod = new Import();

		for($currentDate = $startDate; $currentDate <= $endDate; $currentDate = date('Y-m-d', strtotime($currentDate.' +1 day'))) {

			if($startPeriod === NULL) {
				$startPeriod = $currentDate;
			}

			$eImport = self::extractImportFromDate($cImport, $currentDate);

			if($endPeriod !== NULL) {
				if($eImport->exists() === FALSE) { // No corresponding import
					if($currentPeriod->exists() === TRUE) { // Last import existed
						$imports[] = [
							'startPeriod' => $startPeriod,
							'endPeriod' => $endPeriod,
							'import' => $currentPeriod
						];
						$startPeriod = $currentDate;
					}
				} else {
					if($currentPeriod->exists() === FALSE) { // Last import did not exist
						$imports[] = [
							'startPeriod' => $startPeriod,
							'endPeriod' => $endPeriod,
							'import' => new Import()
						];
						$startPeriod = $currentDate;
					} else {
						if($currentPeriod['id'] !== $eImport['id']) { // Changed the import
							$imports[] = [
								'startPeriod' => $startPeriod,
								'endPeriod' => $endPeriod,
								'import' => $currentPeriod
							];
							$startPeriod = $currentDate;
						}
					}
				}
			}

			$currentPeriod = $eImport;
			$endPeriod = $currentDate;
		}

		// Add the last period
		$imports[] = [
			'startPeriod' => date('Y-m-d', strtotime($startPeriod)),
			'endPeriod' => date('Y-m-d', strtotime($endPeriod)),
			'import' => $currentPeriod
		];

		return $imports;

	}

	protected static function extractImportFromDate(\Collection $cImport, string $date): Import {

		$imports = [ImportElement::FULL => [], ImportElement::PARTIAL => [], ImportElement::ERROR => [], ImportElement::NONE => []];

		foreach($cImport as $eImport) {
			if(date('Y-m-d', strtotime($eImport['startDate'])) <= $date and date('Y-m-d', strtotime($eImport['endDate'])) >= $date) {
				$imports[$eImport['status']][] = $eImport;
			}
		}

		// On récupère l'import le plus pertinent
		foreach($imports as $importList) {
			if(count($importList) > 0) {
				return $importList[0];
			}
		}
		return new Import();
	}

	public static function getAll(\accounting\FinancialYear $eFinancialYear): \Collection {
		return Import::model()
			->select(Import::getSelection() + ['account' => Account::getSelection()])
			->whereStartDate('>=', $eFinancialYear['startDate'].' 00:00:00')
			->whereEndDate('<=', $eFinancialYear['endDate'].' 23:59:59')
			->sort(['startDate' => SORT_ASC])
			->getCollection();
	}

	public static function importBankStatement(\company\Company $eCompany): ?string {

		if(isset($_FILES['ofx']) === FALSE) {
			return null;
		}

		$filepath = $_FILES['ofx']['tmp_name'];
		$filename = $_FILES['ofx']['name'];

		// Vérification de la taille (max 1 Mo)
		if(filesize($filepath) > 1024 * 1024) {
			\Fail::log('Import::ofxSize');
			return null;
		}

		try {

			$xmlFile = \bank\OfxParserLib::extractFile($filepath);;

			$eAccount = \bank\OfxParserLib::extractAccount($xmlFile);

			$import = \bank\OfxParserLib::extractImport($xmlFile);

			$eImport = new Import([
				'filename' => $filename,
				'account' => $eAccount,
				'startDate' => $import['startDate'],
				'endDate' => $import['endDate'],
				'result' => [],
				'status' => ImportElement::PROCESSING,
			]);

			Import::model()->insert($eImport);

			$cashflows = \bank\OfxParserLib::extractOperations($xmlFile, $eAccount, $eImport);
			$result = \bank\CashflowLib::insertMultiple($cashflows, $eCompany);

			if(count($result['imported']) === 0) {
				if(count($result['noFinancialYear']) > 0) {
					\Fail::log('Import::nothingImportedNoFinancialYear');
				} else {
					\Fail::log('Import::nothingImported');
				}
				$status = ImportElement::NONE;
			} else if(count($result['alreadyImported']) > 0 or count($result['invalidDate']) > 0 or count($result['noFinancialYear']) > 0) {
				$status = ImportElement::PARTIAL;
			} else {
				$status = ImportElement::FULL;
			}

		} catch (\Exception $e) {

			\Fail::log('Import::ofxError');
			$status = ImportElement::ERROR;
			$result = [];

		}

		$eImport['result'] = $result;
		$eImport['status'] = $status;
		$eImport['processedAt'] = Import::model()->now();
		self::update($eImport, ['result', 'status', 'processedAt']);

		return $status;

	}

}
?>
